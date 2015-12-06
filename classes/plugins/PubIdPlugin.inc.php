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
		return array('Issue', 'Article', 'Representation', 'SubmissionFile');
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

			case 'Representation':
				$representationDao = Application::getRepresentationDAO();
				$objectsToCheck = $representationDao->getByJournalId($contextId);
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
		$representation = ($pubObjectType == 'Representation' ? $pubObject : null);
		$submissionFile = ($pubObjectType == 'SubmissionFile' ? $pubObject : null);

		// Get the context id of the object.
		if (in_array($pubObjectType, array('Issue', 'Article'))) {
			$contextId = $pubObject->getJournalId();
		} else {
			// Retrieve the article.
			assert(is_a($pubObject, 'Representation') || is_a($pubObject, 'SubmissionFile'));
			$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$article =& $articleDao->getById($pubObject->getSubmissionId(), null, true);
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

				if ($representation) {
					// %g - galley id
					$pubIdSuffix = String::regexp_replace('/%g/', $representation->getId(), $pubIdSuffix);
				}

				if ($submissionFile) {
					// %f - file id
					$pubIdSuffix = String::regexp_replace('/%f/', $submissionFile->getId(), $pubIdSuffix);
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

				if ($representation) {
					$pubIdSuffix .= '.g' . $representation->getId();
				}

				if ($submissionFile) {
					$pubIdSuffix .= '.f' . $submissionFile->getId();
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
	 * Clear pubIds of all issue objects.
	 * @param $issue Issue
	 */
	function clearIssueObjectsPubIds($issue) {
		$issueId = $issue->getId();
		$articlePubIdEnabled = $this->isObjectTypeEnabled('Article', $issue->getJournalId());
		$representationPubIdEnabled = $this->isObjectTypeEnabled('Representation', $issue->getJournalId());
		$filePubIdEnabled = $this->isObjectTypeEnabled('SubmissionFile', $issue->getJournalId());
		if (!$articlePubIdEnabled && !$representationPubIdEnabled && !$filePubIdEnabled) return false;

		$pubIdType = $this->getPubIdType();
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$representationDao = Application::getRepresentationDAO();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_... constants
		import('lib.pkp.classes.submission.SubmissionFileDAODelegate');
		$submissionFileDaoDelegate = new SubmissionFileDAODelegate();

		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		foreach ($publishedArticles as $publishedArticle) {
			if ($articlePubIdEnabled) { // Does this option have to be enabled here for?
				$articleDao->deletePubId($publishedArticle->getId(), $pubIdType);
			}
			if ($representationPubIdEnabled || $filePubIdEnabled) { // Does this option have to be enabled here for?
				$representations = $representationDao->getBySubmissionId($publishedArticle->getId());
				while ($representation = $representations->next()) {
					if ($representationPubIdEnabled) { // Does this option have to be enabled here for?
						$representationDao->deletePubId($representation->getId(), $pubIdType);
					}
					if ($filePubIdEnabled) { // Does this option have to be enabled here for?
						$articleProofFiles = $submissionFileDao->getAllRevisionsByAssocId(ASSOC_TYPE_REPRESENTATION, $representation->getId(), SUBMISSION_FILE_PROOF);
						foreach ($articleProofFiles as $articleProofFile) {
							$submissionFileDaoDelegate->deletePubId($articleProofFile->getId(), $pubIdType);
						}
					}
				}
				unset($representations);
			}
		}
	}

	/**
	 * @copydoc PKPPubIdPlugin::getDAOs()
	 */
	function getDAOs() {
/*
$file = 'debug.txt';
$current = file_get_contents($file);
$current .= print_r("++++getAdditionalFieldNames++++", true);
file_put_contents($file, $current);
*/
		$representationDao = Application::getRepresentationDAO();
		import('lib.pkp.classes.submission.SubmissionFileDAODelegate');
		$submissionFileDAODelegete = new SubmissionFileDAODelegate();
		return  array(
			'Issue' => DAORegistry::getDAO('IssueDAO'),
			'Article' => DAORegistry::getDAO('ArticleDAO'),
			'Representation' => $representationDao,
			'SubmissionFile' => $submissionFileDAODelegete,
		);
	}

}

?>
