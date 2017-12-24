<?php

/**
 * @file tools/deleteMissingSubmissions.php
 *
 * @class deleteMissingSubmissions
 * @ingroup tools
 *
 * @brief CLI tool to delete submissions
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

class MissingSubmissionDeletionTool extends CommandLineTool {


	/**
	 * Delete missing submission data
	 */
	function execute() {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$authorDao = DAORegistry::getDAO('AuthorDAO');
		require_once('plugins/generic/referral/ReferralDAO.inc.php');
		$referralDao = new ReferralDAO();

		// remove article xml galleys, article comments, comments, metrics with misisng submissions
		$reviewAssignmentDao->update('DELETE FROM article_xml_galleys WHERE article_id NOT IN (SELECT article_id FROM articles)');
		$reviewAssignmentDao->update('DELETE FROM article_comments WHERE article_id NOT IN (SELECT article_id FROM articles)');
		$reviewAssignmentDao->update('DELETE FROM comments WHERE submission_id NOT IN (SELECT article_id FROM articles)');
		$reviewAssignmentDao->update('DELETE FROM metrics WHERE submission_id NOT IN (SELECT article_id FROM articles)');
		$reviewAssignmentDao->update('DELETE FROM review_rounds WHERE submission_id NOT IN (SELECT article_id FROM articles)');

		// remove authors with misisng submissions
		$missingAuthorsSubmissionIds = $authorDao->retrieve('SELECT submission_id FROM authors WHERE submission_id NOT IN (SELECT article_id FROM articles)');
		while (!$missingAuthorsSubmissionIds->EOF) {
			$row = $missingAuthorsSubmissionIds->GetRowAssoc(false);
			$articleId = (int)$row['submission_id'];
			$authorDao->deleteAuthorsByArticle($articleId);
			$missingAuthorsSubmissionIds->MoveNext();
		}
		$missingAuthorsSubmissionIds->Close();

		// remove referrals with misisng submissions
		$missingReferralsSubmissionIds = $referralDao->retrieve('SELECT referral_id FROM referrals WHERE article_id NOT IN (SELECT article_id FROM articles)');
		while (!$missingReferralsSubmissionIds->EOF) {
			$row = $missingReferralsSubmissionIds->GetRowAssoc(false);
			$referralId = (int)$row['referral_id'];
			$referralDao->deleteReferralById($referralId);
			$missingReferralsSubmissionIds->MoveNext();
		}
		$missingReferralsSubmissionIds->Close();

		// remove reivew assignments with misisng submissions
		$missingReviewAssignmentsSubmissionIds = $reviewAssignmentDao->retrieve('SELECT submission_id FROM review_assignments WHERE submission_id NOT IN (SELECT article_id FROM articles)');
		while (!$missingReviewAssignmentsSubmissionIds->EOF) {
			$row = $missingReviewAssignmentsSubmissionIds->GetRowAssoc(false);
			$articleId = (int)$row['submission_id'];
			$reviewAssignmentDao->deleteBySubmissionId($articleId);
			$missingReviewAssignmentsSubmissionIds->MoveNext();
		}
		$missingReviewAssignmentsSubmissionIds->Close();

	}
}

$tool = new MissingSubmissionDeletionTool();
$tool->execute();
?>
