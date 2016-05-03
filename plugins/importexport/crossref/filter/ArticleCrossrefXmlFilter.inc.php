<?php

/**
 * @file plugins/importexport/crossref/filter/ArticleCrossrefXmlFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleCrossrefXmlFilter
 * @ingroup plugins_importexport_crossref
 *
 * @brief Class that converts a Article to a Crossref XML document.
 */

import('plugins.importexport.crossref.filter.IssueCrossrefXmlFilter');

class ArticleCrossrefXmlFilter extends IssueCrossrefXmlFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function ArticleCrossrefXmlFilter($filterGroup) {
		$this->setDisplayName('Crossref XML article export');
		parent::IssueCrossrefXmlFilter($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.crossref.filter.ArticleCrossrefXmlFilter';
	}

	//
	// Submission conversion functions
	//

	function createJournalNode($doc, $pubObject) {
		$deployment = $this->getDeployment();
		$journalNode = parent::createJournalNode($doc, $pubObject);
		assert(is_a($pubObject, 'PublishedArticle'));
		$journalNode->appendChild($this->createJournalArticleNode($doc, $pubObject));
		return $journalNode;
	}

	function createJournalIssueNode($doc, $submission) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		assert(is_a($submission, 'PublishedArticle'));
		$issueId = $submission->getIssueId();
		// TO DO: $issue =& $cache->get('issues', $issueId);
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issue = $issueDao->getById($issueId, $context->getId());
		// if ($issue) $cache->add($issue, $nullVar);
		$journalIssueNode = parent::createJournalIssueNode($doc, $issue);
		return $journalIssueNode;
	}

	function createJournalArticleNode($doc, $submission) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::getRequest();
		// Issue shoulld be set by now
		$issue = $deployment->getIssue();

		$journalArticleNode = $doc->createElementNS($deployment->getNamespace(), 'journal_article');
		$journalArticleNode->setAttribute('publication_type', 'full_text');
		$journalArticleNode->setAttribute('metadata_distribution_opts', 'any');
		// title
		$titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
		$titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', $submission->getTitle($submission->getLocale())));
		$journalArticleNode->appendChild($titlesNode);
		// contributors
		$contributorsNode = $doc->createElementNS($deployment->getNamespace(), 'contributors');
		$authors = $submission->getAuthors();
		$isFirst = true;
		foreach ($authors as $author) {
			$personNameNode = $doc->createElementNS($deployment->getNamespace(), 'person_name');
			$personNameNode->setAttribute('contributor_role', 'author');
			if ($isFirst) {
				$personNameNode->setAttribute('sequence', 'first');
			} else {
				$personNameNode->setAttribute('sequence', 'additional');
			}
			$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'given_name', ucfirst($author->getFirstName()).(($author->getMiddleName())?' '.ucfirst($author->getMiddleName()):'')));
			$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'surname', ucfirst($author->getLastName())));
			if ($author->getData('orcid')) {
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ORCID', $author->getData('orcid')));
			}
			$contributorsNode->appendChild($personNameNode);
		}
		$journalArticleNode->appendChild($contributorsNode);
		// abstract
		if ($submission->getAbstract($context->getPrimaryLocale())) {
			$abstractNode = $doc->createElementNS($deployment->getJATSNamespace(), 'jats:abstract');
			$abstractNode->appendChild($node = $doc->createElementNS($deployment->getJATSNamespace(), 'jats:p', html_entity_decode(strip_tags($submission->getAbstract($context->getPrimaryLocale())), ENT_COMPAT, 'UTF-8')));
			$journalArticleNode->appendChild($abstractNode);
		}
		// publication date
		$datePublished = $submission->getDatePublished() ? $submission->getDatePublished() : $issue->getDatePublished();
		if ($datePublished) {
			$journalArticleNode->appendChild($this->createPublicationDateNode($doc, $submission->getDatePublished()));
		}
		// pages
		if ($submission->getPages() != '') {
			$pagesNode = $doc->createElementNS($deployment->getNamespace(), 'pages');
			// extract the first page for the first_page element, store the remaining bits in otherPages,
			// after removing any preceding non-numerical characters.
			if (preg_match('/^[^\d]*(\d+)\D*(.*)$/', $submission->getPages(), $matches)) {
				$firstPage = $matches[1];
				$otherPages = $matches[2];
				$pagesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'first_page', $firstPage));
				if ($otherPages != '') {
					$pagesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'other_pages', $otherPages));
				}
			}
			$journalArticleNode->appendChild($pagesNode);
		}
		// license
		if ($submission->getLicenseUrl()) {
			$licenseNode = $doc->createElementNS($deployment->getAINamespace(), 'ai:program');
			$licenseNode->setAttribute('name', 'AccessIndicators');
			$licenseNode->appendChild($node = $doc->createElementNS($deployment->getAINamespace(), 'ai:license_ref', $submission->getLicenseUrl()));
			$journalArticleNode->appendChild($licenseNode);
		}

		// DOI data
		$doiDataNode = $this->createDOIDataNode($doc, $submission->getStoredPubId('doi'), $request->url($context->getPath(), 'article', 'view', $submission->getBestArticleId()));
		// append galleys files and collection nodes to the DOI data node
		// galley can contain several files
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleys = $articleGalleyDao->getBySubmissionId($submission->getId());
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_... constants
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		// supplementary files
		$supplementaryFiles = array();
		while ($galley = $galleys->next()) {
			$gallayFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_REPRESENTATION, $galley->getId(), $submission->getId(), SUBMISSION_FILE_PROOF);
			// filter supp files
			$suppGalleyFiles = array_filter($gallayFiles, create_function('$a', 'return get_class($a) == \'SupplementoryFile\';'));
			array_push($supplementaryFiles, $suppGalleyFiles);
			// filter submission files
			$submissionGalleyFiles = array_filter($gallayFiles, create_function('$a', 'return get_class($a) == \'SubmissionFile\';'));
			if (!empty($submissionGalleyFiles)) {
				$this->appendCollectionNodes($doc, $doiDataNode, $galley, $submissionGalleyFiles);
			}
		}
		$journalArticleNode->appendChild($doiDataNode);

		/* Component list (supplementary files) ??? */
		$componentFiles = array_filter($supplementaryFiles, create_function('$a', 'return $a->getStoredPubId(\'doi\');'));
		if (!empty($componentFiles)) {
			$journalArticleNode->appendChild($this->createComponentListNode($doc, $componentFiles));
		}

		return $journalArticleNode;
	}

	function appendCollectionNodes($doc, $doiDataNode, $galley, $submissionFiles) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::getRequest();

		// start of the text-mining collection element
		$textMiningCollectionNode = $doc->createElementNS($deployment->getNamespace(), 'collection');
		$textMiningCollectionNode->setAttribute('property', 'text-mining');
		foreach ($submissionFiles as $submissionFile) {
			$resourceURL = $request->url($context->getPath(), 'article', 'download', array($submission->getBestArticleId(), $galley->getBestGalleyId(), $submissionFile->getFileId()));
			// iParadigms crawler based collection element
			$crawlerBasedCollectionNode = $doc->createElementNS($deployment->getNamespace(), 'collection');
			$crawlerBasedCollectionNode->setAttribute('property', 'crawler-based');
			$iParadigmsItemNode = $doc->createElementNS($deployment->getNamespace(), 'item');
			$iParadigmsItemNode->setAttribute('crawler', 'iParadigms');
			$iParadigmsItemNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'resource', $resourceURL));
			$crawlerBasedCollectionNode->appendChild($iParadigmsItemNode);
			$doiDataNode->appendChild($crawlerBasedCollectionNode);
			// end iParadigms
			// text-mining collection item
			$textMiningItemNode = $doc->createElementNS($deployment->getNamespace(), 'item');
			$resourceNode = $doc->createElementNS($deployment->getNamespace(), 'resource', $resourceURL);
			// not neccessary to consider remote galleys because we are just interested in files?
			//$remoteGalleyURL = $galley->getRemoteURL();
			//if (!$remoteGalleyURL) {
				$resourceNode->setAttribute('mime_type', $gallayFile->getFileType());
			//}
			$textMiningItemNode->appendChild($resourceNode);
			$textMiningCollectionNode->appendChild($textMiningItemNode);
		}
		$doiDataNode->appendChild($textMiningCollectionNode);
	}


	function createComponentListNode($doc, $componentFiles) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::getRequest();

		// Create the base node
		$componentListNode =$doc->createElementNS($deployment->getNamespace(), 'component_list');
		// Run through supp files and add component nodes.
		foreach($componentFiles as $componentFile) {
			$componentNode = $doc->createElementNS($deployment->getNamespace(), 'component');
			$componentNode->setAttribute('parent_relation', 'isPartOf');

			/* Titles */
			$componentFileTitle = $componentFile->getName();
			if (!empty($componentFileTitle)) {
				$titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
				// TO-DO: what language?
				$titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', $componentFileTitle[$submission->getLocale()]));
				$componentNode->appendChild($titlesNode);
			}

			// DOI data node
			// TO-DO: bestIds missing
			$resourceURL = $request->url($context->getPath(), 'article', 'download', array($submission->getBestArticleId(), $galley->getBestGalleyId(), $componentFile->getFileId()));
			$componentNode->appendChild($this->createDOIDataNode($doc, $componentFile->getStoredPubId('doi'), $resourceURL));
		}
		$componentListNode->appendChild($componentNode);
		return $componentListNode;
	}


}

?>
