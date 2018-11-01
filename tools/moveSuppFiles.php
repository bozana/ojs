<?php

/**
 * @file tools/moveSuppFiles.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MoveSuppFilesTool
 * @ingroup tools
 *
 * @brief CLI tool to delete submissions
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_...

class MoveSuppFilesTool extends CommandLineTool {

	var $contextId;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		if (!isset($this->argv[0])) {
			$this->usage();
			exit(1);
		}

		$this->contextId = $this->argv[0];
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Moves all galleys supplementary files to the submisison files grid. USE WITH CARE.\n"
			. "Usage: {$this->scriptName} journal_id \n";
	}

	/**
	 * Move supp files to the submission stage i.e. submission files grid
	 */
	function execute() {
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$suppFilesResult = $submissionFileDao->retrieve('SELECT sf.* FROM submission_files sf, genres g, submission_supplementary_files ssf, submissions s WHERE sf.file_id = ssf.file_id AND sf.revision = ssf.revision AND sf.file_stage = ? AND sf.assoc_type = ? AND sf.genre_id = g.genre_id AND g.supplementary = 1 AND s.submission_id = sf.submission_id AND s.context_id = ?', array(SUBMISSION_FILE_PROOF, ASSOC_TYPE_REPRESENTATION, (int)$this->contextId));
		while (!$suppFilesResult->EOF) {
			$row = $suppFilesResult->getRowAssoc(false);
			$suppFile = $submissionFileDao->getRevision($row['file_id'], $row['revision'], SUBMISSION_FILE_PROOF, $row['submission_id']);
			if ($suppFile) {
				$submissionFileManager = new SubmissionFileManager($this->contextId, $row['submission_id']);
				// get old supp file path
				$oldSuppFilePath = $suppFile->getFilePath();
				// get galley ID
				$galleyId = $suppFile->getAssocId();
				// get new supp file path
				$suppFile->setFileStage(SUBMISSION_FILE_SUBMISSION);
				$suppFile->setViewable(true);
				$suppFile->setAssocId(null);
				$suppFile->setAssocType(null);
				$newSuppFilePath = $suppFile->getFilePath();

				// rename old to the new file path
				$error = false;
				if (!file_exists($newSuppFilePath)) {
					if (!file_exists($path = dirname($newSuppFilePath)) && !$submissionFileManager->mkdirtree($path)) {
						error_log("ERROR: Unable to make directory \"$path\"");
						$error = true;
					}
					if (!rename($oldSuppFilePath, $newSuppFilePath)) {
						error_log("ERROR: Unable to move \"$oldSuppFilePath\" to \"$newSuppFilePath\".");
						$error = true;
					}
				} else {
					error_log("ERROR: \"$newSuppFilePath\" already exists.");
					$error = true;
				}

				if (!$error) {
					// update DB table submission_files
					$submissionFileDao->update('UPDATE submission_files SET file_stage = ?, viewable = ?, assoc_type = ?, assoc_id = ? WHERE file_id = ? AND revision = ?', array(SUBMISSION_FILE_SUBMISSION, true, NULL, NULL, (int) $row['file_id'], (int) $row['revision']));
					// remove galley
					$submissionFileDao->update('DELETE FROM submission_galleys WHERE galley_id = ?', array((int)$galleyId));
					$submissionFileDao->update('DELETE FROM submission_galley_settings WHERE galley_id = ?', array((int) $galleyId));
				}
			}
			$suppFilesResult->MoveNext();
		}
		$suppFilesResult->Close();
	}
}

$tool = new MoveSuppFilesTool(isset($argv) ? $argv : array());
$tool->execute();
?>
