<?php

/**
 * @file plugins/generic/usageEvent/UsageEventPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageEventPlugin
 * @ingroup plugins_generic_usageEvent
 *
 * @brief Implement application specifics for generating usage events.
 */

import('lib.pkp.plugins.generic.usageEvent.PKPUsageEventPlugin');

class UsageEventPlugin extends PKPUsageEventPlugin {


	//
	// Implement methods from PKPUsageEventPlugin.
	//
	/**
	 * @copydoc PKPUsageEventPlugin::getEventHooks()
	 */
	function getEventHooks() {
		return array_merge(parent::getEventHooks(), array(
			'ArticleHandler::download',
			'IssueHandler::download',
			'ArticleHandler::viewRemoteGalley',
		));
	}

	/**
	 * @copydoc PKPUsageEventPlugin::getUSageEventData()
	 */
	protected function getUsageEventData($hookName, $hookArgs, $request, $router, $templateMgr, $context) {
		list($pubObject, $downloadSuccess, $assocType, $idParams, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams) =
			parent::getUsageEventData($hookName, $hookArgs, $request, $router, $templateMgr, $context);

		if (!$pubObject) {
			switch ($hookName) {
				// Press index page, issue content page and article abstract.
				case 'TemplateManager::display':
					$page = $router->getRequestedPage($request);
					$op = $router->getRequestedOp($request);
					$args = $router->getRequestedArgs($request);

					$wantedPages = array('issue', 'article');
					$wantedOps = array('index', 'view');

					if (!in_array($page, $wantedPages) || !in_array($op, $wantedOps)) break;

					// View requests with 1 argument might relate to journal
					// or article. With more than 1 is related with other objects
					// that we are not interested in or that are counted using a
					// different hook.
					if ($op == 'view' && count($args) > 1) break;

					$journal = $templateMgr->get_template_vars('currentContext');
					$issue = $templateMgr->get_template_vars('issue');
					$publishedArticle = $templateMgr->get_template_vars('publishedArticle');

					// No published objects, no usage event.
					if (!$journal && !$issue && !$publishedArticle) break;

					if ($journal) {
						$pubObject = $journal;
						$assocType = ASSOC_TYPE_JOURNAL;
						$canonicalUrlOp = '';
					}

					if ($issue) {
						$pubObject = $issue;
						$assocType = ASSOC_TYPE_ISSUE;
						$canonicalUrlParams = array($issue->getId());
						$idParams = array('s' . $issue->getId());
					}

					if ($publishedArticle) {
						$pubObject = $publishedArticle;
						$assocType = ASSOC_TYPE_ARTICLE;
						$canonicalUrlParams = array($pubObject->getId());
						$idParams = array('m' . $pubObject->getId());
					}

					$downloadSuccess = true;
					$canonicalUrlOp = $op;
					break;

					// Issue galley.
				case 'IssueHandler::download':
					$assocType = ASSOC_TYPE_ISSUE_GALLEY;
					$issue = $hookArgs[0];
					$galley = $hookArgs[1];
					$canonicalUrlOp = 'download';
					$canonicalUrlParams = array($issue->getId(), $galley->getId());
					$idParams = array('i' . $issue->getId(), 'f' . $galley->getId());
					$downloadSuccess = false;
					$pubObject = $galley;
					break;

					// Article file.
				case 'ArticleHandler::download':
					$assocType = ASSOC_TYPE_SUBMISSION_FILE;
					$article = $hookArgs[0];
					$galley = $hookArgs[1];
					$fileId = $hookArgs[2];
					$canonicalUrlOp = 'download';
					$canonicalUrlParams = array($article->getId(), $galley->getId(), $fileId);
					$idParams = array('a' . $article->getId(), 'g' . $galley->getId(), 'f' . $fileId);
					$downloadSuccess = false;
					$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
					$pubObject = $submissionFileDao->getLatestRevision($fileId);
					break;
				case 'ArticleHandler::viewRemoteGalley':
					$assocType = ASSOC_TYPE_GALLEY;
					$article = $hookArgs[0];
					$galley = $hookArgs[1];
					$canonicalUrlOp = 'view';
					$canonicalUrlParams = array($article->getId(), $galley->getId());
					$idParams = array('a' . $article->getId(), 'g' . $galley->getId());
					$downloadSuccess = true;
					$pubObject = $galley;
					break;
				default:
					// Why are we called from an unknown hook?
					assert(false);
			}
		}

		return array($pubObject, $downloadSuccess, $assocType, $idParams, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams);
	}

	/**
	 * @see PKPUsageEventPlugin::getHtmlPageAssocTypes()
	 */
	protected function getHtmlPageAssocTypes() {
		return array(
			ASSOC_TYPE_JOURNAL,
			ASSOC_TYPE_ISSUE,
			ASSOC_TYPE_ARTICLE,
			ASSOC_TYPE_GALLEY
		);
	}

	/**
	 * @see PKPUsageEventPlugin::isPubIdObjectType()
	 */
	protected function isPubIdObjectType($pubObject) {
		return is_a($pubObject, 'PublishedArticle');
	}

}

?>
