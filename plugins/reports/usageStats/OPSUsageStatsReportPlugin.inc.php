<?php

/**
 * @file plugins/reports/usageStats/OPSUsageStatsReportPlugin.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OPSUsageStatsReportPlugin
 * @ingroup plugins_reports_usageStats
 *
 * @brief OPS default statistics report plugin (and metrics provider)
 */

use PKP\statistics\StatisticsHelper;

import('plugins.reports.usageStats.PKPUsageStatsReportPlugin');


class OPSUsageStatsReportPlugin extends PKPUsageStatsReportPlugin
{
    /**
     * @copydoc ReportPlugin::getColumns()
     */
    public function getReportColumns(): ?array
    {
        return $this->getOrderedReportColumns();
    }

    /**
     * @copydoc PKPUsageStatsReportPlugin::getOrderedReportColumns()
     */
    protected function getOrderedReportColumns(): array
    {
        return [
            StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE,
            StatisticsHelper::STATISTICS_DIMENSION_FILE_TYPE,
            StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID,
            StatisticsHelper::STATISTICS_DIMENSION_PKP_SECTION_ID,
            StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID,
            StatisticsHelper::STATISTICS_DIMENSION_MONTH,
            StatisticsHelper::STATISTICS_METRIC,
        ];
    }
}
