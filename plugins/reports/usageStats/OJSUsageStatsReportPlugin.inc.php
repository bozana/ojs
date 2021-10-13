<?php

/**
 * @file plugins/reports/usageStats/OJSUsageStatsReportPlugin.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJUsageStatsReportPlugin
 * @ingroup plugins_reports_usageStats
 *
 * @brief OJS default statistics report plugin (and metrics provider)
 */

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\statistics\StatisticsHelper;
use PKP\db\DAORegistry;

import('plugins.reports.usageStats.PKPUsageStatsReportPlugin');


class OJSUsageStatsReportPlugin extends PKPUsageStatsReportPlugin
{
    /**
     * Save/cache object information (titles) that could be used
     * in several report rows.
     */
    protected array $issueTitles = [];

    /**
     * @copydoc ReportPlugin::getColumns()
     */
    public function getReportColumns(): array
    {
        return $this->getOrderedReportColumns();
    }

    /**
     * @copydoc PKPUsageStatsReportPlugin::getOrderedReportColumns()
     */
    public function getOrderedReportColumns(): array
    {
        return [
            StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE,
            StatisticsHelper::STATISTICS_DIMENSION_FILE_TYPE,
            StatisticsHelper::STATISTICS_DIMENSION_FILE_ID,
            StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID,
            StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID,
            StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID,
            StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID,
            StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID,
            StatisticsHelper::STATISTICS_DIMENSION_MONTH,
            StatisticsHelper::STATISTICS_METRIC,
        ];
    }

    /**
     * @copydoc PKPUsageStatsReportPlugin::getIDColumns
     */
    public function getIDColumns(): array
    {
        return array_merge(
            parent::getIDColumns(),
            [
                StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID,
                StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID,
            ]
        );
    }

    /**
     * @copydoc PKPUsageStatsReportPlugin::getAssocId()
     */
    public function getAssocId(array $record): array
    {
        $assocId = $assocType = null;
        if (isset($record[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID])) {
            $assocId = $record[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID];
            $assocType = Application::ASSOC_TYPE_ISSUE_GALLEY;
        } elseif (isset($record[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID])) {
            $assocId = $record[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID];
            $assocType = Application::ASSOC_TYPE_ISSUE;
        } else {
            [$assocId, $assocType] = parent::getAssocId($record);
        }
        return [$assocId, $assocType];
    }

    /**
     * @copydoc PKPUsageStatsReportPlugin::getObjectInformation()
     */
    protected function getObjectInformation(int $assocId, int $assocType): array
    {
        $objectInformation = parent::getObjectInformation($assocId, $assocType);
        $objectTitle = $issueId = null;
        switch ($assocType) {
            case Application::ASSOC_TYPE_ISSUE_GALLEY:
                $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
                $issueGalley = $issueGalleyDao->getById($assocId);
                if (!$issueGalley) {
                    break;
                }
                $objectTitle = $issueGalley->getGalleyLabel();
                $assocId = $issueGalley->getIssueId();
                // no break
            case Application::ASSOC_TYPE_ISSUE_GALLEY:
            case Application::ASSOC_TYPE_ISSUE:
                $issue = Repo::issue()->get($assocId);
                if (!$issue) {
                    break;
                }
                $issueId = $issue->getId();
                if (!array_key_exists($issueId, $this->issueTitles)) {
                    $this->issueTitles[$issueId] = $issue->getIssueIdentification();
                }

                if (!$objectTitle) { // it could be issue galley
                    $objectTitle = $issue->getIssueIdentification();
                }
                break;
            default:
                return $objectInformation;
                break;
        }
        if (!$objectTitle) {
            $objectTitle = __('manager.statistics.reports.objectNotFound');
        }
        $objectInformation['title'] = $objectTitle;
        $objectInformation[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID] = $issueId;
        return $objectInformation;
    }

    /**
     * Get additional information (issue ID) for a submission
     * that will be used for the report column StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID
     */
    protected function getAdditionalSubmissionInformation(\APP\submission\Submission $submission): array
    {
        $additionalObjectInformation = [];
        if (is_a($submission, 'Submission')) {
            $issueId = $submission->getCurrentPublication()->getData('issueId');
            if ($issueId && !array_key_exists($issueId, $this->issueTitles)) {
                $issue = Repo::issue()->get($issueId);
                $this->issueTitles[$issueId] = $issue ? $issue->getIssueIdentification() : __('manager.statistics.reports.objectNotFound');
            }
            $additionalObjectInformation[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID] = $issueId;
        }
        return $additionalObjectInformation;
    }


    /**
     * A helper method to get the submissionIds filter when section i.e.
     * series IDs and/or issue IDs are also passed.
     *
     * If the sectionIds and/or issueIds, and submissionIds were passed in the
     * request, then we only return IDs that match all conditions.
     *
     * Return null if there is no filetring by sectionIds, submissionIds or issueIds
    */
    public function processFilterSubmissionIds(array $filters = []): ?array
    {
        $submissionIds = parent::processFilterSubmissionIds($filters);

        if (array_key_exists(StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID, $filters)) {
            // Identify submissions which should be included in the results when issue IDs are passed
            if (isset($filters[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID])) {
                $service = Services::get('publicationStats');
                $submissionIds = $service->processIssueIds($filters[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID], $submissionIds);
            }
        }
        return $submissionIds;
    }

