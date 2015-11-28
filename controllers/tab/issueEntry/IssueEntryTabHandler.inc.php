<?php

/**
 * @file controllers/tab/issueEntry/IssueEntryTabHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryTabHandler
 * @ingroup controllers_tab_catalogEntry
 *
 * @brief Handle AJAX operations for tabs on the submission issue management page.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.publicationEntry.PublicationEntryTabHandler');

class IssueEntryTabHandler extends PublicationEntryTabHandler {
	/**
	 * Constructor
	 */
	function IssueEntryTabHandler() {
		parent::PublicationEntryTabHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array(
				'publicationMetadata', 'pubIds', 'clearPubId',
			)
		);
	}


	//
	// Public handler methods
	//

	/**
	 * Show the publication metadata form.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function publicationMetadata($args, $request) {
		import('controllers.tab.issueEntry.form.IssueEntryPublicationMetadataForm');

		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$user = $request->getUser();

		$issueEntryPublicationMetadataForm = new IssueEntryPublicationMetadataForm($submission->getId(), $user->getId(), $stageId, array('displayedInContainer' => true));

		$issueEntryPublicationMetadataForm->initData();
		return new JSONMessage(true, $issueEntryPublicationMetadataForm->fetch($request));
	}

	/**
	 * Show the public identifiers form.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function pubIds($args, $request) {
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$identifiersForm = new PublicIdentifiersForm($submission, $stageId, array('displayedInContainer' => true));
		$identifiersForm->initData();
		return new JSONMessage(true, $identifiersForm->fetch($request));
	}

	/**
	 * Clear article pub id.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function clearPubId($args, $request) {
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$identifiersForm = new PublicIdentifiersForm($submission, $stageId, array('displayedInContainer' => true));
		$identifiersForm->clearPubId($request->getUserVar('actionType'));
		return new JSONMessage(true);
	}

	/**
	 * Get the form for a particular tab.
	 */
	function _getFormFromCurrentTab(&$form, &$notificationKey, $request) {
		parent::_getFormFromCurrentTab($form, $notificationKey, $request); // give PKP-lib a chance to set the form and key.

		if (!$form) { // nothing applicable in parent.
			$submission = $this->getSubmission();
			switch ($this->getCurrentTab()) {
				case 'publication':
					import('controllers.tab.issueEntry.form.IssueEntryPublicationMetadataForm');
					$user = $request->getUser();
					$form = new IssueEntryPublicationMetadataForm($submission->getId(), $user->getId(), $this->getStageId(), array('displayedInContainer' => true, 'tabPos' => $this->getTabPosition()));
					$notificationKey = 'notification.savedIssueMetadata';
					import('lib.pkp.classes.log.SubmissionLog');
					import('classes.log.SubmissionEventLogEntry'); // Log consts
					SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_ISSUE_METADATA_UPDATE, 'submission.event.issueMetadataUpdated');
					break;
				case 'identifiers':
					import('controllers.tab.pubIds.form.PublicIdentifiersForm');
					$form = new PublicIdentifiersForm($submission, $this->getStageId(), array('displayedInContainer' => true, 'tabPos' => $this->getTabPosition()));
					$notificationKey = 'common.changesSaved';
					break;
			}
		}
	}

	/**
	 * Returns an instance of the form used for reviewing a submission's 'submission' metadata.
	 * @copydoc PublicationEntryTabHandler::_getPublicationEntrySubmissionReviewForm()
	 * @return PKPForm
	 */
	function _getPublicationEntrySubmissionReviewForm() {

		$submission = $this->getSubmission();
		import('controllers.modals.submissionMetadata.form.IssueEntrySubmissionReviewForm');
		return new IssueEntrySubmissionReviewForm($submission->getId(), $this->getStageId(), array('displayedInContainer' => true));
	}

	/**
	 * return a string to the Handler for this modal.
	 * @return String
	 */
	function _getHandlerClassPath() {
		return 'modals.submissionMetadata.IssueEntryHandler';
	}
}

?>
