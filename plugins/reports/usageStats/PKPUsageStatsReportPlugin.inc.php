<?php

/**
 * @file plugins/reports/usageStats/PKPUsageStatsReportPlugin.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPUsageStatsReportPlugin
 * @ingroup plugins_reports_usageStats
 *
 * @brief OJS, OMP and OPS default statistics report plugin (and metrics provider)
 */

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\statistics\StatisticsHelper;
use PKP\plugins\ReportPlugin;

abstract class PKPUsageStatsReportPlugin extends ReportPlugin
{
    /**
     * Save/cache object information (titles) that could be used
     * in several report rows.
     */
    protected array $submissionTitles = [];
    protected array $sectionTitles = [];

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        $this->addLocaleData();
        return $success;
    }

    /**
     * @copydoc Plugin::getName()
     */
    public function getName()
    {
        return 'PKPUsageStatsReportPlugin';
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.reports.usageStats.report.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.reports.usageStats.report.description');
    }

    /**
     * @copydoc ReportPlugin::display()
     */
    public function display($args, $request)
    {
        $context = $request->getContext();
        $columns = $this->getReportColumns();
        $filters = [StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID => [$context->getId()]];
        $orderBy = [StatisticsHelper::STATISTICS_DIMENSION_MONTH => StatisticsHelper::STATISTICS_ORDER_ASC];
        $reportArgs = [
            'columns' => $columns,
            'filters' => json_encode($filters),
            'orderBy' => json_encode($orderBy)
        ];
        // redirect in order to have the report URL
        $request->redirect(null, null, 'reports', 'generateReport', $reportArgs);
    }

    /**
     * Get report columns, used as report URL parameters and to get metrics.
     */
    abstract public function getReportColumns(): array;

    /**
     * Get report columns in the wished order.
     */
    abstract public function getOrderedReportColumns(): array;

    /**
     * Get metrics for the report based on the given parameters.
     */
    abstract public function getMetrics(\APP\core\Request $request, array $columns = [], array $filters = [], array $orderBy = []);

    /**
     * Get ID columns.
     * If one of the columns is requested, the ID column will be displayed.
     */
    protected function getIDColumns(): array
    {
        return [
            StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID,
            StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID,
            StatisticsHelper::STATISTICS_DIMENSION_FILE_ID,
        ];
    }

    /**
     * Allow subclasses to add additional information for a submission.
     */
    protected function getAdditionalSubmissionInformation(\APP\submission\Submission $submission): array
    {
        return [];
    }

    /**
     * Allow subclasses to set the report row value
     */
    protected function getReportRowValue(string $key, string $assocType, array $objectInformation): ?string
    {
        return null;
    }

    /**
     * Get data object ID as assoc type
     * based on passed metrics result record
     */
    protected function getAssocId(array $record): array
    {
        $assocId = $assocType = null;
        if (isset($record[StatisticsHelper::STATISTICS_DIMENSION_FILE_ID])) {
            $assocId = $record[StatisticsHelper::STATISTICS_DIMENSION_FILE_ID];
            $assocType = Application::ASSOC_TYPE_SUBMISSION_FILE;
        } elseif (isset($record[StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID])) {
            $assocId = $record[StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID];
            $assocType = Application::ASSOC_TYPE_SUBMISSION;
        } elseif (isset($record[StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID])) {
            $assocId = $record[StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID];
            $assocType = Application::getContextAssocType();
        }
        return [$assocId, $assocType];
    }

    /**
     * Get data object information i.e.
     * title and associated infromation (e.g. submission ID and section ID for submission files)
     * based on passed assoc type and id.
     */
    protected function getObjectInformation(int $assocId, int $assocType): array
    {
        $objectTitle = $submissionId = $sectionId = null;
        $additionalObjectInformation = [];
        switch ($assocType) {
            case Application::ASSOC_TYPE_SUBMISSION_FILE:
            case Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER:
                $submissionFile = Services::get('submissionFile')->get($assocId);
                if (!$submissionFile) {
                    break;
                }
                $objectTitle = $submissionFile->getLocalizedData('name');
                $assocId = $submissionFile->getData('submissionId');
                // no break, continue to get the submission information
            case Application::ASSOC_TYPE_SUBMISSION:
            case Application::ASSOC_TYPE_SUBMISSION_FILE:
            case Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER:
                $submission = Repo::submission()->get($assocId);
                if (!$submission) {
                    break;
                }
                // possibility for subclasses to add additional information needed
                $additionalObjectInformation = $this->getAdditionalSubmissionInformation($submission);
                $submissionId = $submission->getId();
                $this->submissionTitles[$submissionId] = $submission->getLocalizedTitle();

                $sectionId = $submission->getCurrentPublication()->getData('sectionId');
                if (!array_key_exists($sectionId, $this->sectionTitles)) {
                    $sectionDao = Application::getSectionDAO();
                    $section = $sectionDao->getById($sectionId);
                    $this->sectionTitles[$sectionId] = $section ? $section->getLocalizedTitle() : __('manager.statistics.reports.objectNotFound');
                }
                if (!$objectTitle) { // it could be submission file
                    $objectTitle = $submission->getLocalizedTitle();
                }
                break;
        }
        if (!$objectTitle) {
            $objectTitle = __('manager.statistics.reports.objectNotFound');
        }
        // merge arrays keeping the keys
        return [
            'title' => $objectTitle,
            StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID => $submissionId,
            StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID => $sectionId,
        ] + $additionalObjectInformation;
    }

    /**
     * A helper method to get the submissionIds filter when section i.e.
     * series IDs are also passed.
     *
     * If the sectionIds and submissionIds params were both passed in the
     * request, then we only return IDs that match both conditions.
     *
     * If there is no filetring by sectionIds nor submissionIds, return null
    */
    public function processFilterSubmissionIds(array $filters = []): ?array
    {
        $submissionIds = null;
        if (array_key_exists(StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID, $filters) ||
            array_key_exists(StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID, $filters)) {
            $submissionIds = $filters[StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID] ?? [];
            // Identify submissions which should be included in the results when section i.e. series IDs are passed
            if (isset($filters[StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID])) {
                $service = Services::get('publicationStats');
                $submissionIds = $service->processSectionIds($filters[StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID], $submissionIds);
            }
        }
        return $submissionIds;
    }

    /**
     * Get submission metrics based on passed parameters.
     */
    public function getSubmissionMetrics(\APP\core\Request $request, array $columns = [], array $filters = [], array $orderBy = []): array
    {
        $metrics = [];
        $service = Services::get('publicationStats');
        $publicationColumns = array_intersect($service->getStatsColumns(), $columns);
        // get metrics only if the given columns contain a submission metrics column
        if (!empty($publicationColumns)) {
            $validFilterColumns = array_merge($service->getStatsColumns(), [StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID, StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID]);
            // get metrics if there is no filter or if the filters contain a submission metrics columns, section ID or issue ID
            if (empty($filters) || !empty(array_intersect(array_keys($filters), $validFilterColumns))) {
                // if the filters contain StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE key
                // check if a value is submission abstract, file or supp file assoc type
                if (!isset($filters[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE]) ||
                    !empty(array_intersect($filters[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE], [Application::ASSOC_TYPE_SUBMISSION_FILE, Application::ASSOC_TYPE_SUBMISSION, Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER]))) {
                    $submissionIds = $this->processFilterSubmissionIds($filters);
                    // is submission IDs is not null a submission IDs filter was set
                    if ($submissionIds !== null) {
                        if (empty($submissionIds)) {
                            return [];
                        } else {
                            $filters[StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID] = $submissionIds;
                        }
                    }
                    // only possible to get metrics for the current context
                    $filters[StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID] = [$request->getContext()->getId()];
                    // TO-DO: validate orderBy
                    $args = $service->prepareStatsArgs($filters);
                    $metrics = $service->getMetrics($publicationColumns, $orderBy, $args)->toArray();
                }
            }
        }
        return $metrics;
    }

    /**
    * Get context (index page) metrics based on passed parameters.
    */
    public function getContextMetrics(\APP\core\Request $request, array $columns = [], array $filters = [], array $orderBy = []): array
    {
        $metrics = [];
        $service = Services::get('contextStats');
        $contextColumns = array_intersect($service->getStatsColumns(), $columns);
        // get metrics only if the given columns contain a context metrics column
        if (!empty($contextColumns)) {
            // get metrics if there is no filter or if the filters contain a context metrics columns
            if (empty($filters) || !empty(array_intersect(array_keys($filters), $service->getStatsColumns()))) {
                // if the filters contain StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE key
                // check if a value is context assoc type
                if (!isset($filters[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE]) ||
                    !empty(array_intersect($filters[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE], [Application::getContextAssocType()]))) {
                    // only possible to get metrics for the current context
                    $filters[StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID] = [$request->getContext()->getId()];
                    $args = $service->prepareStatsArgs($filters);
                    // TO-DO: validate orderBy
                    $metrics = $service->getMetrics($contextColumns, $orderBy, $args)->toArray();
                }
            }
        }
        return $metrics;
    }

    /**
     * Create CSV file.
     */
    public function createCSV(\APP\core\Request $request, array $columnNames, array $metrics)
    {
        import('classes.statistics.StatisticsHelper');
        $statsHelper = new StatisticsHelper();

        $context = $request->getContext();

        header('content-type: text/comma-separated-values');
        header('content-disposition: attachment; filename=statistics-' . date('Ymd') . '.csv');
        $fp = fopen('php://output', 'wt');
        //Add BOM (byte order mark) to fix UTF-8 in Excel
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, [$this->getDisplayName()]);
        fputcsv($fp, [$this->getDescription()]);
        fputcsv($fp, [__('manager.statistics.reports.reportUrl') . ': ' . $request->getCompleteUrl()]);
        fputcsv($fp, ['']);

        $columnNames = array_merge([''], $columnNames);
        fputcsv($fp, $columnNames);

        foreach ($metrics as $record) {
            $record = json_decode(json_encode($record), true);
            $assocId = $assocType = null;
            $objectInformation = [];

            if (array_key_exists(StatisticsHelper::STATISTICS_DIMENSION_ASSOC_ID, $columnNames)) {
                [$assocId, $assocType] = $this->getAssocId($record);
            }
            if (isset($assocId) && isset($assocType)) {
                // get Context title here, because we already have the object
                if ($assocType == Application::getContextAssocType()) {
                    $objectTitle = $context->getLocalizedName();
                } else {
                    $objectInformation = $this->getObjectInformation($assocId, $assocType);
                    $objectTitle = $objectInformation['title'];
                    $submissionId = $objectInformation[StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID];
                    $sectionId = $objectInformation[StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID];
                }
            } elseif (isset($record[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE])) { // in case only assoc_type column is given
                $assocType = $record[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE];
            }

            $row = [];
            foreach ($columnNames as $key => $name) {
                if (empty($name)) {
                    // Column just for better displaying.
                    $row[] = '';
                    continue;
                }

                // Possibility for subclasses to set the row values.
                $rawValue = $this->getReportRowValue($key, $assocType, $objectInformation);
                if (isset($rawValue)) {
                    $row[] = $rawValue;
                    continue;
                }

                switch ($key) {
                    case StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE:
                        $row[] = $statsHelper->getObjectTypeString($assocType);
                        break;
                    case StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID:
                        $row[] = $context->getLocalizedName();
                        break;
                    case StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID:
                        if ($assocType == Application::ASSOC_TYPE_SUBMISSION) {
                            $row[] = $objectTitle;
                        } elseif ($submissionId) {
                            if (array_key_exists($submissionId, $this->submissionTitles)) {
                                $row[] = $this->submissionTitles[$submissionId];
                            }
                        } else {
                            $row[] = '';
                        }
                        break;
                    case StatisticsHelper::STATISTICS_DIMENSION_FILE_ID:
                        if (in_array($assocType, [Application::ASSOC_TYPE_SUBMISSION_FILE, Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER])) {
                            $row[] = $objectTitle;
                        } else {
                            $row[] = '';
                        }
                        break;
                    case StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID:
                        if ($sectionId) {
                            if (array_key_exists($sectionId, $this->sectionTitles)) {
                                $row[] = $this->sectionTitles[$sectionId];
                            } else {
                                $row[] = __('manager.statistics.reports.objectNotFound');
                            }
                        } else {
                            $row[] = '';
                        }
                        break;
                    case StatisticsHelper::STATISTICS_DIMENSION_FILE_TYPE:
                        if (isset($record[$key])) {
                            $row[] = $statsHelper->getFileTypeString($record[$key]);
                        } else {
                            $row[] = '';
                        }
                        break;
                    case StatisticsHelper::STATISTICS_DIMENSION_ASSOC_ID:
                        $row[] = $assocId;
                        break;
                    case StatisticsHelper::STATISTICS_DIMENSION_MONTH:
                        // has changed: till now it was in the form Ym, now Y-m
                        $row[] = substr($record[$key], 0, 7);
                        break;
                    case StatisticsHelper::STATISTICS_DIMENSION_DAY:
                        // has changed: till now it was in the form Ymd, now Y-m-d
                        $row[] = $record[$key];
                        break;
                    case StatisticsHelper::STATISTICS_METRIC:
                        $row[] = $record[$key];
                        break;
                }
            }
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    /**
     * Get report column names.
     */
    public function getColumnNames(array $columns = []): array
    {
        import('classes.statistics.StatisticsHelper');
        $statsHelper = new StatisticsHelper();
        $allColumnNames = $statsHelper->getColumnNames();

        $columnNames = [];
        if (!empty(array_intersect($this->getIDColumns(), $columns))) {
            $columnNames[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_ID] = $allColumnNames[StatisticsHelper::STATISTICS_DIMENSION_ASSOC_ID];
        }

        $orderedColumns = $this->getOrderedReportColumns();
        foreach ($orderedColumns as $column) {
            if (in_array($column, $columns)) {
                $columnNames[$column] = $allColumnNames[$column];
            } elseif ($column == StatisticsHelper::STATISTICS_DIMENSION_MONTH &&
                in_array(StatisticsHelper::STATISTICS_DIMENSION_DAY, $columns)) {
                $columnNames[StatisticsHelper::STATISTICS_DIMENSION_DAY] = $allColumnNames[StatisticsHelper::STATISTICS_DIMENSION_DAY];
            }
        }

        // Make sure the metric column will always be present.
        if (!in_array(StatisticsHelper::STATISTICS_METRIC, $columnNames)) {
            $columnNames[StatisticsHelper::STATISTICS_METRIC] = $allColumnNames[StatisticsHelper::STATISTICS_METRIC];
        }
        return $columnNames;
    }

    /**
     * Get the CSV, i.e. comma separated, report.
     */
    public function getCSV(\APP\core\Request $request, array $columns = [], array $filters = [], array $orderBy = [])
    {
        $columnNames = $this->getColumnNames($columns);
        $metrics = $this->getMetrics($request, $columns, $filters, $orderBy);
        $this->createCSV($request, $columnNames, $metrics);
    }
}
