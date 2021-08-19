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
use PKP\core\Core;
use PKP\plugins\PluginRegistry;

class I6782_Metrics extends Migration
{
    /**
     * Run the migration.
     */
    public function up()
    {
        // Read old usage stats settings
        // ??? createLogFiles
        // ??? enableInstitutionUsageStats
        // Geo data stats settings
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
        // Compress archives settings
        $compressArchives = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'compressArchives')
            ->value('setting_value');
        // Migrate site settings
        DB::table('site_settings')->insertOrIgnore([
            ['setting_name' => 'archivedUsageStatsLogFiles', 'setting_value' => $compressArchives],
            ['setting_name' => 'enableGeoUsageStats', 'setting_value' => $enableGeoUsageStats],
            ['setting_name' => 'geoUsageStatsKeepDaily', 'setting_value' => $geoUsageStatsKeepDaily]
        ]);

        // Display site settings
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
        // Migrate usage stats site display settings to the active site theme
        $siteThemePlugins = PluginRegistry::getPlugins('themes');
        $activeSiteTheme = null;
        foreach ($siteThemePlugins as $siteThemePlugin) {
            if ($siteThemePlugin->isActive()) {
                $activeSiteTheme = $siteThemePlugin;
                break;
            }
        }
        if (isset($activeSiteTheme)) {
            $siteUsageStatsDisplay = !$displayStatistics ? 'none' : $chartType;
            DB::table('plugin_settings')->insertOrIgnore([
                ['plugin_name' => $activeSiteTheme->getName(), 'context_id' => 0, 'setting_name' => 'usageStatsDisplay', 'setting_value' => $siteUsageStatsDisplay],
            ]);
        }

        // Migrate context settings
        $contextIds = Services::get('context')->getIds([
            'isEnabled' => true, // only enabled contexts?
        ]);
        foreach ($contextIds as $contextId) {
            $contextDisplayStatistics = $contextChartType = null;
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
            // Migrate usage stats display settings to the active context theme
            $contextThemePlugins = PluginRegistry::loadCategory('themes', true, $contextId);
            $activeContextTheme = null;
            foreach ($contextThemePlugins as $contextThemePlugin) {
                if ($contextThemePlugin->isActive()) {
                    $activeContextTheme = $contextThemePlugin;
                    break;
                }
            }
            if (isset($activeContextTheme)) {
                $contextUsageStatsDisplay = !$contextDisplayStatistics ? 'none' : $contextChartType;
                DB::table('plugin_settings')->insertOrIgnore([
                    ['plugin_name' => $activeContextTheme->getName(), 'context_id' => $contextId, 'setting_name' => 'usageStatsDisplay', 'setting_value' => $contextUsageStatsDisplay],
                ]);
            }
        }

