<?php

/**
 * @file classes/statistics/TemporaryTotalsDAO.inc.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TemporaryTotalsDAO
 * @ingroup statistics
 *
 * @brief Operations for retrieving and adding total usage.
 *
 * It considers:
 * issue toc and galley views.
 */

namespace APP\statistics;

use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\statistics\PKPTemporaryTotalsDAO;

class TemporaryTotalsDAO extends PKPTemporaryTotalsDAO
{
    public function checkForeignKeys(object $entryData): array
    {
        $errorMsg = [];
        if (DB::table('journals')->where('journal_id', '=', $entryData->contextId)->doesntExist()) {
            $errorMsg[] = "journal_id: {$entryData->contextId}";
        }
        if (!empty($entryData->issueId) && DB::table('issues')->where('issue_id', '=', $entryData->issueId)->doesntExist()) {
            $errorMsg[] = "issue_id: {$entryData->issueId}";
        }
        if (!empty($entryData->submissionId) && DB::table('submissions')->where('submission_id', '=', $entryData->submissionId)->doesntExist()) {
            $errorMsg[] = "submission_id: {$entryData->submissionId}";
        }
        if (!empty($entryData->representationId) && DB::table('publication_galleys')->where('galley_id', '=', $entryData->representationId)->doesntExist()) {
            $errorMsg[] = "galley_id: {$entryData->representationId}";
        }
        if (in_array($entryData->assocType, [Application::ASSOC_TYPE_SUBMISSION_FILE, Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER]) &&
            DB::table('submission_files')->where('submission_file_id', '=', $entryData->assocId)->doesntExist()) {
            $errorMsg[] = "submission_file_id: {$entryData->assocId}";
        }
        if (($entryData->assocType == Application::ASSOC_TYPE_ISSUE_GALLEY) &&
            DB::table('issue_galleys')->where('galley_id', '=', $entryData->assocId)->doesntExist()) {
            $errorMsg[] = "issue_galley_id: {$entryData->assocId}";
        }
        foreach ($entryData->institutionIds as $institutionId) {
            if (DB::table('institutions')->where('institution_id', '=', $institutionId)->doesntExist()) {
                $errorMsg[] = "institution_id: {$institutionId}";
            }
        }
        return $errorMsg;
    }

    /**
     * Load usage for issue (TOC and galleys views)
     */
    public function loadMetricsIssue(string $loadId): void
    {
        DB::table('metrics_issue')->where('load_id', '=', $loadId)->delete();
        DB::statement(
            "
            INSERT INTO metrics_issue (load_id, context_id, issue_id, date, metric)
                SELECT load_id, context_id, issue_id, DATE(date) as date, count(*) as metric
                FROM {$this->table}
                WHERE load_id = ? AND assoc_type = ?
                GROUP BY load_id, context_id, issue_id, DATE(date)
            ",
            [$loadId, Application::ASSOC_TYPE_ISSUE]
        );
        DB::statement(
            "
            INSERT INTO metrics_issue (load_id, context_id, issue_id, issue_galley_id, date, metric)
                SELECT load_id, context_id, issue_id, assoc_id, DATE(date) as date, count(*) as metric
                FROM {$this->table}
                WHERE load_id = ? AND assoc_type = ?
                GROUP BY load_id, context_id, issue_id, assoc_id, DATE(date)
            ",
            [$loadId, Application::ASSOC_TYPE_ISSUE_GALLEY]
        );
    }
}
