<?php

/**
 * @file Jobs/Statistics/LoadMonthlyMetricsDataJob.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LoadMonthlyMetricsDataJob
 * @ingroup jobs
 *
 * @brief Class to monthly aggregate the usage metrics data as a Job
 */

namespace APP\Jobs\Statistics;

use APP\core\Application;
use APP\core\Services;
use PKP\Support\Jobs\BaseJob;

class LoadMonthlyMetricsDataJob extends BaseJob
{
    /**
     * The month the usage metrics should be aggregated by
     */
    protected string $month;

    /**
     * Create a new job instance.
     */
    public function __construct(string $month)
    {
        $file = '/home/bozana/pkp/ojs-master/debug.txt';
        $current = file_get_contents($file);
        $current .= print_r("++++ LoadMonthlyMetricsDataJob construct ++++\n", true);
        file_put_contents($file, $current);

        parent::__construct();
        $this->month = $month;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $file = '/home/bozana/pkp/ojs-master/debug.txt';
        $current = file_get_contents($file);
        $current .= print_r("++++ LoadMonthlyMetricsDataJob handle ++++\n", true);
        $current .= print_r($this->month, true);
        file_put_contents($file, $current);

        $application = Application::get();
        $request = $application->getRequest();
        $site = $request->getSite();
        $currentMonth = date('Ym'); // shall we consider only current month or maybe rather previous month?

        // geo
        $geoService = Services::get('geoStats');
        $geoService->aggregateMetrics($this->month);
        if (!$site->getData('usageStatsKeepDaily') && $this->month != $currentMonth) {
            $geoService->deleteDailyMetrics($this->month);
        }

        // COUNTER submissions and insitutions
        $counterService = Services::get('sushiStats');
        $counterService->aggregateMetrics($this->month);
        if (!$site->getData('usageStatsKeepDaily') && $this->month != $currentMonth) {
            $counterService->deleteDailyMetrics($this->month);
        }

        $file = '/home/bozana/pkp/ojs-master/debug.txt';
        $current = file_get_contents($file);
        $current .= print_r("++++ LoadMetricsDataJob succeded ++++\n", true);
        file_put_contents($file, $current);
    }
}
