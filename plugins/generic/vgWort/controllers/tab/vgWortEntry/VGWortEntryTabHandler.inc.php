<?php

/**
 * @file plugins/generic/vgWort/controllers/tab/vgWortEntry/VGWortEntryTabHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VGWortEntryTabHandler
 * @ingroup controllers_tab_catalogEntry
 *
 * @brief Handle AJAX operations for tabs on the submission issue management page.
 */

// Import the base Handler.
import('classes.handler.Handler');

import('plugins.generic.vgWort.classes.PixelTag');

class VGWortEntryTabHandler extends Handler {

	/**
	 * Constructor
	 */
	function VGWortEntryTabHandler() {
		parent::Handler();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array(
				'assignPixelTag'
			)
		);
	}


	//
	// Public handler methods
	//

	/**
	 * Assign a pixel tag to an article or
	 * update the pixel tag assignment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function assignPixelTag($args, $request) {
		$submissionId = $request->getUserVar('submissionId');
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($submissionId);

		$router = $request->getRouter();
		$journal = $router->getContext($request);

		$vgWortPlugin = PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$templateMgr = TemplateManager::getManager($request);

		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$pixelTag = $pixelTagDao->getPixelTagByArticleId($journal->getId(), $submissionId);

		$function = $request->getUserVar('function');
		if ($function == 'assign' && isset($submission)) {
			// check if there is a card number
			$cardNoExists = false;
			foreach ($submission->getAuthors() as $author) {
				$cardNo = $author->getData('cardNo');
				if (!empty($cardNo)) {
					$cardNoExists = true;
					break;
				}
			}
			if (!$cardNoExists) {
				$templateMgr->assign('errorCode', 1);
			} else {
				// assign
				$vgWortPlugin->import('classes.VGWortEditorAction');
				$vgWortEditorAction = new VGWortEditorAction();
				$vgWortTextType = (int) $request->getUserVar('vgWortTextType');
				$assigned = $vgWortEditorAction->assignPixelTag($journal, $submissionId, $vgWortTextType);
				if (!$assigned) {
					$templateMgr->assign('errorCode', 2);
				}
			}
		}
		if ($function == 'update' && isset($submission)) {
			$updatePixelTag = false;
			$removePixelTag = $request->getUserVar('removePixelTag');
			if ($removePixelTag) {
				if ($pixelTag && $pixelTag->getStatus() != PT_STATUS_AVAILABLE && !$pixelTag->getDateRemoved()) {
					$pixelTag->setDateRemoved(Core::getCurrentDate());
					$updatePixelTag = true;
				}
			} else {
				if ($pixelTag && $pixelTag->getDateRemoved()) {
					$pixelTag->setDateRemoved(NULL);
					$updatePixelTag = true;
				}
			}
			if($pixelTag && $pixelTag->getStatus() != PT_STATUS_REGISTERED) {
				$vgWortTextTypeNew = $request->getUserVar('vgWortTextType') ? (int) $request->getUserVar('vgWortTextType') : null;
				if (isset($vgWortTextTypeNew) && $vgWortTextTypeNew != $pixelTag->getTextType()) {
						$pixelTag->setTextType($vgWortTextTypeNew);
						$updatePixelTag = true;
				}
			}
			if ($updatePixelTag) $pixelTagDao->updateObject($pixelTag);
		}

		$vgWortTextType = !isset($pixelTag) ? 0 : $pixelTag->getTextType();

		$templateMgr->assign('submissionId', $submissionId);
		$templateMgr->assign('pixelTag', $pixelTag);
		$templateMgr->assign('vgWortTextType', $vgWortTextType);
		$templateMgr->assign('typeOptions', PixelTag::getTextTypeOptions());
//		return $templateMgr->fetchJson($vgWortPlugin->getTemplatePath() . 'assignPixelTag.tpl');
		$returner = $templateMgr->display($vgWortPlugin->getTemplatePath() . 'assignPixelTag.tpl', null, null, false);
		$json = new JSONMessage(true, $returner);
		return $json->getString();

	}

	/**
	 * Display about index page.
	 * @param $request PKPRequest
	 * @param $args array
	 */
/*
	function saveFormBB($args, $request) {
		$json = new JSONMessage();

		$router = $request->getRouter();
		$dispatcher = $router->getDispatcher();
		$url = $dispatcher->url($request, ROUTE_COMPONENT, null, null, 'assignPixelTag');
		$json->setAdditionalAttributes(array('reloadContainer' => true, 'tabsUrl' => $url));
		$json->setContent(true); // prevents modal closure
		return $json->getString();

	}
*/
}

?>
