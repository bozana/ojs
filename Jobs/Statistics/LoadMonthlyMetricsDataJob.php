<?php

declare(strict_types=1);

/**
 * @file Jobs/Statistics/LoadMonthly1MetricsDataJob.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LoadMonthly1MetricsDataJob
 * @ingroup jobs
 *
 * @brief Class to handle the usage metrics data loading as a Job
 */

namespace APP\Jobs\Statistics;

use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\Support\Jobs\BaseJob;

class LoadMonthly1MetricsDataJob extends BaseJob
{
    /**
     * @var string The month the usage metrics should be aggregated by
     */
    protected $month;

    /**
     * Create a new job instance.
     *
     */
    public function __construct(string $month)
    {
        $file = '/home/bozana/pkp/ojs-master/debug.txt';
        $current = file_get_contents($file);
        $current .= print_r("++++ LoadMonthly1MetricsDataJob construct ++++\n", true);
        file_put_contents($file, $current);

        parent::__construct();
        $this->month = $month;
    }

    /**
     * Execute the job.
     *
     */
    public function handle(): void
    {
        $file = '/home/bozana/pkp/ojs-master/debug.txt';
        $current = file_get_contents($file);
        $current .= print_r("++++ LoadMonthlyMetricsDataJob handle ++++\n", true);
        file_put_contents($file, $current);

        $application = Application::get();
        $request = $application->getRequest();
        $site = $request->getSite();
        $currentMonth = date('Ym'); // shall we consider only current month or maybe rather previous month?

        // geo
        DB::statement(
            "
			INSERT INTO metrics_submission_geo_monthly (context_id, submission_id, country, region, city, month, metric, metric_unique)
			SELECT gd.context_id, gd.submission_id, COALESCE(gd.country, ''), COALESCE(gd.region, ''), COALESCE(gd.city, ''), DATE_FORMAT(gd.date, '%Y%m') as month, SUM(gd.metric), SUM(gd.metric_unique) FROM metrics_submission_geo_daily gd WHERE month = ? GROUP BY gd.context_id, gd.submission_id, gd.country, gd.region, gd.city, month
			",
            [$this->month]
        );
        if ($site->getData('geoUsageStatsKeepDaily') == 0 && $this->month != $currentMonth) {
            DB::statement("DELETE FROM metrics_submission_geo_daily WHERE DATE_FORMAT(date, '%Y%m') = ?", [$this->month]);
        }

        // submissions
        DB::statement(
            "
			INSERT INTO metrics_counter_submission_monthly (context_id, submission_id, month, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
			SELECT sd.context_id, sd.submission_id, DATE_FORMAT(sd.date, '%Y%m') as month, SUM(sd.metric_investigations), SUM(sd.metric_investigations_unique), SUM(sd.metric_requests), SUM(sd.metric_requests_unique) FROM metrics_counter_submission_daily sd WHERE month = ? GROUP BY sd.context_id, sd.submission_id, month
			",
            [$this->month]
        );
        if ($site->getData('submissionUsageStatsKeepDaily') == 0 && $this->month != $currentMonth) {
            DB::statement("DELETE FROM metrics_counter_submission_daily WHERE DATE_FORMAT(date, '%Y%m') = ?", [$this->month]);
        }

        //institutions
        DB::statement(
            "
			INSERT INTO metrics_counter_submission_institution_monthly (context_id, submission_id, institution_id, month, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
			SELECT id.context_id, id.submission_id, id.institution_id, DATE_FORMAT(id.date, '%Y%m') as month, SUM(id.metric_investigations), SUM(id.metric_investigations_unique), SUM(id.metric_requests), SUM(id.metric_requests_unique) FROM metrics_counter_submission_institution_daily id WHERE month = ? GROUP BY id.context_id, id.submission_id, id.institution_id, month
			",
            [$this->month]
        );
        if ($site->getData('institutionUsageStatsKeepDaily') == 0 && $this->month != $currentMonth) {
            DB::statement("DELETE FROM metrics_counter_submission_institution_daily WHERE DATE_FORMAT(date, '%Y%m') = ?", [$this->month]);
        }

        $file = '/home/bozana/pkp/ojs-master/debug.txt';
        $current = file_get_contents($file);
        $current .= print_r("++++ LoadMetricsDataJob succeded ++++\n", true);
        file_put_contents($file, $current);
    }
}
