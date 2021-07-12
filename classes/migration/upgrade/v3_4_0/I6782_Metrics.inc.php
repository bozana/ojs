<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_Metrics.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_Metrics
 * @brief Migrate data from the old DB table metrics into the new DB tables.
 */

namespace APP\migration\upgrade\v3_4_0;

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
        if (!Schema::hasTable('metrics_context')) {
            Schema::create('metrics_context', function (Blueprint $table) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->dateTime('date', $precision = 0);
                $table->integer('metric');
                $table->index(['load_id'], 'metrics_context_load_id');
            });
        }
        if (!Schema::hasTable('metrics_issue')) {
            Schema::create('metrics_issue', function (Blueprint $table) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('issue_id');
                $table->bigInteger('issue_galley_id')->nullable();
                $table->dateTime('date', $precision = 0);
                $table->integer('metric');
                $table->index(['load_id'], 'metrics_issue_load_id');
            });
        }
        if (!Schema::hasTable('metrics_submission')) {
            Schema::create('metrics_submission', function (Blueprint $table) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->bigInteger('representation_id')->nullable();
                $table->bigInteger('file_id')->nullable();
                $table->bigInteger('file_type')->nullable();
                $table->dateTime('date', $precision = 0);
                $table->integer('metric');
                $table->index(['load_id'], 'metrics_submission_load_id');
            });
        }
        if (!Schema::hasTable('metrics_counter_submission_daily')) {
            Schema::create('metrics_counter_submission_daily', function (Blueprint $table) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->dateTime('date', $precision = 0);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->index(['load_id'], 'metrics_counter_submission_daily_load_id');
            });
        }
        if (!Schema::hasTable('metrics_counter_submission_monthly')) {
            Schema::create('metrics_counter_submission_monthly', function (Blueprint $table) {
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->string('month', 6);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
            });
        }
        if (!Schema::hasTable('metrics_counter_submission_institution_daily')) {
            Schema::create('metrics_counter_submission_institution_daily', function (Blueprint $table) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->bigInteger('institution_id');
                $table->dateTime('date', $precision = 0);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->index(['load_id'], 'metrics_counter_submission_institution_daily_load_id');
            });
        }
        if (!Schema::hasTable('metrics_counter_submission_institution_monthly')) {
            Schema::create('metrics_counter_submission_institution_monthly', function (Blueprint $table) {
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->bigInteger('institution_id');
                $table->string('month', 6);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
            });
        }
        if (!Schema::hasTable('metrics_counter_submission_geo_daily')) {
            Schema::create('metrics_counter_submission_geo_daily', function (Blueprint $table) {
                $table->string('load_id', 255);
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->string('country', 2)->nullable();
                $table->string('region', 3)->nullable();
                $table->string('city', 255)->nullable();
                $table->dateTime('date', $precision = 0);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
                $table->index(['load_id'], 'metrics_counter_submission_geo_daily_load_id');
            });
        }
        if (!Schema::hasTable('metrics_counter_submission_geo_monthly')) {
            Schema::create('metrics_counter_submission_geo_monthly', function (Blueprint $table) {
                $table->bigInteger('context_id');
                $table->bigInteger('submission_id');
                $table->string('country', 2)->nullable();
                $table->string('region', 3)->nullable();
                $table->string('city', 255)->nullable();
                $table->string('month', 6);
                $table->integer('metric_investigations');
                $table->integer('metric_investigations_unique');
                $table->integer('metric_requests');
                $table->integer('metric_requests_unique');
            });
        }

        // Requires that new DB metrics tables are already there
        DB::statement("INSERT INTO metrics_context (load_id, context_id, date, metric) SELECT m.load_id, m.assoc_id, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d %H:%i:%s'), m.metric FROM metrics m WHERE m.assoc_type = 256 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT INTO metrics_issue (load_id, context_id, issue_id, issue_galley_id, date, metric) SELECT m.load_id, m.context_id, m.assoc_id, null, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d %H:%i:%s'), m.metric FROM metrics m WHERE m.assoc_type = 259 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT INTO metrics_issue (load_id, context_id, issue_id, issue_galley_id, date, metric) SELECT m.load_id, m.context_id, m.assoc_object_id, m.assoc_id, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d %H:%i:%s'), m.metric FROM metrics m WHERE m.assoc_type = 261 AND m.assoc_object_type = 259 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, file_id, file_type, date, metric) SELECT m.load_id, m.context_id, m.assoc_id, null, null, null, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d %H:%i:%s'), m.metric FROM metrics m WHERE m.assoc_type = 1048585 AND m.metric_type = 'ojs::counter'");
        DB::statement("INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, file_id, file_type, date, metric) SELECT m.load_id, m.context_id, m.submission_id, m.representation_id, m.assoc_id, m.file_type, DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d %H:%i:%s'), m.metric FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter'");

        DB::statement("INSERT INTO metrics_counter_submission_geo_monthly (context_id, submission_id, country, region, city, month, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique) SELECT m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month, SUM(m.metric), 0, 0, 0 FROM metrics m WHERE m.assoc_type = 515 OR m.assoc_type = 1048585 AND m.metric_type = 'ojs::counter' GROUP BY m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month");
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            DB::statement("UPDATE metrics_counter_submission_geo_monthly mg SET mg.metric_requests = r.msum (SELECT m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month, SUM(m.metric) as msum FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter' GROUP BY m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month) AS r WHERE mg.context_id = r.context_id AND mg.submission_id = r.submission_id AND COALESCE(mg.country, 0) = COALESCE(r.country_id, 0) AND COALESCE(mg.region, 0) = COALESCE(r.region, 0) AND COALESCE(mg.city, 0) = COALESCE(r.city, 0) AND mg.month = r.month");
        } else {
            DB::statement("UPDATE metrics_counter_submission_geo_monthly mg, (SELECT m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month, SUM(m.metric) as msum FROM metrics m WHERE m.assoc_type = 515 AND m.metric_type = 'ojs::counter' GROUP BY m.context_id, m.submission_id, m.country_id, m.region, m.city, m.month) AS r SET mg.metric_requests = r.msum WHERE mg.context_id = r.context_id AND mg.submission_id = r.submission_id AND COALESCE(mg.country, 0) = COALESCE(r.country_id, 0) AND COALESCE(mg.region, 0) = COALESCE(r.region, 0) AND COALESCE(mg.city, 0) = COALESCE(r.city, 0) AND mg.month = r.month");
        }

        // Drop the DB table metrics
        /*
        if (Schema::hasTable('metrics')) {
            Schema::drop('metrics');
        }
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