        $dayFormatSql = "DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d')";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $dayFormatSql = "to_date(m.day, 'YYYYMMDD')";
        }
        // Requires the new DB metrics tables
        // The not existing foreign keys should already be removed in PreflightCheckStatsMigration
        // Migrate context metrics
        DB::statement("INSERT INTO metrics_context (load_id, context_id, date, metric) SELECT m.load_id, m.assoc_id, {$dayFormatSql}, m.metric FROM metrics m WHERE m.assoc_type = 256 AND m.metric_type = 'ojs::counter'");
        // Migrate issue metrics; consider issue TOCs and galley files
        DB::statement("INSERT INTO metrics_issue (load_id, context_id, issue_id, issue_galley_id, date, metric) SELECT m.load_id, m.context_id, m.assoc_id, null, {$dayFormatSql}, m.metric FROM metrics m WHERE m.assoc_type = 259 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT INTO metrics_issue (load_id, context_id, issue_id, issue_galley_id, date, metric) SELECT m.load_id, m.context_id, m.assoc_object_id, m.assoc_id, {$dayFormatSql}, m.metric FROM metrics m WHERE m.assoc_type = 261 AND m.assoc_object_type = 259 AND m.metric_type = 'ojs::counter'");
        // Migrate submission metrics; consider abstracts, galley and supp files
        DB::statement("INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, submission_file_id, file_type, assoc_type, date, metric) SELECT m.load_id, m.context_id, m.assoc_id, null, null, null, m.assoc_type, {$dayFormatSql}, m.metric FROM metrics m WHERE m.assoc_type = 1048585 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, submission_file_id, file_type, assoc_type, date, metric) SELECT m.load_id, m.context_id, m.submission_id, m.representation_id, m.assoc_id, m.file_type, m.assoc_type, {$dayFormatSql}, m.metric FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, submission_file_id, file_type, assoc_type, date, metric) SELECT m.load_id, m.context_id, m.submission_id, m.representation_id, m.assoc_id, m.file_type, m.assoc_type, {$dayFormatSql}, m.metric FROM metrics m WHERE m.assoc_type = 531 AND m.metric_type = 'ojs::counter'");

        // Migrate Geo metrics -- no matter if the Geo usage stats are currently enabled
        // fix wrong entries in the DB table metrics
        // do all this first in order for groupBy to function properly
        DB::table('metrics')->where('city', '')->update(['city' => null]);
        DB::table('metrics')->where('region', '')->orWhere('region', '0')->update(['region' => null]);
        DB::table('metrics')->where('country_id', '')->update(['country_id' => null]);
        // in the GeoIP Legacy databases, several country codes were included that don't represent countries
        DB::table('metrics')->whereIn('country_id', ['AP', 'EU', 'A1', 'A2'])->update(['country_id' => null, 'region' => null, 'city' => null]);
        // some regions are missing the leading '0'
        DB::table('metrics')->update(['region' => DB::raw("LPAD(region, 2, '0')")]);

        // insert into daily table
        // metric_investigations
        DB::statement("
            INSERT INTO metrics_counter_submission_geo_daily (load_id, context_id, submission_id, country, region, city, date, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
            SELECT m.load_id, m.context_id, m.submission_id, COALESCE(m.country_id, ''), COALESCE(m.region, ''), COALESCE(m.city, ''), {$dayFormatSql} as mday, SUM(m.metric), 0, 0, 0
            FROM metrics m
            WHERE m.assoc_type IN (515, 531, 1048585) AND m.metric_type = 'ojs::counter' AND (m.country_id IS NOT NULL OR m.region IS NOT NULL OR m.city IS NOT NULL)
            GROUP BY m.load_id, m.context_id, m.submission_id, m.country_id, m.region, m.city, mday
        ");
        // metric_requests
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            DB::statement("
                UPDATE metrics_counter_submission_geo_daily mg
                SET metric_requests = r.msum
                FROM (SELECT m.load_id, m.context_id, m.submission_id, COALESCE(m.country_id, '') as country_id, COALESCE(m.region, '') as region, COALESCE(m.city, '') as city, {$dayFormatSql} as mday, SUM(m.metric) as msum
                    FROM metrics m
                    WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter' AND (m.country_id IS NOT NULL OR m.region IS NOT NULL OR m.city IS NOT NULL)
                    GROUP BY m.load_id, m.context_id, m.submission_id, m.country_id, m.region, m.city, mday) AS r
                WHERE mg.load_id = r.load_id AND mg.context_id = r.context_id AND mg.submission_id = r.submission_id AND mg.country = r.country_id AND mg.region = r.region AND mg.city = r.city AND mg.date = r.mday
            ");
        } else {
            DB::statement("
                UPDATE metrics_counter_submission_geo_daily mg, (
                    SELECT m.load_id, m.context_id, m.submission_id, COALESCE(m.country_id, '') as country_id, COALESCE(m.region, '') as region, COALESCE(m.city, '') as city, {$dayFormatSql} as mday, SUM(m.metric) as msum
                    FROM metrics m
                    WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter' AND (m.country_id IS NOT NULL OR m.region IS NOT NULL OR m.city IS NOT NULL)
                    GROUP BY m.load_id, m.context_id, m.submission_id, m.country_id, m.region, m.city, mday) AS r
                SET mg.metric_requests = r.msum
                WHERE mg.load_id = r.load_id AND mg.context_id = r.context_id AND mg.submission_id = r.submission_id AND mg.country = r.country_id AND mg.region = r.region AND mg.city = r.city AND mg.date = r.mday
            ");
        }

        // migrate region FIPS to ISO, s. https://dev.maxmind.com/geoip/whats-new-in-geoip2?lang=en
        // create a temporary table for the FIPS-ISO mapping
        if (!Schema::hasTable('region_mapping_tmp')) {
            Schema::create('region_mapping_tmp', function (Blueprint $table) {
                $table->string('country', 2);
                $table->string('fips', 3);
                $table->string('iso', 3)->nullable();
            });
            // read the FIPS to ISO mappings and isert them into the temporary table
            $mappings = include Core::getBaseDir() . DIRECTORY_SEPARATOR . PKP_LIB_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'regionMapping.php';
            foreach ($mappings as $country => $regionMapping) {
                foreach ($regionMapping as $fips => $iso) {
                    DB::table('region_mapping_tmp')->insert([
                        'country' => $country,
                        'fips' => $fips,
                        'iso' => $iso
                    ]);
                }
            }
        }
        // temporary create index on the column country and region, in order to be able to update the region codes in a reasonable time
        Schema::table('metrics_counter_submission_geo_daily', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('metrics_counter_submission_geo_daily');
            if (!array_key_exists('metrics_counter_submission_geo_daily_tmp_index', $indexesFound)) {
                $table->index(['country', 'region'], 'metrics_counter_submission_geo_daily_tmp_index');
            }
        });
        // update region code from FIPS to ISP
        // Laravel join+update does not work well with PostgreSQL, so use the direct SQLs
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            DB::statement('
                UPDATE metrics_counter_submission_geo_daily AS gd
                SET region = rm.iso
                FROM region_mapping_tmp AS rm
                WHERE gd.country = rm.country AND gd.region = rm.fips
            ');
        } else {
            DB::statement('
                UPDATE metrics_counter_submission_geo_daily gd
                INNER JOIN region_mapping_tmp rm ON (rm.country = gd.country AND rm.fips = gd.region)
                SET gd.region = rm.iso
            ');
        }
        // drop the temporary index
        Schema::table('metrics_counter_submission_geo_daily', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('metrics_counter_submission_geo_daily');
            if (array_key_exists('metrics_counter_submission_geo_daily_tmp_index', $indexesFound)) {
                $table->dropIndex(['tmp']);
            }
        });

        // Migrate to monthly table
        $monthFormatSql = "DATE_FORMAT(STR_TO_DATE(gd.date, '%Y-%m-%d'), '%Y%m')";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $monthFormatSql = "to_char(gd.date, 'YYYYMM')";
        }
        // use the metrics_counter_submission_geo_monthly instead of meetrics to calculate the monthly numbers
        DB::statement("
            INSERT INTO metrics_counter_submission_geo_monthly (context_id, submission_id, country, region, city, month, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
            SELECT gd.context_id, gd.submission_id, gd.country, gd.region, gd.city, {$monthFormatSql} as month, SUM(gd.metric_investigations), SUM(gd.metric_investigations_unique), SUM(gd.metric_requests), SUM(gd.metric_requests_unique)
            FROM metrics_counter_submission_geo_daily gd
            GROUP BY gd.context_id, gd.submission_id, gd.country, gd.region, gd.city, month
        ");

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
