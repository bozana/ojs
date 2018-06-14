<?php

/**
 * @file plugins/generic/referenceLinking/ReferenceLinkingPlugin.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferenceLinkingPlugin
 * @ingroup plugins_generic_referenceLinking
 *
 * @brief Reference Linking plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

define('CROSSREF_API_REFS_URL', 'https://doi.crossref.org/getResolvedRefs?doi=');
// TESTING
define('CROSSREF_API_REFS_URL_DEV', 'https://test.crossref.org/getResolvedRefs?doi=');


class ReferenceLinkingPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if ($success&& $this->getEnabled($mainContextId)) {
			if (!isset($mainContextId)) $mainContextId = $this->getCurrentContextId();
			$username = $this->getSetting($mainContextId, 'username');
			$password = $this->getSetting($mainContextId, 'password');
			if ($username && $password) {
				// references tab i.e. citation form hooks
				HookRegistry::register('citationsform::display', array($this, 'getAdditionalCitationActionNames'));
				HookRegistry::register('citationsform::execute', array($this, 'citationsFormExecute'));
				HookRegistry::register('citationdao::getAdditionalFieldNames', array($this, 'getAdditionalCitationFieldNames'));
				HookRegistry::register('Templates::Controllers::Tab::PublicationEntry::Form::CitationsForm::Citation', array($this, 'displayReferenceDOI'));
				// crossref export plugin hooks
				HookRegistry::register('articlecrossrefxmlfilter::execute', array($this, 'addCrossrefCitationsElements'));
				HookRegistry::register('crossrefexportplugin::deposited', array($this, 'getCitationsDiagnosticId'));
				HookRegistry::register('articledao::getAdditionalFieldNames', array(&$this, 'getAdditionalArticleFieldNames'));
				// article page hooks
				HookRegistry::register('Templates::Article::Details::Reference', array($this, 'displayReferenceDOI'));
			}
		}
		return $success;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.referenceLinking.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.referenceLinking.description');
	}

	/**
	 * @copydoc Plugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	 * Get the handler path for this plugin.
	 * @return string
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}

	/**
	 * @see Plugin::getActions()
	 */
	public function getActions($request, $actionArgs) {
		$actions = parent::getActions($request, $actionArgs);
		if (!$this->getEnabled()) {
			return $actions;
		}
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$linkAction = new LinkAction(
			'settings',
			new AjaxModal(
				$router->url(
					$request,
					null,
					null,
					'manage',
					null,
					array(
						'verb' => 'settings',
						'plugin' => $this->getName(),
						'category' => 'generic'
					)
				),
				$this->getDisplayName()
			),
			__('manager.plugins.settings'),
			null
		);
		array_unshift($actions, $linkAction);
		return $actions;
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		$context = $request->getContext();
		$this->import('ReferenceLinkingSettingsForm');
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$form = new ReferenceLinkingSettingsForm($this, $context->getId());
				$form->initData();
				return new JSONMessage(true, $form->fetch($request));
			case 'save':
				$form = new ReferenceLinkingSettingsForm($this, $context->getId());
				$form->readInputData();
				if ($form->validate()) {
					$form->execute($request);
					$notificationManager = new NotificationManager();
					$notificationManager->createTrivialNotification(
						$request->getUser()->getId(),
						NOTIFICATION_TYPE_SUCCESS,
						array('contents' => __('plugins.generic.referenceLinking.settings.form.saved'))
					);
					return new JSONMessage(true);
				}
				return new JSONMessage(true, $settingsForm->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * Hook to articlecrossrefxmlfilter::execute and add references data to the Crossref XML export
	 * @param $hookName string
	 * @param $params array
	 * @return boolean
	 */
	function addCrossrefCitationsElements($hookName, $params) {
		$preliminaryOutput =& $params[0];
		$request = Application::getRequest();
		$context = $request->getContext();
		$publishedArticleDAO = DAORegistry::getDAO('PublishedArticleDAO');
		$citationDao = DAORegistry::getDAO('CitationDAO');

		$rfNamespace = 'http://www.crossref.org/schema/4.3.6';
		$articleNodes = $preliminaryOutput->getElementsByTagName('journal_article');
		foreach ($articleNodes as $articleNode) {
			$doiDataNode = $articleNode->getElementsByTagName('doi_data')->item(0);
			$doiNode = $doiDataNode->getElementsByTagName('doi')->item(0);
			$doi = $doiNode->nodeValue;

			$publishedArticle = $publishedArticleDAO->getPublishedArticleByPubId('doi', $doi, $context->getId());
			assert($publishedArticle);
			$articleCitations = $citationDao->getBySubmissionId($publishedArticle->getId());
			if ($articleCitations->getCount() != 0) {
				$citationListNode = $preliminaryOutput->createElementNS($rfNamespace, 'citation_list');
				while ($citation = $articleCitations->next()) {
					$rawCitation = $citation->getRawCitation();
					if (!empty($rawCitation)) {
						$citationNode = $preliminaryOutput->createElementNS($rfNamespace, 'citation');
						$citationNode->setAttribute('key', $citation->getId());
						// if Crossref DOI already exists for this citation, include it
						// else include unstructred raw citation
						if ($citation->getData($this->getCitationDoiSettingName())) {
							$citationNode->appendChild($node = $preliminaryOutput->createElementNS($rfNamespace, 'doi', htmlspecialchars($citation->getData($this->getCitationDoiSettingName()), ENT_COMPAT, 'UTF-8')));
						} else {
							$citationNode->appendChild($node = $preliminaryOutput->createElementNS($rfNamespace, 'unstructured_citation', htmlspecialchars($rawCitation, ENT_COMPAT, 'UTF-8')));
						}
						$citationListNode->appendChild($citationNode);
					}
				}
				$doiDataNode->parentNode->insertBefore($citationListNode, $doiDataNode->nextSibling);
			}
		}
		return false;
	}

	/**
	 * During the article DOI registration with Crossref, get the citations diagnostic ID from the Crossref response.
	 *
	 * @param $hookName string Hook name
	 * @param $params array Array of hook parameters
	 * @return boolean
	 */
	function getCitationsDiagnosticId($hookName, $params) {
		$response = & $params[1];
		$submission = & $params[2];
		// Get DOMDocument from the response XML string
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($response);
		if ($xmlDoc->getElementsByTagName('citations_diagnostic')->length > 0) {
			$citationsDiagnosticNode = $xmlDoc->getElementsByTagName('citations_diagnostic')->item(0);
			$citationsDiagnosticCode = $citationsDiagnosticNode->getAttribute('deferred') ;
			//set the citations diagnostic code and the setting fot the automatic check
			$submission->setData($this->getCitationsDiagnosticIdSettingName(), $citationsDiagnosticCode);
			$submission->setData($this->getAutoCheckSettingName(), true);
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateObject($submission);
		}
		return false;
	}

	/**
	 * Hook callback that returns the additional article setting names
	 * "crossref::citationsDiagnosticId" and "crossref::checkCitationsDOIs".
	 * @see DAO::getAdditionalFieldNames()
	 * @param $hookName string
	 * @param $params array
	 */
	function getAdditionalArticleFieldNames($hookName, $params) {
		$additionalFields =& $params[1];
		$additionalFields[] = $this->getCitationsDiagnosticIdSettingName();
		$additionalFields[] = $this->getAutoCheckSettingName();
	}

	/**
	 * Add "Check Crossref DOIs" button on the citations form page.
	 *
	 * @param $hookName string Hook name
	 * @param $params array Array of hook parameters
	 * @return boolean
	 */
	function getAdditionalCitationActionNames($hookName, $params) {
		$templateMgr = TemplateManager::getManager();
		$submission =& $templateMgr->get_template_vars('submission');
		$parsedCitations =& $templateMgr->get_template_vars('parsedCitations');

		$notificationLabel = '<span class="label">'.$this->getDisplayName().'</span>';
		if (!$parsedCitations->getCount() || !$submission->getStoredPubId('doi') || !$submission->getData($this->getCitationsDiagnosticIdSettingName())) {
			$notificationContents = __('plugins.generic.referenceLinking.citationsForm.warning.toCheck');
			$checkItems = array();
			if (!$parsedCitations->getCount()) {
				$checkItems[] = __('plugins.generic.referenceLinking.citationsForm.warning.extractCitations');
			}
			if (!$submission->getStoredPubId('doi')) {
				$checkItems[] = __('plugins.generic.referenceLinking.citationsForm.warning.assignDOI');
			}
			if (!$submission->getData($this->getCitationsDiagnosticIdSettingName())) {
				$checkItems[] = __('plugins.generic.referenceLinking.citationsForm.warning.registerDOI');
			}
			$commaSeparatedCheckItems = implode(', ', $checkItems);
			$notificationContents .= ' ' . $commaSeparatedCheckItems . '.';
		} else {
			$notificationContents = __('plugins.generic.referenceLinking.citationsForm.warning.toCheck.ok');
		}
		$notificationContents .= '<br />' . __('plugins.generic.referenceLinking.description.note');
		$additionalNotifications = '<div class="section">'.$notificationLabel.'<span clas="description">'.$notificationContents.'</span></div>';
		$templateMgr->assign(array(
			'additionalNotifications' => $additionalNotifications,
		));

		// Add "Check Crossref DOIs" button only if the submission has a DOI and the references were deposited
		if ($parsedCitations->getCount() && $submission->getStoredPubId('doi') && $submission->getData($this->getCitationsDiagnosticIdSettingName())) {
			$actionNames =& $templateMgr->get_template_vars('actionNames');
			$actionNames['getDois'] = __('plugins.generic.referenceLinking.citationsFormActionName');
			$templateMgr->assign(array(
				'actionNames' => $actionNames,
			));
		}
		return false;
	}

	/**
	 * Hook callback that returns the additional citation setting name
	 * "crossref::doi".
	 * @see DAO::getAdditionalFieldNames()
	 * @param $hookName string
	 * @param $params array
	 */
	function getAdditionalCitationFieldNames($hookName, $params) {
		$additionalFields =& $params[1];
		assert(is_array($additionalFields));
		$additionalFields[] = $this->getCitationDoiSettingName();
	}

	/**
	 * Hook to the CitationForm::execute function.
	 *
	 * @param $hookName string Hook name
	 * @param $params array Array of hook parameters
	 * @return boolean
	 */
	function citationsFormExecute($hookName, $params) {
		$form =& $params[0];
		$request =& $params[1];
		$submission = $form->getSubmission();
		if ($request->getUserVar('parse') && $submission->getData($this->getCitationsDiagnosticIdSettingName())) {
			// If the button "Extract and Save References" is used, the article citations will be removed and inserted anew
			// If the article DOI was already registered together with the references
			// the setting name citationsDiagnosticId has to be removed, so that the plugin knows that
			// the article DOI should be registered anew, before the possibility to "Check Crossref DOIs"
			$submission->setData($this->getCitationsDiagnosticIdSettingName(), null);
			$submission->setData($this->getAutoCheckSettingName(), null);
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateObject($submission);
		} elseif ($request->getUserVar('getDois')) {
			// If the button "Check Crossref DOIs" is used, get the found Crossref references DOIs.
			// It is enough to check if citations diagnostic ID exist -- it only exists
			// if a DOI with references is successfully registered
			if ($submission->getData($this->getCitationsDiagnosticIdSettingName())) {
				$this->getCrossrefReferencesDOIs($submission);
			}
		}
		return false;
	}

	/**
	 * Get found Crossref references DOIs for the given artilce DOI.
	 * @param $submission PublishedArticle
	 */
	function getCrossrefReferencesDOIs($submission) {
		$doi = urlencode($submission->getStoredPubId('doi'));
		if (!empty($doi)){
			$citationDao = DAORegistry::getDAO('CitationDAO');
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$citationsToCheck = $citationDao->getCitaionsBySetting($this->getCitationDoiSettingName(), null, $submission->getId());
			$citationsToCheckKeys = array_keys($citationsToCheck);
			if (!empty($citationsToCheckKeys)) {
				$matchedReferences = $this->_getResolvedRefs($doi, $submission->getContextId());
				if ($matchedReferences) {
					$filteredMatchedReferences = array_filter($matchedReferences, function ($value) use ($citationsToCheckKeys) {
						return in_array($value['key'], $citationsToCheckKeys);
					});
					foreach ($filteredMatchedReferences as $matchedReference) {
						$citation = $citationsToCheck[$matchedReference['key']];
						$citation->setData($this->getCitationDoiSettingName(), $matchedReference['doi']);
						$citationDao->updateObject($citation);
					}
					// remove auto check setting
					$submission->setData($this->getAutoCheckSettingName(), null);
					$articleDao->updateObject($submission);
				}
			}
		}
	}

	/**
	 * Insert reference DOI on the citations and article view page.
	 *
	 * @param $hookName string Hook name
	 * @param $params array Array of hook parameters
	 * @return boolean
	 */
	function displayReferenceDOI($hookName, $params) {
		$citation =& $params[0]['citation'];
		$smarty =& $params[1];
		$output =& $params[2];

		if ($citation->getData($this->getCitationDoiSettingName())) {
			$crossrefFullUrl = 'https://doi.org/' . $citation->getData($this->getCitationDoiSettingName());
			$output .= 'DOI: <a href="'.$crossrefFullUrl.'">'.$crossrefFullUrl.'</a>';
		}
		return false;
	}

	/**
	 * Check whether we are in the test mode.
	 * @param $contextId int
	 * @return boolean
	 */
	function isTestMode($contextId) {
		return ($this->getSetting($contextId, 'testMode') == 1);
	}

	/**
	 * Get citations diagnostic ID setting name.
	 * @return string
	 */
	function getCitationsDiagnosticIdSettingName() {
		return 'crossref::citationsDiagnosticId';
	}

	/**
	 * Get citation crossref DOI setting name.
	 * @return string
	 */
	function getCitationDoiSettingName() {
		return 'crossref::doi';
	}

	/**
	 * Get setting name, that defines if the scheduled task for the automatic check
	 * of the found Crossref citations DOIs should be run, if set up so in the plugin settings.
	 * @return string
	 */
	function getAutoCheckSettingName() {
		return 'crossref::checkCitationsDOIs';
	}

	/**
	 * Retrieve all articles that should be automatically checked for the found Crossref citations DOIs.
	 * @param $context Context
	 * @return DAOResultFactory
	 */
	function getArticlesToCheck($context) {
		// Retrieve all published articles with their DOIs depositted together with the references.
		// i.e. with the citations diagnostic ID setting
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articles = $publishedArticleDao->getBySetting(
			$this->getAutoCheckSettingName(),
			true,
			$context->getId()
		);
		return $articles;
	}

	/**
	 * Use Crossref API to get the references DOIs for the the given article DOI.
	 * @param $doi string
	 * @param $contextId integer
	 * @return NULL|array
	 */
	function _getResolvedRefs($doi, $contextId) {
		$matchedReferences = null;
		$curlCh = curl_init();
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		$username = $this->getSetting($contextId, 'username');
		$password = $this->getSetting($contextId, 'password');

		// Use a different endpoint for testing and production.
		$endpoint = ($this->isTestMode($contextId) ? CROSSREF_API_REFS_URL_DEV : CROSSREF_API_REFS_URL);
		curl_setopt($curlCh, CURLOPT_URL, $endpoint.$doi.'&usr='.$username.'&pwd='.$password);

		$response = curl_exec($curlCh);
		if ($response && curl_getinfo($curlCh, CURLINFO_HTTP_CODE) == 200)  {
			$response = json_decode($response, true);
			$matchedReferences = $response['matched-references'];
		}
		curl_close($curlCh);
		return $matchedReferences;
	}

}

?>
