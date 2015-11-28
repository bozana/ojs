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
		return parent::register($category, $path);
	}

	//
	// Protected template methods from PKPPlubIdPlugin
	//
	/**
	 * @copydoc PKPPubIdPlugin::getPubObjectTypes()
	 */
	function getPubObjectTypes() {
		return array('Issue', 'Article', 'SubmissionFile');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubObjects()
	 */
	function getPubObjects($pubObjectType, $contextId) {
		$objectsToCheck = null;
		switch($pubObjectType) {
			case 'Issue':
				$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
				$objectsToCheck = $issueDao->getIssues($contextId);
				break;

			case 'Article':
				$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao PublishedArticleDAO */
				$objectsToCheck = $articleDao->getByContextId($contextId);
				break;

			case 'SubmissionFile':
				$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
				$galleys = $galleyDao->getByJournalId($contextId);
				$objectsToCheck = array();
				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
				foreach ($galleys as $galley) {
					$objectsToCheck = array_merge($objectsToCheck, $submissionFileDao->getAllRevisionsByAssocId(ASSOC_TYPE_REPRESENTATION, $galley->getId(), SUBMISSION_FILE_PROOF));
				}
				break;
		}
		return $objectsToCheck;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubId()
	 */
	function getPubId($pubObject, $preview = false) {
		if ($this->isObjectExcluded($pubObject)) return null;

		// Get the pub id type
		$pubIdType = $this->getPubIdType();

		// If we already have an assigned pub id, use it.
		$storedDOI = $pubObject->getStoredPubId($pubIdType);
		if ($storedDOI) return $storedDOI;

		// Determine the type of the publishing object.
		$pubObjectType = $this->getPubObjectType($pubObject);

		// Initialize variables for publication objects.
		$issue = ($pubObjectType == 'Issue' ? $pubObject : null);
		$article = ($pubObjectType == 'Article' ? $pubObject : null);
		$file = ($pubObjectType == 'SubmissionFile' ? $pubObject : null);

		// Get the context id of the object.
		if (in_array($pubObjectType, array('Issue', 'Article'))) {
			$contextId = $pubObject->getJournalId();
		} else {
			// Retrieve the published article.
			assert(is_a($pubObject, 'SubmissionFile'));
			$articleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$article =& $articleDao->getPublishedArticleByArticleId($pubObject->getSubmissionId(), null, true);
			if (!$article) return null;

			// Now we can identify the context.
			$contextId = $article->getJournalId();
		}

		$context = $this->getContext($contextId);
		if (!$context) return null;
		$contextId = $context->getId();

		// Check whether pub ids are enabled for the given object type.
		$objectTypeEnabled = $this->isObjectTypeEnabled($pubObjectType, $contextId);
		if (!$objectTypeEnabled) return null;

		// Retrieve the issue.
		if (!is_a($pubObject, 'Issue')) {
			assert(!is_null($article));
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue = $issueDao->getIssueByArticleId($article->getId(), $context->getId(), true);
		}
		if ($issue && $contextId != $issue->getJournalId()) return null;

		// Retrieve the pub id prefix.
		$pubIdPrefix = $this->getSetting($contextId, $this->getPrefixFieldName());
		if (empty($pubIdPrefix)) return null;

		// Generate the pub id suffix.
		$suffixFieldName = $this->getSuffixFieldName();
		$suffixGenerationStrategy = $this->getSetting($contextId, $suffixFieldName);
		switch ($suffixGenerationStrategy) {
			case 'customId':
				$pubIdSuffix = $pubObject->getData($suffixFieldName);
				break;

			case 'pattern':
				$suffixPatternsFieldNames = $this->getSuffixPatternsFieldNames();
				$pubIdSuffix = $this->getSetting($contextId, $suffixPatternsFieldNames[$pubObjectType]);

				// %j - journal initials
				$pubIdSuffix = String::regexp_replace('/%j/', String::strtolower($context->getAcronym($context->getPrimaryLocale())), $pubIdSuffix);

				// %x - custom identifier
				if ($pubObject->getStoredPubId('publisher-id')) {
					$pubIdSuffix = String::regexp_replace('/%x/', $pubObject->getStoredPubId('publisher-id'), $pubIdSuffix);
				}

				if ($issue) {
					// %v - volume number
					$pubIdSuffix = String::regexp_replace('/%v/', $issue->getVolume(), $pubIdSuffix);
					// %i - issue number
					$pubIdSuffix = String::regexp_replace('/%i/', $issue->getNumber(), $pubIdSuffix);
					// %Y - year
					$pubIdSuffix = String::regexp_replace('/%Y/', $issue->getYear(), $pubIdSuffix);
				}

				if ($article) {
					// %a - article id
					$pubIdSuffix = String::regexp_replace('/%a/', $article->getId(), $pubIdSuffix);
					// %p - page number
					if ($article->getPages()) {
						$pubIdSuffix = String::regexp_replace('/%p/', $article->getPages(), $pubIdSuffix);
					}
				}

				if ($file) {
					// %f - file id
					$pubIdSuffix = String::regexp_replace('/%f/', $file->getId(), $pubIdSuffix);
				}

				break;

			default:
				$pubIdSuffix = String::strtolower($context->getAcronym($context->getPrimaryLocale()));

				if ($issue) {
					$pubIdSuffix .= '.v' . $issue->getVolume() . 'i' . $issue->getNumber();
				} else {
					$pubIdSuffix .= '.v%vi%i';
				}

				if ($article) {
					$pubIdSuffix .= '.' . $article->getId();
				}

				if ($file) {
					$pubIdSuffix .= '.f' . $file->getId();
				}
		}
		if (empty($pubIdSuffix)) return null;

		// Costruct the pub id from prefix and suffix.
		$pubId = $this->constructPubId($pubIdPrefix, $pubIdSuffix, $contextId);

		if ($pubId && !$preview) {
			$this->setStoredPubId($pubObject, $pubObjectType, $pubId);
		}

		return $pubId;
	}

	//
	// Public API
	//
	/**
	 * Exclude all issue objects (articles, files) from
	 * assigning them the pubId or
	 * clear pubIds of all issue objects (articles, files)
	 * @param $exclude bool
	 * @param $clear bool
	 * @param $issue Issue
	 */
	function issueObjectsPubIdsActions($exclude, $clear, $issue) {
		$issueId = $issue->getId();
		$articlePubIdEnabled = $this->isObjectTypeEnabled('Article', $issue->getJournalId());
		$filePubIdEnabled = $this->isObjectTypeEnabled('SubmissionFile', $issue->getJournalId());
		if (!$articlePubIdEnabled && !$filePubIdEnabled) return false;
		$settingName = $this->getExcludeFormFieldName();
		$pubIdType = $this->getPubIdType();
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		foreach ($publishedArticles as $publishedArticle) {
			if ($articlePubIdEnabled) { // Does this option have to be enabled here for?
				if ($exclude && !$publishedArticle->getStoredPubId($pubIdType)) { // only if pub id is not stored
					$publishedArticle->setData($settingName, 1);
					$articleDao->updateObject($publishedArticle);
				} else if ($clear) {
					$articleDao->deletePubId($publishedArticle->getId(), $pubIdType);
				}
			}
			if ($filePubIdEnabled) { // Does this option have to be enabled here for?
				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
				$articleProofFiles = $submissionFileDao->getAllRevisionsByStageId(SUBMISSION_FILE_PROOF);
				foreach ($articleProofFiles as $articleProofFile) {
					if ($exclude && !$articleProofFile->getStoredPubId($pubIdType)) { // only if pub id is not stored
						$articleProofFile->setData($settingName, 1);
						$submissionFileDao->updateObject($articleProofFile);
					} else if ($clear) {
						$submissionFileDao->deletePubId($articleProofFile->getId(), $pubIdType);
					}
				}
			}
		}
	}

	/**
	 * @copydoc PKPPubIdPlugin::getDAOs()
	 */
	function getDAOs() {
		return  array(
			'Issue' => 'IssueDAO',
			'Article' => 'ArticleDAO',
			'SubmissionFile' => 'SubmissionFileDAO',
		);
	}

}

?>
