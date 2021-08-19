<?php

/**
 * @file classes/statistics/UsageStatsTotalTemporaryRecordDAO.inc.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsTotalTemporaryRecordDAO
 * @ingroup statistics
 *
 * @brief Operations for retrieving and adding total temporary usage statistics records.
 */

namespace APP\statistics;

use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\config\Config;
use PKP\db\DAORegistry;

class UsageStatsTotalTemporaryRecordDAO
{
    /** @var string The name of the table */
    public $table = 'usage_stats_total_temporary_records';


    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Add the passed usage statistic record.
     *
     * @param \stdClass $entryData [
     * 	issue_id
     *  time
     *  ip
     *  canonicalURL
     *  contextId
     *  submissionId
     *  representationId
     *  assocType
     *  assocId
     *  fileType
     *  userAgent
     *  country
     *  region
     *  city
     *  instituionIds
     * ]
     */
    public function insert(\stdClass $entryData, int $lineNumber, string $loadId)
    {
        DB::table($this->table)->insert([
            'date' => $entryData->time,
            'ip' => $entryData->ip,
            'user_agent' => substr($entryData->userAgent, 0, 255),
            'line_number' => $lineNumber,
            'canonical_url' => $entryData->canonicalUrl,
            'issue_id' => property_exists($entryData, 'issueId') ? $entryData->issueId : null,
            'context_id' => $entryData->contextId,
            'submission_id' => $entryData->submissionId,
            'representation_id' => $entryData->representationId,
            'assoc_type' => $entryData->assocType,
            'assoc_id' => $entryData->assocId,
            'file_type' => $entryData->fileType,
            'country' => !empty($entryData->country) ? $entryData->country : '',
            'region' => !empty($entryData->region) ? $entryData->region : '',
            'city' => !empty($entryData->city) ? $entryData->city : '',
            'institution_ids' => json_encode($entryData->institutionIds), // TO-DO: remove
            'load_id' => $loadId,
        ]);
    }