    /**
     * Get issue metrics based on passed parameters.
     *
     * @param array $columns DB columns the metrics should be aggregated by
     * @param array $filters Filter the metrics should be filtered by
     * @param array $orderBy The way the metrics should be ordered
     */
    public function getIssueMetrics(\APP\core\Request $request, array $columns = [], array $filters = [], array $orderBy = []): array
    {
        // if filter contains a column other than getContextMetricsColumns, return []
        $metrics = [];
        $service = Services::get('issueStats');
        $issueColumns = array_intersect($service->getStatsColumns(), $columns);
        if (!empty($issueColumns)) {
            // get metrics if there is no filter or if the filters contain an issue metrics column
            if (empty($filters) || !empty(array_intersect(array_keys($filters), $service->getStatsColumns()))) {
                // if filters contain StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE key
                // check if a value is issue or issue galley assoc type
                if (!isset($filters[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE]) ||
                    !empty(array_intersect($filters[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE], [Application::ASSOC_TYPE_ISSUE, Application::ASSOC_TYPE_ISSUE_GALLEY]))) {
                    // only possible to get metrics for the current context
                    $filters[StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID] = [$request->getContext()->getId()];
                    $args = $service->prepareStatsArgs($filters);
                    // TO-DO: validate orderBy
                    $metrics = $service->getMetrics($issueColumns, $orderBy, $args)->toArray();
                }
            }
        }
        return $metrics;
    }

    /**
     * @copydoc PKPUsageStatsReportPlugin::getMetrics()
     */
    public function getMetrics(\APP\core\Request $request, array $columns = [], array $filters = [], array $orderBy = [])
    {
        $submissionMetrics = $issueMetrics = $contextMetrics = $metrics = [];
        // get all metrics: submission, issue, context
        $submissionMetrics = $this->getSubmissionMetrics($request, $columns, $filters, $orderBy);
        $issueMetrics = $this->getIssueMetrics($request, $columns, $filters, $orderBy);
        $contextMetrics = $this->getContextMetrics($request, $columns, $filters, $orderBy);
        $metrics = array_merge($submissionMetrics, $issueMetrics, $contextMetrics);
        // If the result is a merge of two or more metrics tables,
        // apply the first sort criteria on it.
        // The only common criteria are date and metric.
        if ((!empty($submissionMetrics) && !empty($issueMetrics)) ||
            (!empty($submissionMetrics) && !empty($contextMetrics)) ||
            (!empty($issueMetrics) && !empty($contextMetrics))) {
            $file = 'debug.txt';
            $current = file_get_contents($file);
            $current .= print_r("++++ SORT ++++\n", true);
            $current .= print_r(current($orderBy), true);
            file_put_contents($file, $current);

            uasort(
                $metrics,
                function ($a, $b) use ($orderBy) {
                    $first = $a;
                    $second = $b;
                    $keyFirst = array_key_first($orderBy);
                    if ($orderBy[$keyFirst] == StatisticsHelper::STATISTICS_ORDER_DESC) {
                        $first = $b;
                        $second = $a;
                    }
                    if ($keyFirst == StatisticsHelper::STATISTICS_DIMENSION_MONTH) {
                        return strtotime($first->month) - strtotime($second->month);
                    } elseif ($keyFirst == StatisticsHelper::STATISTICS_DIMENSION_DAY) {
                        return strtotime($first->day) - strtotime($second->day);
                    } elseif ($keyFirst == StatisticsHelper::STATISTICS_METRIC) {
                        return $first->metric - $second->metric;
                    }
                }
            );
        }
        return $metrics;
    }

    /**
     * Get value for the report row value for the StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID column
     */
    protected function getReportRowValue(string $key, string $assocType, array $objectInformation): ?string
    {
        $rawValue = null;
        switch ($key) {
            case StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID:
                if ($assocType == Application::ASSOC_TYPE_ISSUE_GALLEY) {
                    $rawValue = $objectInformation['title'];
                } else {
                    $rawValue = '';
                }
                break;
            case StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID:
                if ($assocType == Application::ASSOC_TYPE_ISSUE) {
                    $rawValue = $objectInformation['title'];
                } else {
                    // the issue ID should be found in the function getObjectInformation for an existing object (e.g. submission or submission file)
                    $issueId = $objectInformation[StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID] ?? null;
                    if ($issueId) {
                        // if it is not in the issueTitles array, the object could not be found in the DB
                        if (array_key_exists($issueId, $this->issueTitles)) {
                            $rawValue = $this->issueTitles[$issueId];
                        } else {
                            $rawValue = __('manager.statistics.reports.objectNotFound');
                        }
                    } else {
                        $rawValue = '';
                    }
                }
                break;
        }
        return $rawValue;
    }
}
