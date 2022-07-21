<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_CleanOldMetrics.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_CleanOldMetrics
 * @brief Clean the old metrics:
 *  delete migrated entries with the metric type ojs::counter from the DB table metrics,
 *  move back the orphaned metrics from the temporary metrics_tmp,
 *  rename or delete the DB table metrics,
 *  delete DB table usage_stats_temporary_records.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I6782_CleanOldMetrics extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Delete the entries with the metric type ojs::counter from the DB table metrics -> they were migrated in earlier scripts
        if (Schema::hasTable('metrics')) {
            DB::table('metrics')->where('metric_type', '=', 'ojs::counter')->delete();

            // Move back the orphaned metrics form the temporary metrics_tmp
            $metricsColumns = Schema::getColumnListing('metrics_tmp');
            $metricsTmp = DB::table('metrics_tmp')->select('*');
            DB::table('metrics')->insertUsing($metricsColumns, $metricsTmp);
            Schema::drop('metrics_tmp');

            $metricsExist = DB::table('metrics')->count();
            // if table metrics is now not empty rename it, else delete it
            if ($metricsExist > 0) {
                Schema::rename('metrics', 'metrics_old');
            } else {
                Schema::drop('metrics');
            }
        }
        // Delete the old usage_stats_temporary_records table
        if (Schema::hasTable('usage_stats_temporary_records')) {
            Schema::drop('usage_stats_temporary_records');
        }
    }

    /**
     * Reverse the downgrades
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