    public function checkForeignKeys(\stdClass $entryData): array
    {
        $errorMsg = [];
        $contextDao = Application::getContextDAO();
        $representationDao = \APP\core\Application::getRepresentationDAO();
        if (DB::table($contextDao->tableName)->where($contextDao->primaryKeyColumn, '=', $entryData->contextId)->doesntExist()) {
            $errorMsg[] = "{$contextDao->primaryKeyColumn}: {$entryData->contextId}";
        }
        if (!empty($entryData->issueId) && DB::table('issues')->where('issue_id', '=', $entryData->issueId)->doesntExist()) {
            $errorMsg[] = "issue_id: {$entryData->issueId}";
        }
        if (!empty($entryData->submissionId) && DB::table('submissions')->where('submission_id', '=', $entryData->submissionId)->doesntExist()) {
            $errorMsg[] = "submission_id: {$entryData->submissionId}";
        }
        if (!empty($entryData->representationId) && DB::table($representationDao->tableName)->where($representationDao->primaryKeyColumn, '=', $entryData->representationId)->doesntExist()) {
            $errorMsg[] = "{$representationDao->primaryKeyColumn}: {$entryData->representationId}";
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
     * Delete all temporary records associated
     * with the passed load id.
     */
    public function deleteByLoadId(string $loadId)
    {
        DB::table($this->table)->where('load_id', '=', $loadId)->delete();
    }

    /**
     * Remove Double Clicks
     * See https://www.projectcounter.org/code-of-practice-five-sections/7-processing-rules-underlying-counter-reporting-data/#doubleclick
     */
    public function removeDoubleClicks(int $counterDoubleClickTimeFilter)
    {
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            DB::statement("DELETE FROM {$this->table} ust WHERE EXISTS (SELECT * FROM (SELECT 1 FROM {$this->table} ustt WHERE ustt.load_id = ust.load_id AND ustt.ip = ust.ip AND ustt.user_agent = ust.user_agent AND ustt.canonical_url = ust.canonical_url AND EXTRACT(EPOCH FROM (ustt.date - ust.date)) < ? AND EXTRACT(EPOCH FROM (ustt.date - ust.date)) > 0 AND ust.line_number < ustt.line_number) AS tmp)", [$counterDoubleClickTimeFilter]);
        } else {
            DB::statement("DELETE FROM {$this->table} ust WHERE EXISTS (SELECT * FROM (SELECT 1 FROM {$this->table} ustt WHERE ustt.load_id = ust.load_id AND ustt.ip = ust.ip AND ustt.user_agent = ust.user_agent AND ustt.canonical_url = ust.canonical_url AND TIMESTAMPDIFF(SECOND, ust.date, ustt.date) < ? AND TIMESTAMPDIFF(SECOND, ust.date, ustt.date) > 0 AND ust.line_number < ustt.line_number) AS tmp)", [$counterDoubleClickTimeFilter]);
        }
    }

    public function loadMetricsContext(string $loadId)
    {
        DB::table('metrics_context')->where('load_id', '=', $loadId)->delete();
        DB::statement(
            "
            INSERT INTO metrics_context (load_id, context_id, date, metric)
                SELECT load_id, context_id, DATE(date) as date, count(*) as metric
                FROM {$this->table}
                WHERE load_id = ? AND assoc_type = ?
                GROUP BY load_id, context_id, DATE(date)
            ",
            [$loadId, Application::getContextAssocType()]
        );
    }

    public function loadMetricsIssue(string $loadId)
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

    public function loadMetricsSubmission(string $loadId)
    {
        DB::table('metrics_submission')->where('load_id', '=', $loadId)->delete();
        DB::statement(
            '
            INSERT INTO metrics_submission (load_id, context_id, submission_id, assoc_type, date, metric)
                SELECT load_id, context_id, submission_id, ' . Application::ASSOC_TYPE_SUBMISSION . ", DATE(date) as date, count(*) as metric
                FROM {$this->table}
                WHERE load_id = ? AND assoc_type = ?
                GROUP BY load_id, context_id, submission_id, DATE(date)
            ",
            [$loadId, Application::ASSOC_TYPE_SUBMISSION]
        );
        DB::statement(
            '
            INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, submission_file_id, file_type, assoc_type, date, metric)
                SELECT load_id, context_id, submission_id, representation_id, assoc_id, file_type, ' . Application::ASSOC_TYPE_SUBMISSION_FILE . ", DATE(date) as date, count(*) as metric
                FROM {$this->table}
                WHERE load_id = ? AND assoc_type = ?
                GROUP BY load_id, context_id, submission_id, representation_id, assoc_id, file_type, DATE(date)
            ",
            [$loadId, Application::ASSOC_TYPE_SUBMISSION_FILE]
        );
        DB::statement(
            '
            INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, submission_file_id, file_type, assoc_type, date, metric)
                SELECT load_id, context_id, submission_id, representation_id, assoc_id, file_type, ' . Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER . ", DATE(date) as date, count(*) as metric
                FROM {$this->table}
                WHERE load_id = ? AND assoc_type = ?
                GROUP BY load_id, context_id, submission_id, representation_id, assoc_id, file_type, DATE(date)
            ",
            [$loadId, Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER]
        );
    }

    public function deleteCounterSubmissionDailyByLoadId(string $loadId)
    {
        DB::table('metrics_counter_submission_daily')->where('load_id', '=', $loadId)->delete();
    }

    public function loadMetricsCounterSubmissionDaily(string $loadId)
    {
        // construct metric_investigations upsert
        $metricInvestigationsUpsertSql = "
            INSERT INTO metrics_counter_submission_daily (load_id, context_id, submission_id, date, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
            SELECT * FROM (SELECT load_id, context_id, submission_id, DATE(date) as date, count(*) as metric, 0 as metric_investigations_unique, 0 as metric_requests, 0 as metric_requests_unique
                FROM {$this->table}
                WHERE load_id = ? AND submission_id IS NOT NULL
                GROUP BY load_id, context_id, submission_id, DATE(date)) AS t
            ";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $metricInvestigationsUpsertSql .= '
                ON CONFLICT ON CONSTRAINT metrics_submission_daily_uc_load_id_context_id_submission_id_date DO UPDATE
                SET metric_investigations = excluded.metric_investigations;
                ';
        } else {
            $metricInvestigationsUpsertSql .= '
                ON DUPLICATE KEY UPDATE metric_investigations = metric;
            ';
        }
        // load metric_investigations
        DB::statement($metricInvestigationsUpsertSql, [$loadId]);

        // construct metric_requests upsert
        $metricRequestsUpsertSql = "
            INSERT INTO metrics_counter_submission_daily (load_id, context_id, submission_id, date, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
            SELECT * FROM (SELECT load_id, context_id, submission_id, DATE(date) as date, 0 as metric_investigations, 0 as metric_investigations_unique, count(*) as metric, 0 as metric_requests_unique
                FROM {$this->table}
                WHERE load_id = ? AND assoc_type = ?
                GROUP BY load_id, context_id, submission_id, DATE(date)) AS t
            ";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $metricRequestsUpsertSql .= '
                ON CONFLICT ON CONSTRAINT metrics_submission_daily_uc_load_id_context_id_submission_id_date DO UPDATE
                SET metric_requests = excluded.metric_requests;
                ';
        } else {
            $metricRequestsUpsertSql .= '
                ON DUPLICATE KEY UPDATE metric_requests = metric;
            ';
        }
        // load metric_requests
        DB::statement($metricRequestsUpsertSql, [$loadId, Application::ASSOC_TYPE_SUBMISSION_FILE]);
    }

    public function deleteCounterSubmissionGeoDailyByLoadId(string $loadId)
    {
        DB::table('metrics_counter_submission_geo_daily')->where('load_id', '=', $loadId)->delete();
    }

    public function loadMetricsCounterSubmissionGeoDaily(string $loadId)
    {
        // construct metric_investigations upsert
        $metricInvestigationsUpsertSql = "
            INSERT INTO metrics_counter_submission_geo_daily (load_id, context_id, submission_id, date, country, region, city, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
            SELECT * FROM (SELECT load_id, context_id, submission_id, DATE(date) as date, country, region, city, count(*) as metric, 0 as metric_investigations_unique, 0 as metric_requests, 0 as metric_requests_unique
                FROM {$this->table}
                WHERE load_id = ? AND submission_id IS NOT NULL
                GROUP BY load_id, context_id, submission_id, DATE(date), country, region, city) AS t
            ";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $metricInvestigationsUpsertSql .= '
                ON CONFLICT ON CONSTRAINT metrics_geo_daily_uc_load_id_context_id_submission_id_country_region_city_date DO UPDATE
                SET metric_investigations = excluded.metric_investigations;
                ';
        } else {
            $metricInvestigationsUpsertSql .= '
                ON DUPLICATE KEY UPDATE metric_investigations = metric;
                ';
        }
        // load metric_investigations
        DB::statement($metricInvestigationsUpsertSql, [$loadId]);

        // construct metric_requests upsert
        $metricRequestsUpsertSql = "
            INSERT INTO metrics_counter_submission_geo_daily (load_id, context_id, submission_id, date, country, region, city, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
            SELECT * FROM (SELECT load_id, context_id, submission_id, DATE(date) as date, country, region, city, 0 as metric_investigations, 0 as metric_investigations_unique, count(*) as metric, 0 as metric_requests_unique
                FROM {$this->table}
                WHERE load_id = ? AND assoc_type = ?
                GROUP BY load_id, context_id, submission_id, DATE(date), country, region, city) AS t
            ";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $metricRequestsUpsertSql .= '
                ON CONFLICT ON CONSTRAINT metrics_geo_daily_uc_load_id_context_id_submission_id_country_region_city_date DO UPDATE
                SET metric_requests = excluded.metric_requests;
                ';
        } else {
            $metricRequestsUpsertSql .= '
                ON DUPLICATE KEY UPDATE metric_requests = metric;
            ';
        }
        // load metric_requests
        DB::statement($metricRequestsUpsertSql, [$loadId, Application::ASSOC_TYPE_SUBMISSION_FILE]);
    }

    public function deleteCounterSubmissionInstitutionDailyByLoadId(string $loadId)
    {
        DB::table('metrics_counter_submission_institution_daily')->where('load_id', '=', $loadId)->delete();
    }

    public function loadMetricsCounterSubmissionInstitutionDaily(string $loadId)
    {
        // construct metric_investigations upsert
        $metricInvestigationsUpsertSql = "
            INSERT INTO metrics_counter_submission_institution_daily (load_id, context_id, submission_id, date, institution_id, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
            SELECT * FROM (
                SELECT ustt.load_id, ustt.context_id, ustt.submission_id, DATE(ustt.date) as date, usit.institution_id, count(*) as metric, 0 as metric_investigations_unique, 0 as metric_requests, 0 as metric_requests_unique
                FROM {$this->table} ustt
                JOIN usage_stats_institution_temporary_records usit on (usit.load_id = ustt.load_id AND usit.line_number = ustt.line_number)
                WHERE ustt.load_id = ? AND submission_id IS NOT NULL AND usit.institution_id = ?
                GROUP BY ustt.load_id, ustt.context_id, ustt.submission_id, DATE(ustt.date), usit.institution_id) AS t
            ";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $metricInvestigationsUpsertSql .= '
                ON CONFLICT ON CONSTRAINT metrics_institution_daily_uc_load_id_context_id_submission_id_institution_id_date DO UPDATE
                SET metric_investigations = excluded.metric_investigations;
                ';
        } else {
            $metricInvestigationsUpsertSql .= '
                ON DUPLICATE KEY UPDATE metric_investigations = metric;
                ';
        }

        // construct metric_requests upsert
        $metricRequestsUpsertSql = "
            INSERT INTO metrics_counter_submission_institution_daily (load_id, context_id, submission_id, date, institution_id, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
            SELECT * FROM (
                SELECT ustt.load_id, ustt.context_id, ustt.submission_id, DATE(ustt.date) as date, usit.institution_id, 0 as metric_investigations, 0 as metric_investigations_unique, count(*) as metric, 0 as metric_requests_unique
                FROM {$this->table} ustt
                JOIN usage_stats_institution_temporary_records usit on (usit.load_id = ustt.load_id AND usit.line_number = ustt.line_number)
                WHERE ustt.load_id = ? AND ustt.assoc_type = ? AND usit.institution_id = ?
                GROUP BY ustt.load_id, ustt.context_id, ustt.submission_id, DATE(ustt.date), usit.institution_id) AS t
            ";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $metricRequestsUpsertSql .= '
                ON CONFLICT ON CONSTRAINT metrics_institution_daily_uc_load_id_context_id_submission_id_institution_id_date DO UPDATE
                SET metric_requests = excluded.metric_requests;
                ';
        } else {
            $metricRequestsUpsertSql .= '
                ON DUPLICATE KEY UPDATE metric_requests = metric;
            ';
        }

        $statsInstitutionDao = DAORegistry::getDAO('UsageStatsInstitutionTemporaryRecordDAO'); /* @var $statsInstitutionDao UsageStatsInstitutionTemporaryRecordDAO */
        $institutionIds = $statsInstitutionDao->getInstitutionIdsByLoadId($loadId);
        foreach ($institutionIds as $institutionId) {
            // load metric_investigations
            DB::statement($metricInvestigationsUpsertSql, [$loadId, (int) $institutionId]);
            // load metric_requests
            DB::statement($metricRequestsUpsertSql, [$loadId, Application::ASSOC_TYPE_SUBMISSION_FILE, (int) $institutionId]);
        }
    }
}
