<?php

/**
 * @file tools/copySuppFiles.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class copySuppFiles
 * @ingroup tools
 *
 * @brief CLI tool to delete submissions
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_...

class CopySuppFilesTool extends CommandLineTool {

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
		echo "Copies all journal supplementary files to the submisison files grid. USE WITH CARE.\n"
			. "Usage: {$this->scriptName} journal_id \n";
	}

	/**
	 * Copy supp files to the submission stage i.e. submission files grid
	 */
	function execute() {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$suppFilesResult = $submissionFileDao->retrieve('SELECT sf.file_id, sf.revision, sf.submission_id FROM submission_supplementary_files ssf, submission_files sf, submissions s WHERE sf.file_id = ssf.file_id AND sf.revision = ssf.revision AND sf.file_stage = ? AND s.submission_id = sf.submission_id AND s.context_id = ?', array(SUBMISSION_FILE_PROOF, (int)$this->contextId));
		while (!$suppFilesResult->EOF) {
			$row = $suppFilesResult->getRowAssoc(false);
			$suppFile = $submissionFileDao->getRevision($row['file_id'], $row['revision'], SUBMISSION_FILE_PROOF, $row['submission_id']);
			if ($suppFile) {
				$suppFilePath = $suppFile->getFilePath();

				$suppFile->setFileid(null);
				$suppFile->setFileStage(SUBMISSION_FILE_SUBMISSION);
				$suppFile->setViewable(true);
				$suppFile->setAssocId(null);
				$suppFile->setAssocType(null);
				$submissionFileDao->insertObject($suppFile, $suppFilePath);
			}
			$suppFilesResult->MoveNext();
		}
		$suppFilesResult->Close();
	}
}

$tool = new CopySuppFilesTool(isset($argv) ? $argv : array());
$tool->execute();
?>
