<?php

/**
 * @defgroup plugins_reports_usageStats Usage Stats Report Plugin
 */

/**
 * @file plugins/reports/usageStats/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_usageStats
 * @brief Wrapper for usage stats report plugin.
 *
 */

use APP\core\Application;

$application = Application::get();
$applicationName = $application->getName();
switch ($applicationName) {
    case 'ojs2':
        require_once(dirname(__FILE__) . '/OJSUsageStatsReportPlugin.inc.php');
        return new OJSUsageStatsReportPlugin();
        break;
    case 'omp':
        require_once(dirname(__FILE__) . '/OMSUsageStatsReportPlugin.inc.php');
        return new OMPUsageStatsReportPlugin();
        break;
    case 'ops':
        require_once(dirname(__FILE__) . '/OPSUsageStatsReportPlugin.inc.php');
        return new OPSUsageStatsReportPlugin();
        break;
    default:
        assert(false);
}
