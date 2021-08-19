<?php

/**
 * @file classes/tasks/UsageStatsMonthly.inc.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsMonthly
 * @ingroup tasks
 *
 * @brief Class responsible to aggregate monthly usage stats.
 */

namespace APP\tasks;

use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\scheduledTask\ScheduledTask;

class UsageStatsMonthly extends ScheduledTask
{
    /**
     * @copydoc ScheduledTask::getName()
     */
    public function getName(): string
    {
        return __('usageStats.usageStatsMonthly');
    }

    /**
     * @copydoc ScheduledTask::executeActions()
     */
    public function executeActions(): bool
    {
        $currentMonth = date('Ym');
        $application = Application::get();
        $request = $application->getRequest();
        $site = $request->getSite();

        // geo
        DB::statement(
            "
			INSERT INTO metrics_submission_geo_monthly (context_id, submission_id, country, region, city, month, metric, metric_unique)
			SELECT gd.context_id, gd.submission_id, COALESCE(gd.country, ''), COALESCE(gd.region, ''), COALESCE(gd.city, ''), DATE_FORMAT(gd.date, '%Y%m') as month, SUM(gd.metric), SUM(gd.metric_unique) FROM metrics_submission_geo_daily gd WHERE month <> ? GROUP BY gd.context_id, gd.submission_id, gd.country, gd.region, gd.city, month
			",
            [$currentMonth]
        );
        if ($site->getData('geoUsageStatsKeepDaily') == 0) {
            DB::statement("DELETE FROM metrics_submission_geo_daily WHERE DATE_FORMAT(date, '%Y%m') <> ?", [$currentMonth]);
        }

        // submissions
        DB::statement(
            "
			INSERT INTO metrics_counter_submission_monthly (context_id, submission_id, month, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
			SELECT sd.context_id, sd.submission_id, DATE_FORMAT(sd.date, '%Y%m') as month, SUM(sd.metric_investigations), SUM(sd.metric_investigations_unique), SUM(sd.metric_requests), SUM(sd.metric_requests_unique) FROM metrics_counter_submission_daily sd WHERE month <> ? GROUP BY sd.context_id, sd.submission_id, month
			",
            [$currentMonth]
        );
        if ($site->getData('submissionUsageStatsKeepDaily') == 0) {
            DB::statement("DELETE FROM metrics_counter_submission_daily WHERE DATE_FORMAT(date, '%Y%m') <> ?", [$currentMonth]);
        }

        //institutions
        DB::statement(
            "
			INSERT INTO metrics_counter_submission_institution_monthly (context_id, submission_id, institution_id, month, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
			SELECT id.context_id, id.submission_id, id.institution_id, DATE_FORMAT(id.date, '%Y%m') as month, SUM(id.metric_investigations), SUM(id.metric_investigations_unique), SUM(id.metric_requests), SUM(id.metric_requests_unique) FROM metrics_counter_submission_institution_daily id WHERE month <> ? GROUP BY id.context_id, id.submission_id, id.institution_id, month
			",
            [$currentMonth]
        );
        if ($site->getData('institutionUsageStatsKeepDaily') == 0) {
            DB::statement("DELETE FROM metrics_counter_submission_institution_daily WHERE DATE_FORMAT(date, '%Y%m') <> ?", [$currentMonth]);
        }

        return true;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\tasks\UsageStatsMonthly', '\UsageStatsMonthly');
}
