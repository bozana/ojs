<?php

/**
 * @file plugins/importexport/crossref/CrossRefExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefExportPlugin
 * @ingroup plugins_importexport_crossref
 *
 * @brief CrossRef/MEDLINE XML metadata export plugin
 */

import('lib.pkp.classes.plugins.ImportExportPlugin');

// The status of the Crossref DOI.
// status 0 and 1 are reserved (0 = any, 1 = not deposited)
define('CROSSREF_STATUS_SUBMITTED', 2);
define('CROSSREF_STATUS_COMPLETED', 3);
define('CROSSREF_STATUS_FAILED', 4);
define('CROSSREF_STATUS_REGISTERED', 5);

class CrossRefExportPlugin extends ImportExportPlugin {
	/**
	 * Constructor
	 */
	function CrossRefExportPlugin() {
		parent::ImportExportPlugin();
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		$this->import('CrossrefExportDeployment');
		return $success;
	}

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'CrossRefExportPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.crossref.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.crossref.description');
	}

	/**
	 * @copydoc Plugin::getTemplatePath($inCore)
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}

	/**
	 * @coydoc Plugin::getLocaleFilename($locale)
	 */
	function getLocaleFilename($locale) {
		$localeFilenames = parent::getLocaleFilename($locale);

		// Add shared locale keys.
		$localeFilenames[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'common.xml';

		return $localeFilenames;
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		$user = $request->getUser();
		$router = $request->getRouter();
		$context = $router->getContext($request);

		$form = $this->_instantiateSettingsForm($context);
		$notificationManager = new NotificationManager();
		switch ($request->getUserVar('verb')) {
			case 'save':
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();
					$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS);
					return new JSONMessage(true);
				} else {
					return new JSONMessage(true, $form->fetch($request));
				}
			default:
				$form->initData();
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * @copydoc IportExportPlugin::display()
	 */
	function display(&$args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		parent::display($args, $request);

		$templateMgr->assign('plugin', $this);

		switch (array_shift($args)) {
			case 'index':
			case '':
				// Check for configuration errors:
				$configurationErrors = array();
				// 1) missing DOI prefix
				$doiPrefix = null;
				$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
				if (isset($pubIdPlugins['doipubidplugin'])) {
					$doiPlugin = $pubIdPlugins['doipubidplugin'];
					$doiPrefix = $doiPlugin->getSetting($context->getId(), $doiPlugin->getPrefixFieldName());
				}
				if (empty($doiPrefix)) {
					$configurationErrors[] = DOI_EXPORT_CONFIGERROR_DOIPREFIX;
				}

				// 2) missing plugin settings
				$form = $this->_instantiateSettingsForm($context);
				foreach($form->getFormFields() as $fieldName => $fieldType) {
					if ($form->isOptional($fieldName)) continue;
					$pluginSetting = $this->getSetting($context->getId(), $fieldName);
					if (empty($pluginSetting)) {
						$configurationErrors[] = DOI_EXPORT_CONFIGERROR_SETTINGS;
						break;
					}
				}
				$templateMgr->assign('configurationErrors', $configurationErrors);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
			case 'exportSubmissions':
				$selectedObjects = (array) $request->getUserVar('selectedSubmissions');
				if (!empty($selectedObjects)) {
					$exportXml = $this->exportSubmissions(
						$selectedObjects,
						$request->getContext(),
						$request->getUser()
					);
					header('Content-type: application/xml');
					echo $exportXml;
				} else {
					echo __('plugins.importexport.crossref.error.noObjectsSelected');
				}
				break;
			case 'exportIssues':
				$selectedObjects = (array) $request->getUserVar('selectedIssues');
				if (!empty($selectedObjects)) {
					$exportXml = $this->exportIssues(
						$selectedObjects,
						$request->getContext(),
						$request->getUser()
					);
					header('Content-type: application/xml');
					echo $exportXml;
				} else {
					echo __('plugins.importexport.crossref.error.noObjectsSelected');
				}
				break;
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	/**
	 * Get the XML for a set of submissions.
	 */
	function getPluginId() {
		return 'crossref';
	}

	/**
	 * Get pub ID type
	 */
	function getPubIdType() {
		return 'doi';
	}

	/**
	 * Get pub ID display type
	 */
	function getPubIdDisplayType() {
		return 'DOI';
	}

	/**
	 * Return the class name of the plugin's settings form.
	 * @return string
	 */
	function getSettingsFormClassName() {
		return 'CrossRefSettingsForm';
	}

	/**
	 * Get the XML for a set of submissions.
	 * @param $submissionIds array Array of submission IDs
	 * @param $context Context
	 * @param $user User
	 * @return string XML contents representing the supplied submission IDs.
	 */
	function exportSubmissions($submissionIds, $context, $user) {
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('article=>crossref-xml');
		assert(count($nativeExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment(new CrossrefExportDeployment($context, $this, $user));
		$publishedArticles = array();
		foreach ($submissionIds as $submissionId) {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($submissionId, $context->getId());
			if ($publishedArticle) $publishedArticles[] = $publishedArticle;
		}
		$exportXml = $exportFilter->execute($publishedArticles);
		if ($exportXml) $xml = $exportXml->saveXml();
		else fatalError('Could not convert submissions.');
		return $xml;
	}

	/**
	 * Get the XML for a set of issues.
	 * @param $issueIds array Array of issues IDs
	 * @param $context Context
	 * @param $user User
	 * @return string XML contents representing the supplied object IDs.
	 */
	function exportIssues($issueIds, $context, $user) {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('issue=>crossref-xml');
		assert(count($nativeExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment(new CrossrefExportDeployment($context, $this, $user));
		$issues = array();
		foreach ($issueIds as $issueId) {
			$issue = $issueDao->getById($issueId, $context->getId());
			if ($issue) $issues[] = $issue;
		}
		$exportXml = $exportFilter->execute($issues);
		if ($exportXml) $xml = $exportXml->saveXml();
		else fatalError('Could not convert issues.');
		return $xml;
	}

	/**
	 * Mark selected submission as registered.
	 * @param $submissionIds array Array of submission IDs
	 * @param $context Context
	 * @param $user User
	 */
	function markRegistered($submissionIds, $context, $user) {
		$submissionDao = Application::getSubmissionDAO();
		foreach ($submissionIds as $submissionId) {
			// TO-DO: get current status from Crossref or
			$submissionDao->updateSetting($submissionId, $this->getStatusSettingName(), CROSSREF_STATUS_SUBMITTED, 'string');
		}
	}

	/**
	 * Get status mapping for the filter search option.
	 * @return array (integer status ID => string text)
	 *  status 0 and 1 are reserved (0 = any, 1 = not deposited)
	 */
	function getStatusMapping() {
		return array(
			CROSSREF_STATUS_SUBMITTED => __('plugins.importexport.crossref.status.submitted'),
			CROSSREF_STATUS_COMPLETED => __('plugins.importexport.crossref.status.completed'),
			CROSSREF_STATUS_FAILED => __('plugins.importexport.crossref.status.failed'),
			CROSSREF_STATUS_REGISTERED => __('plugins.importexport.crossref.status.registered')
		);
	}

	/**
	 * Get status setting name.
	 * @return string
	 */
	function getStatusSettingName() {
		return $this->getPluginId().'::status';
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
//		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		$journal = $journalDao->getByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.crossref.cliError') . "\n";
				echo __('plugins.importexport.crossref.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile != '') switch (array_shift($args)) {
			case 'articles':
				$articleSearch = new ArticleSearch();
				$results = $articleSearch->formatResults($args);
				if (!$this->exportArticles($journal, $results, $xmlFile)) {
					echo __('plugins.importexport.crossref.cliError') . "\n";
					echo __('plugins.importexport.crossref.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
			case 'issue':
				$issueId = array_shift($args);
				$issue = $issueDao->getByBestId($issueId, $journal->getId());
				if ($issue == null) {
					echo __('plugins.importexport.crossref.cliError') . "\n";
					echo __('plugins.importexport.crossref.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
					return;
				}
				$issues = array($issue);
				if (!$this->exportIssues($journal, $issues, $xmlFile)) {
					echo __('plugins.importexport.crossref.cliError') . "\n";
					echo __('plugins.importexport.crossref.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
		}
		$this->usage($scriptName);

	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.crossref.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}

	/**
	 * Instantiate the settings form.
	 * @param $context Context
	 * @return CrossRefSettingsForm
	 */
	function &_instantiateSettingsForm($context) {
		$settingsFormClassName = $this->getSettingsFormClassName();
		$this->import('classes.form.' . $settingsFormClassName);
		$settingsForm = new $settingsFormClassName($this, $context->getId());
		return $settingsForm;
	}

}

?>
