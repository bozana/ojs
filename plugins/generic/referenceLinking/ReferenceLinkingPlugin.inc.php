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
				HookRegistry::register('citationsform::display', array($this, 'getAdditionalActionNames'));
				HookRegistry::register('citationsform::execute', array($this, 'getCrossrefDois'));
				HookRegistry::register('citationdao::getAdditionalFieldNames', array($this, 'getAdditionalCitationFieldNames'));
				HookRegistry::register('Templates::Controllers::Tab::PublicationEntry::Form::CitationsForm::Citation', array($this, 'insertReferenceDOI'));
				// crossref export plugin hooks
				HookRegistry::register('articlecrossrefxmlfilter::execute', array($this, 'addCrossrefCitationListElement'));
				HookRegistry::register('crossrefexportplugin::deposited', array($this, 'getCitationsDiagnosticId'));
				HookRegistry::register('articledao::getAdditionalFieldNames', array(&$this, 'getAdditionalArticleFieldNames'));
				// article page hooks
				HookRegistry::register('Templates::Article::Details::Reference', array($this, 'insertReferenceDOI'));
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
	 * Get the stylesheet for this plugin.
	 * @return string
	 */
	function getStyleSheet() {
		return $this->getPluginPath() . '/styles/referenceLinking.css';
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
	 */
	function addCrossrefCitationListElement($hookName, $params) {
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
						if ($citation->getData('crossref::doi')) {
							$citationNode->appendChild($node = $preliminaryOutput->createElementNS($rfNamespace, 'doi', htmlspecialchars($citation->getData('crossref::doi'), ENT_COMPAT, 'UTF-8')));
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
	 * Check if a reference DOI is found by the Crossref for a citation that misses a DOI.
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
			//set the citations diagnostic code
			$submission->setData($this->getCitationsDiagnosticIdSettingName(), $citationsDiagnosticCode);
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateObject($submission);
		}
	}

	/**
	 * Hook callback that returns the
	 * "crossref::citationsDiagnosticId" setting name.
	 * @see DAO::getAdditionalFieldNames()
	 * @param $hookName string
	 * @param $params array
	 */
	function getAdditionalArticleFieldNames($hookName, $params) {
		$additionalFields =& $params[1];
		$additionalFields[] = $this->getCitationsDiagnosticIdSettingName();
	}

	/**
	 * Add "Check Crossref DOIs" form button on the citations form page.
	 *
	 * @param $hookName string Hook name
	 * @param $params array Array of hook parameters
	 * @return boolean
	 */
	function getAdditionalActionNames($hookName, $params) {
		$templateMgr = TemplateManager::getManager();
		$submission =& $templateMgr->get_template_vars('submission');
		$parsedCitations =& $templateMgr->get_template_vars('parsedCitations');

		$notificationLabel = '<span class="label">'.$this->getDisplayName().'</span>';
		if (!$parsedCitations->getCount() || !$submission->getStoredPubId('doi') || !$submission->getData('crossref::citationsDiagnosticId')) {
			$notificationContents = __('plugins.generic.referenceLinking.citationsForm.warning.toCheck');
			$checkItems = array();
			if (!$parsedCitations->getCount()) {
				$checkItems[] = __('plugins.generic.referenceLinking.citationsForm.warning.extractCitations');
			}
			if (!$submission->getStoredPubId('doi')) {
				$checkItems[] = __('plugins.generic.referenceLinking.citationsForm.warning.assignDOI');
			}
			if (!$submission->getData('crossref::citationsDiagnosticId')) {
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

		// Add "Check Crossref DOIs" form button only if the submission has a DOI and the references were deposited
		if ($parsedCitations->getCount() && $submission->getStoredPubId('doi') && $submission->getData('crossref::citationsDiagnosticId')) {
			$actionNames =& $templateMgr->get_template_vars('actionNames');
			$actionNames['getDois'] = __('plugins.generic.referenceLinking.citationsFormActionName');
			$templateMgr->assign(array(
				'actionNames' => $actionNames,
			));
		}
		return false;
	}

	/**
	 * Hook callback that returns the
	 * "crossref::doi" setting name.
	 * @see DAO::getAdditionalFieldNames()
	 * @param $hookName string
	 * @param $params array
	 */
	function getAdditionalCitationFieldNames($hookName, $params) {
		$additionalFields =& $params[1];
		assert(is_array($additionalFields));
		$additionalFields[] = 'crossref::doi';
	}

	/**
	 * Check if a reference DOI is found by the Crossref for a citation that misses a DOI.
	 *
	 * @param $hookName string Hook name
	 * @param $params array Array of hook parameters
	 * @return boolean
	 */
	function getCrossrefDois($hookName, $params) {
		$form =& $params[0];
		$request =& $params[1];
		$submission = $form->getSubmission();
		if ($request->getUserVar('parse') && $submission->getData('crossref::citationsDiagnosticId')) {
			// if the button "Extract and Save References" was used after the article DOI was registered together with the references
			// the article citations will be removed and inserted anew
			// thus the setting name citationsDiagnosticId has to be removed too, so that the plugin knows that
			// the article DOI should be registered anew
			$submission->setData($this->getCitationsDiagnosticIdSettingName(), null);
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateObject($submission);
		} elseif ($request->getUserVar('getDois')) {
			$doi = urlencode($submission->getStoredPubId('doi'));
			if (!empty($doi) && $submission->getData('crossref::citationsDiagnosticId')) {
				$citationDao = DAORegistry::getDAO('CitationDAO');
				$citationsToCheck = $citationDao->getCitaionsBySetting('crossref::doi', null, $submission->getId());
				$citationsToCheckKeys = array_keys($citationsToCheck);
				if (!empty($citationsToCheckKeys)) {
					$matchedReferences = $this->_getResolvedRefs($doi);
					if ($matchedReferences) {
						$filteredMatchedReferences = array_filter($matchedReferences, function ($value) use ($citationsToCheckKeys) {
							return in_array($value['key'], $citationsToCheckKeys);
						});
						foreach ($filteredMatchedReferences as $matchedReference) {
							$citation = $citationsToCheck[$matchedReference['key']];
							$citation->setData('crossref::doi', $matchedReference['doi']);
							$citationDao->updateObject($citation);
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Insert reference DOI on the citations and article view page.
	 *
	 * @param $hookName string Hook name
	 * @param $params array Array of hook parameters
	 * @return boolean
	 */
	function insertReferenceDOI($hookName, $params) {
		$citation =& $params[0]['citation'];
		$smarty =& $params[1];
		$output =& $params[2];

		if ($citation->getData('crossref::doi')) {
			$crossrefFullUrl = 'https://doi.org/' . $citation->getData('crossref::doi');
			$output .= 'DOI: <a href="'.$crossrefFullUrl.'">'.$crossrefFullUrl.'</a>';
		}
		return false;
	}

	/**
	 * Check whether we are in test mode.
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

	function _getResolvedRefs($doi) {
/*
		$testJson = '
{
 "doi": "10.9876/tj1.v1i1.2",
 "matched-references": [
 {
 "key": "13",
 "doi": "10.17169/fqs-13.1.1801",
 "type": "journal_article"
}]
}
';
		$response = json_decode($testJson, true);
		return $response['matched-references'];
*/
		$file = 'debug.txt';
		$current = file_get_contents($file);
		$current .= print_r("--- _getResolvedRefs ---\n", true);
		file_put_contents($file, $current);

		$contextId = $this->getCurrentContextId();

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
