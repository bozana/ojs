<?php

/**
 * @file classes/plugins/PubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for public identifiers plugins
 */

import('lib.pkp.classes.plugins.PKPPubIdPlugin');

abstract class PubIdPlugin extends PKPPubIdPlugin {

	/**
	 * Constructor
	 */
	function PubIdPlugin() {
		parent::PKPPubIdPlugin();
	}

	//
	// Implement template methods from Plugin
	//
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;
		// Exclude issue objects
		HookRegistry::register('Editor::IssueManagementHandler::editIssue', array($this, 'editIssue'));
		return true;
	}

	//
	// Protected template methods from PKPPlubIdPlugin
	//
	/**
	 * @copydoc PKPPubIdPlugin::getPubObjectTypes()
	 */
	function getPubObjectTypes() {
		return array('Issue', 'Article', 'ArticleGalley');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubObjects()
	 */
	function getPubObjects($pubObjectType) {
		$objectsToCheck = null;
		switch($pubObjectType) {
			case 'Issue':
				$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
				$objectsToCheck = $issueDao->getIssues($contextId);
				break;

			case 'Article':
				$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao PublishedArticleDAO */
				$objectsToCheck =& $articleDao->getByContextId($contextId);
				break;

			case 'ArticleGalley':
				$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
				$objectsToCheck = $galleyDao->getByJournalId($contextId);
				break;
		}
		return $objectsToCheck;
	}

	//
	// Public API
	//
	/**
	 * Exclude all issue objects (articles, galleys) from
	 * assigning them the pubId or
	 * clear pubIds of all issue objects (articles, galleys)
	 * @param $hookName string (Editor::IssueManagementHandler::editIssue)
	 * @param $params array (Issue, IssueForm)
	 */
	function editIssue($hookName, $params) {
		$issue =& $params[0];
		$issueId = $issue->getId();
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				$excludeSubmittName = 'excludeIssueObjects_' . $pubIdPlugin->getPubIdType();
				$clearSubmittName = 'clearIssueObjects_' . $pubIdPlugin->getPubIdType();
				$exclude = $clear = false;
				if (Request::getUserVar($excludeSubmittName)) $exclude = true;
				if (Request::getUserVar($clearSubmittName)) $clear = true;
				if ($exclude || $clear) {
					$articlePubIdEnabled = $pubIdPlugin->isObjectEnabled('Article', $issue->getJournalId());
					$galleyPubIdEnabled = $pubIdPlugin->isObjectEnabled('Galley', $issue->getJournalId());
					if (!$articlePubIdEnabled && !$galleyPubIdEnabled) return false;
					$settingName = $pubIdPlugin->getExcludeFormFieldName();
					$pubIdType = $pubIdPlugin->getPubIdType();
					$articleDao =& DAORegistry::getDAO('ArticleDAO');
					$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
					$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
					foreach ($publishedArticles as $publishedArticle) {
						if ($articlePubIdEnabled) {
							if ($exclude && !$publishedArticle->getStoredPubId($pubIdType)) {
								$publishedArticle->setData($settingName, 1);
								$articleDao->updateArticle($publishedArticle);
							} else if ($clear) {
								$articleDao->deletePubId($publishedArticle->getId(), $pubIdType);
							}
						}
						if ($galleyPubIdEnabled) {
							$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
							$articleGalleys =& $articleGalleyDao->getGalleysByArticle($publishedArticle->getId());
							foreach ($articleGalleys as $articleGalley) {
								if ($exclude && !$articleGalley->getStoredPubId($pubIdType)) {
									$articleGalley->setData($settingName, 1);
									$articleGalleyDao->updateGalley($articleGalley);
								} else if ($clear) {
									$articleGalleyDao->deletePubId($articleGalley->getId(), $pubIdType);
								}
							}
						}
					}
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Return an array of publication object types and
	 * the corresponding DAOs.
	 * @return array
	 */
	function getDAOs() {
		return  array(
			'Issue' => 'IssueDAO',
			'Article' => 'ArticleDAO',
			'ArticleGalley' => 'ArticleGalleyDAO',
		);
	}

}

?>
