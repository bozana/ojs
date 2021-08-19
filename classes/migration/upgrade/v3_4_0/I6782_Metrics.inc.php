<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_Metrics.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_Metrics
 * @brief Migrate usage stats settings, and data from the old DB table metrics into the new DB tables.
 */

namespace APP\migration\upgrade\v3_4_0;

use APP\core\Services;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as Schema;
use PKP\config\Config;

class I6782_Metrics extends Migration
{
    /**
     * Run the migration.
     */
    public function up()
    {

        /*
        // usage stats settings
        // ??? createLogFiles
        // ??? enableInstitutionUsageStats
        $optionalColumns = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'optionalColumns')
            ->value('setting_value');

        $enableGeoUsageStats = $geoUsageStatsKeepDaily = 0;
        if (!is_null($optionalColumns)) {
            $geoUsageStatsKeepDaily = 1;
            if (str_contains($optionalColumns, 'city')) {
                $enableGeoUsageStats = 3;
            } elseif (str_contains($optionalColumns, 'region')) {
                $enableGeoUsageStats = 2;
            } else {
                $enableGeoUsageStats = 1;
            }
        }

        $compressArchives = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'compressArchives')
            ->value('setting_value');
        $displayStatistics = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'displayStatistics')
            ->where('context_id', '=', 0)
            ->value('setting_value');
        $chartType = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'chartType')
            ->where('context_id', '=', 0)
            ->value('setting_value');
        $datasetMaxCount = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'datasetMaxCount')
            ->where('context_id', '=', 0)
            ->value('setting_value');
        DB::table('site_settings')->insertOrIgnore([
            ['setting_name' => 'archivedUsageStatsLogFiles', 'setting_value' => $compressArchives],
            ['setting_name' => 'enableUsageStatsDisplay', 'setting_value' => $displayStatistics],
            ['setting_name' => 'usageStatsDisplayChartType', 'setting_value' => $chartType],
            ['setting_name' => 'usageStatsDisplayMaxCount', 'setting_value' => $datasetMaxCount],
            ['setting_name' => 'enableGeoUsageStats', 'setting_value' => $enableGeoUsageStats],
            ['setting_name' => 'geoUsageStatsKeepDaily', 'setting_value' => $geoUsageStatsKeepDaily]
        ]);

        $contextIds = Services::get('context')->getIds([
            'isEnabled' => true,
        ]);
        foreach ($contextIds as $contextId) {
            $contextDisplayStatistics = $contextChartType = $contextDatasetMaxCount = null;
            $contextDisplayStatistics = DB::table('plugin_settings')
                ->where('plugin_name', '=', 'usagestatsplugin')
                ->where('setting_name', '=', 'displayStatistics')
                ->where('context_id', '=', $contextId)
                ->value('setting_value');
            $contextChartType = DB::table('plugin_settings')
                ->where('plugin_name', '=', 'usagestatsplugin')
                ->where('setting_name', '=', 'chartType')
                ->where('context_id', '=', $contextId)
                ->value('setting_value');
            $contextDatasetMaxCount = DB::table('plugin_settings')
                ->where('plugin_name', '=', 'usagestatsplugin')
                ->where('setting_name', '=', 'datasetMaxCount')
                ->where('context_id', '=', $contextId)
                ->value('setting_value');
            if (isset($contextDisplayStatistics)) {
                DB::table('journal_settings')->insertOrIgnore([
                    ['journal_id' => $contextId, 'setting_name' => 'enableUsageStatsDisplay', 'setting_value' => $contextDisplayStatistics],
                    ['journal_id' => $contextId, 'setting_name' => 'usageStatsDisplayChartType', 'setting_value' => $contextChartType],
                    ['journal_id' => $contextId, 'setting_name' => 'usageStatsDisplayMaxCount', 'setting_value' => $contextDatasetMaxCount]
                ]);
            }
        }
        */

        // metrics tables
        $contextDao = \APP\core\Application::getContextDAO();
        $contextTable = $contextDao->tableName;
        $contextIdColumn = $contextDao->primaryKeyColumn;
        $representationDao = \APP\core\Application::getRepresentationDAO();
        $representationTable = $representationDao->tableName;
        $representationIdColumn = $representationDao->primaryKeyColumn;

        if (!Schema::hasTable('metrics_context')) {
            Schema::create('metrics_context', function (Blueprint $table) use ($contextTable, $contextIdColumn) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->date('date');
                $table->integer('metric');
                $table->foreign('context_id')->references($contextIdColumn)->on($contextTable);
                $table->index(['load_id'], 'metrics_context_load_id');
                $table->index(['context_id'], 'metrics_context_context_id');
            });
        }
        if (!Schema::hasTable('metrics_issue')) {
            Schema::create('metrics_issue', function (Blueprint $table) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('issue_id');
                $table->bigInteger('issue_galley_id')->nullable();
                $table->date('date');
                $table->integer('metric');
                $table->foreign('context_id')->references('journal_id')->on('journals');
                $table->foreign('issue_id')->references('issue_id')->on('issues');
                $table->foreign('issue_galley_id')->references('galley_id')->on('issue_galleys');
                $table->index(['load_id'], 'metrics_issue_load_id');
                $table->index(['context_id', 'issue_id'], 'metrics_issue_context_id_issue_id');
            });
        }
        if (!Schema::hasTable('metrics_submission')) {
            Schema::create('metrics_submission', function (Blueprint $table) use ($contextTable, $contextIdColumn, $representationTable, $representationIdColumn) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->bigInteger('representation_id')->nullable();
                $table->bigInteger('submission_file_id')->nullable();
                $table->bigInteger('file_type')->nullable();
                $table->bigInteger('assoc_type');
                $table->date('date');
                $table->integer('metric');
                $table->foreign('context_id')->references($contextIdColumn)->on($contextTable);
                $table->foreign('submission_id')->references('submission_id')->on('submissions');
                $table->foreign('representation_id')->references($representationIdColumn)->on($representationTable);
                $table->foreign('submission_file_id')->references('submission_file_id')->on('submission_files');
                $table->index(['load_id'], 'metrics_submission_load_id');
                $table->index(['context_id', 'submission_id', 'assoc_type', 'file_type'], 'metrics_submission_context_id_submission_id_assoc_type_file_type');
            });
        }
        if (!Schema::hasTable('metrics_counter_submission_daily')) {
            Schema::create('metrics_counter_submission_daily', function (Blueprint $table) use ($contextTable, $contextIdColumn) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->date('date');
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->foreign('context_id')->references($contextIdColumn)->on($contextTable);
                $table->foreign('submission_id')->references('submission_id')->on('submissions');
                $table->index(['load_id'], 'metrics_counter_submission_daily_load_id');
                $table->index(['context_id', 'submission_id'], 'metrics_counter_submission_daily_context_id_submission_id');
            });
            if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
                DB::statement('ALTER TABLE metrics_counter_submission_daily ADD CONSTRAINT uc_load_id_context_id_submission_id_date UNIQUE INCLUDE (load_id, context_id, submission_id, date)');
            } else {
                DB::statement('ALTER TABLE metrics_counter_submission_daily ADD CONSTRAINT uc_load_id_context_id_submission_id_date UNIQUE (load_id, context_id, submission_id, date)');
            }
        }
        if (!Schema::hasTable('metrics_counter_submission_monthly')) {
            Schema::create('metrics_counter_submission_monthly', function (Blueprint $table) use ($contextTable, $contextIdColumn) {
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->string('month', 6);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->foreign('context_id')->references($contextIdColumn)->on($contextTable);
                $table->foreign('submission_id')->references('submission_id')->on('submissions');
                $table->index(['context_id', 'submission_id'], 'metrics_counter_submission_monthly_context_id_submission_id');
            });
            if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
                DB::statement('ALTER TABLE metrics_counter_submission_monthly ADD CONSTRAINT uc_context_id_submission_id_month UNIQUE INCLUDE (context_id, submission_id, month)');
            } else {
                DB::statement('ALTER TABLE metrics_counter_submission_monthly ADD CONSTRAINT uc_context_id_submission_id_month UNIQUE (context_id, submission_id, month)');
            }
        }
        if (!Schema::hasTable('metrics_counter_submission_institution_daily')) {
            Schema::create('metrics_counter_submission_institution_daily', function (Blueprint $table) use ($contextTable, $contextIdColumn) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->bigInteger('institution_id');
                $table->date('date');
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->foreign('context_id')->references($contextIdColumn)->on($contextTable);
                $table->foreign('submission_id')->references('submission_id')->on('submissions');
                $table->foreign('institution_id')->references('institution_id')->on('institutions');
                $table->index(['load_id'], 'metrics_counter_submission_institution_daily_load_id');
                $table->index(['context_id', 'submission_id'], 'metrics_counter_submission_institution_daily_context_id_submission_id');
            });
            if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
                DB::statement('ALTER TABLE metrics_counter_submission_institution_daily ADD CONSTRAINT uc_load_id_context_id_submission_id_institution_id_date UNIQUE INCLUDE (load_id, context_id, submission_id, institution_id, date)');
            } else {
                DB::statement('ALTER TABLE metrics_counter_submission_institution_daily ADD CONSTRAINT uc_load_id_context_id_submission_id_institution_id_date UNIQUE (load_id, context_id, submission_id, institution_id, date)');
            }
        }
        if (!Schema::hasTable('metrics_counter_submission_institution_monthly')) {
            Schema::create('metrics_counter_submission_institution_monthly', function (Blueprint $table) use ($contextTable, $contextIdColumn) {
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->bigInteger('institution_id');
                $table->string('month', 6);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->foreign('context_id')->references($contextIdColumn)->on($contextTable);
                $table->foreign('submission_id')->references('submission_id')->on('submissions');
                $table->foreign('institution_id')->references('institution_id')->on('institutions');
                $table->index(['context_id', 'submission_id'], 'metrics_counter_submission_institution_monthly_context_id_submission_id');
            });
            if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
                DB::statement('ALTER TABLE metrics_counter_submission_institution_monthly ADD CONSTRAINT uc_context_id_submission_id_institution_id_month UNIQUE INCLUDE (context_id, submission_id, institution_id, month)');
            } else {
                DB::statement('ALTER TABLE metrics_counter_submission_institution_monthly ADD CONSTRAINT uc_context_id_submission_id_institution_id_month UNIQUE (context_id, submission_id, institution_id, month)');
            }
        }
        if (!Schema::hasTable('metrics_counter_submission_geo_daily')) {
            Schema::create('metrics_counter_submission_geo_daily', function (Blueprint $table) use ($contextTable, $contextIdColumn) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->string('country', 2)->default('');
                $table->string('region', 3)->default('');
                $table->string('city', 255)->default('');
                $table->date('date');
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->foreign('context_id')->references($contextIdColumn)->on($contextTable);
                $table->foreign('submission_id')->references('submission_id')->on('submissions');
                $table->index(['load_id'], 'metrics_counter_submission_geo_daily_load_id');
                $table->index(['context_id', 'submission_id'], 'metrics_counter_submission_geo_daily_context_id_submission_id');
            });
            if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
                DB::statement('ALTER TABLE metrics_counter_submission_geo_daily ADD CONSTRAINT uc_load_id_context_id_submission_id_country_region_city_date UNIQUE INCLUDE (load_id, context_id, submission_id, country, region, city, date)');
            } else {
                DB::statement('ALTER TABLE metrics_counter_submission_geo_daily ADD CONSTRAINT uc_load_id_context_id_submission_id_country_region_city_date UNIQUE (load_id, context_id, submission_id, country, region, city, date)');
            }
        }
        if (!Schema::hasTable('metrics_counter_submission_geo_monthly')) {
            Schema::create('metrics_counter_submission_geo_monthly', function (Blueprint $table) use ($contextTable, $contextIdColumn) {
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->string('country', 2)->default('');
                $table->string('region', 3)->default('');
                $table->string('city', 255)->default('');
                $table->string('month', 6);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->foreign('context_id')->references($contextIdColumn)->on($contextTable);
                $table->foreign('submission_id')->references('submission_id')->on('submissions');
                $table->index(['context_id', 'submission_id'], 'metrics_counter_submission_geo_monthly_context_id_submission_id');
            });
            if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
                DB::statement('ALTER TABLE metrics_counter_submission_geo_monthly ADD CONSTRAINT uc_context_id_submission_id_country_region_city_month UNIQUE INCLUDE (context_id, submission_id, country, region, city, month)');
            } else {
                DB::statement('ALTER TABLE metrics_counter_submission_geo_monthly ADD CONSTRAINT uc_context_id_submission_id_country_region_city_month UNIQUE (context_id, submission_id, country, region, city, month)');
            }
        }

        // Requires that new DB metrics tables are already there
        // The not existing foreign keys should already be removed in PreflightCheckStatsMigration
        DB::statement("INSERT IGNORE INTO metrics_context (load_id, context_id, date, metric) SELECT m.load_id, m.assoc_id, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d'), m.metric FROM metrics m WHERE m.assoc_type = 256 AND m.metric_type = 'ojs::counter'");

        DB::statement("INSERT IGNORE INTO metrics_issue (load_id, context_id, issue_id, issue_galley_id, date, metric) SELECT m.load_id, m.context_id, m.assoc_id, null, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d'), m.metric FROM metrics m WHERE m.assoc_type = 259 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT IGNORE INTO metrics_issue (load_id, context_id, issue_id, issue_galley_id, date, metric) SELECT m.load_id, m.context_id, m.assoc_object_id, m.assoc_id, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d'), m.metric FROM metrics m WHERE m.assoc_type = 261 AND m.assoc_object_type = 259 AND m.metric_type = 'ojs::counter'");

        DB::statement("INSERT IGNORE INTO metrics_submission (load_id, context_id, submission_id, representation_id, file_id, file_type, assoc_type, date, metric) SELECT m.load_id, m.context_id, m.assoc_id, null, null, null, m.assoc_type, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d'), m.metric FROM metrics m WHERE m.assoc_type = 1048585 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT IGNORE INTO metrics_submission (load_id, context_id, submission_id, representation_id, file_id, file_type, assoc_type, date, metric) SELECT m.load_id, m.context_id, m.submission_id, m.representation_id, m.assoc_id, m.file_type, m.assoc_type, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d'), m.metric FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT IGNORE INTO metrics_submission (load_id, context_id, submission_id, representation_id, file_id, file_type, assoc_type, date, metric) SELECT m.load_id, m.context_id, m.submission_id, m.representation_id, m.assoc_id, m.file_type, m.assoc_type, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d'), m.metric FROM metrics m WHERE m.assoc_type = 531 AND m.metric_type = 'ojs::counter'");


        /*
        //if ($enableGeoUsageStats > 0) {
        // fix wrong entries
        DB::statement("UPDATE metrics SET city = NULL where city = ''");
        DB::statement("UPDATE metrics SET country_id = NULL where country_id = ''");
        DB::statement("UPDATE metrics SET region = NULL where region = '' OR region = '0'");
        // migrate region numbers to letters, s. https://github.com/matomo-org/matomo/blob/4.x-dev/plugins/GeoIp2/data/regionMapping.php
        // TO-DO
        DB::statement("INSERT INTO metrics_counter_submission_geo_daily (load_id, context_id, submission_id, country, region, city, date, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique) SELECT m.load_id, m.context_id, m.submission_id, COALESCE(m.country_id, ''), COALESCE(m.region, ''), COALESCE(m.city, ''), DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d') as mday, SUM(m.metric), 0, 0, 0 FROM metrics m WHERE (m.assoc_type = 515 OR m.assoc_type = 531 OR m.assoc_type = 1048585) AND m.metric_type = 'ojs::counter' GROUP BY m.load_id, m.context_id, m.submission_id, m.country_id, m.region, m.city, mday");
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            DB::statement("UPDATE metrics_counter_submission_geo_daily mg SET mg.metric_requests = r.msum (SELECT m.load_id, m.context_id, m.submission_id, COALESCE(m.country_id, '') as country_id, COALESCE(m.region, '') as region, COALESCE(m.city, '') as city, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d') as mday, SUM(m.metric) as msum FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter' GROUP BY m.load_id, m.context_id, m.submission_id, m.country_id, m.region, m.city, mday) AS r WHERE mg.load_id = r.load_id AND mg.context_id = r.context_id AND mg.submission_id = r.submission_id AND COALESCE(mg.country, 0) = COALESCE(r.country_id, 0) AND COALESCE(mg.region, 0) = COALESCE(r.region, 0) AND COALESCE(mg.city, 0) = COALESCE(r.city, 0) AND mg.date = r.mday");
        } else {
            DB::statement("UPDATE metrics_counter_submission_geo_daily mg, (SELECT m.load_id, m.context_id, m.submission_id, COALESCE(m.country_id, '') as country_id, COALESCE(m.region, '') as region, COALESCE(m.city, '') as city, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d') as mday, SUM(m.metric) as msum FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter' GROUP BY m.load_id, m.context_id, m.submission_id, m.country_id, m.region, m.city, mday) AS r SET mg.metric_requests = r.msum WHERE mg.load_id = r.load_id AND mg.context_id = r.context_id AND mg.submission_id = r.submission_id AND COALESCE(mg.country, 0) = COALESCE(r.country_id, 0) AND COALESCE(mg.region, 0) = COALESCE(r.region, 0) AND COALESCE(mg.city, 0) = COALESCE(r.city, 0) AND mg.date = r.mday");
        }

        DB::statement("INSERT INTO metrics_counter_submission_geo_monthly (context_id, submission_id, country, region, city, month, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique) SELECT m.context_id, m.submission_id, COALESCE(m.country_id, ''), COALESCE(m.region, ''), COALESCE(m.city, ''), m.month, SUM(m.metric), 0, 0, 0 FROM metrics m WHERE m.assoc_type = 515 OR m.assoc_type = 531 OR m.assoc_type = 1048585 AND m.metric_type = 'ojs::counter' GROUP BY m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month");
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            DB::statement("UPDATE metrics_counter_submission_geo_monthly mg SET mg.metric_requests = r.msum (SELECT m.context_id, m.submission_id, COALESCE(m.country_id, '') as country_id,  COALESCE(m.region, '') as region, COALESCE(m.city, '') as city, m.month, SUM(m.metric) as msum FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter' GROUP BY m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month) AS r WHERE mg.context_id = r.context_id AND mg.submission_id = r.submission_id AND COALESCE(mg.country, 0) = COALESCE(r.country_id, 0) AND COALESCE(mg.region, 0) = COALESCE(r.region, 0) AND COALESCE(mg.city, 0) = COALESCE(r.city, 0) AND mg.month = r.month");
        } else {
            DB::statement("UPDATE metrics_counter_submission_geo_monthly mg, (SELECT m.context_id, m.submission_id, COALESCE(m.country_id, '') as country_id, COALESCE(m.region, '') as region, COALESCE(m.city, '') as city, m.month, SUM(m.metric) as msum FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter' GROUP BY m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month) AS r SET mg.metric_requests = r.msum WHERE mg.context_id = r.context_id AND mg.submission_id = r.submission_id AND COALESCE(mg.country, 0) = COALESCE(r.country_id, 0) AND COALESCE(mg.region, 0) = COALESCE(r.region, 0) AND COALESCE(mg.city, 0) = COALESCE(r.city, 0) AND mg.month = r.month");
        }
        //}
        */

        /*
        // Delete the entries with the metric type ojs::counter from the DB table metrics -> they were migrated above
        if (Schema::hasTable('metrics')) {
            DB::statement("DELETE FROM metrics WHERE metric_type = 'ojs::counter'");
            if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
                DB::statement("ALTER TABLE metrics RENAME TO metrics_old;");
            } else {
                DB::statement("ALTER TABLE metrics RENAME metrics_old;");
            }
        }
        // Deletethe the old usage_stats_temporary_records table
        if (Schema::hasTable('usage_stats_temporary_records')) {
            Schema::drop('usage_stats_temporary_records');
        }
        */
    }

    /**
     * Reverse the downgrades
     */
    public function down()
    {
        // We don't have the data to downgrade and downgrades are unwanted here anyway.
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I6782_Metrics', '\I6782_Metrics');
}
