<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_OrphanedMetrics.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_OrphanedMetrics
 * @brief Migrate usage stats settings, and data from the old DB table metrics into the new DB tables.
 */

namespace APP\migration\upgrade\v3_4_0;

use APP\core\Application;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I6782_OrphanedMetrics extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        $this->createMetricsTmpTable();

        // Metrics issue IDs
        // as m.assoc_id
        $orphanedIds = DB::table('metrics AS m')->leftJoin('issues AS i', 'm.assoc_id', '=', 'i.issue_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_ISSUE)->whereNull('i.issue_id')->distinct()->pluck('m.assoc_id');
        $orphandedIssues = DB::table('metrics')->select('*')->where('assoc_type', '=', Application::ASSOC_TYPE_ISSUE)->whereIn('assoc_id', $orphanedIds)->get();
        foreach ($orphandedIssues as $orphandedIssue) {
            DB::table('metrics_tmp AS mt')->insert(get_object_vars($orphandedIssue));
        }
        DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_ISSUE)->whereIn('assoc_id', $orphanedIds)->delete();

        // Clean orphaned metrics issue galley IDs
        $orphanedIds = DB::table('metrics AS m')->leftJoin('issue_galleys AS ig', 'm.assoc_id', '=', 'ig.galley_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_ISSUE_GALLEY)->whereNull('ig.galley_id')->distinct()->pluck('m.assoc_id');
        $orphandedIssuesGalleys = DB::table('metrics')->select('*')->where('assoc_type', '=', Application::ASSOC_TYPE_ISSUE_GALLEY)->whereIn('assoc_id', $orphanedIds)->get();
        foreach ($orphandedIssuesGalleys as $orphandedIssuesGalley) {
            DB::table('metrics_tmp AS mt')->insert(get_object_vars($orphandedIssuesGalley));
        }
        DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_ISSUE_GALLEY)->whereIn('assoc_id', $orphanedIds)->delete();

        // Clean orphaned metrics context IDs. These IDs can be deleted.
        // as assoc_id
        $orphanedIds = DB::table('metrics AS m')->leftJoin('journals AS c', 'm.assoc_id', '=', 'c.journal_id')->where('m.assoc_type', '=', Application::getContextAssocType())->whereNull('c.journal_id')->distinct()->pluck('m.assoc_id');
        foreach ($orphanedIds as $contextId) {
            $this->_installer->log("Removing stats for journal {$contextId} because no journal with that ID could be found.");
            DB::table('metrics')->where('assoc_type', '=', Application::getContextAssocType())->where('assoc_id', '=', $contextId)->delete();
        }
        // as context_id
        $orphanedIds = DB::table('metrics AS m')->leftJoin('journals AS c', 'm.context_id', '=', 'c.journal_id')->whereNull('c.journal_id')->distinct()->pluck('m.context_id');
        foreach ($orphanedIds as $contextId) {
            $this->_installer->log("Removing stats for journal {$contextId} because no journal with that ID could be found.");
            DB::table('metrics')->where('context_id', '=', $contextId)->delete();
        }

        // Clean orphaned metrics submission IDs
        // as submission_id
        $orphanedIds = DB::table('metrics AS m')->leftJoin('submissions AS s', 'm.submission_id', '=', 's.submission_id')->whereNotNull('m.submission_id')->whereNull('s.submission_id')->distinct()->pluck('m.submission_id');
        $orphandedSubmissions = DB::table('metrics')->select('*')->whereIn('submission_id', $orphanedIds)->get();
        foreach ($orphandedSubmissions as $orphandedSubmission) {
            DB::table('metrics_tmp AS mt')->insert(get_object_vars($orphandedSubmission));
        }
        DB::table('metrics')->whereIn('submission_id', $orphanedIds)->delete();
        // as assoc_id
        $orphanedIds = DB::table('metrics AS m')->leftJoin('submissions AS s', 'm.assoc_id', '=', 's.submission_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION)->whereNull('s.submission_id')->distinct()->pluck('m.assoc_id');
        $orphandedSubmissionsAssocId = DB::table('metrics')->select('*')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION)->whereIn('assoc_id', $orphanedIds)->get();
        foreach ($orphandedSubmissionsAssocId as $orphandedSubmission) {
            DB::table('metrics_tmp AS mt')->insert(get_object_vars($orphandedSubmission));
        }
        DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION)->whereIn('assoc_id', $orphanedIds)->delete();

        // Clean orphaned metrics submission file IDs
        $orphanedIds = DB::table('metrics AS m')->leftJoin('submission_files AS sf', 'm.assoc_id', '=', 'sf.submission_file_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE)->whereNull('sf.submission_file_id')->distinct()->pluck('m.assoc_id');
        $orphandedSubmissionFiles = DB::table('metrics')->select('*')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE)->whereIn('assoc_id', $orphanedIds)->get();
        foreach ($orphandedSubmissionFiles as $orphandedSubmissionFile) {
            DB::table('metrics_tmp AS mt')->insert(get_object_vars($orphandedSubmissionFile));
        }
        DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE)->whereIn('assoc_id', $orphanedIds)->delete();

        // Clean orphaned metrics submission supp file IDs
        $orphanedIds = DB::table('metrics AS m')->leftJoin('submission_files AS sf', 'm.assoc_id', '=', 'sf.submission_file_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER)->whereNull('sf.submission_file_id')->distinct()->pluck('m.assoc_id');
        $orphandedSubmissionSuppFiles = DB::table('metrics')->select('*')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER)->whereIn('assoc_id', $orphanedIds)->get();
        foreach ($orphandedSubmissionSuppFiles as $orphandedSubmissionSuppFile) {
            DB::table('metrics_tmp AS mt')->insert(get_object_vars($orphandedSubmissionSuppFile));
        }
        DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER)->whereIn('assoc_id', $orphanedIds)->delete();

        // Clean orphaned metrics representation IDs
        $orphanedIds = DB::table('metrics AS m')->leftJoin('publication_galleys AS r', 'm.representation_id', '=', 'r.galley_id')->whereNotNull('m.representation_id')->whereNull('r.galley_id')->distinct()->pluck('m.representation_id');
        $orphandedRepresentations = DB::table('metrics')->select('*')->whereIn('representation_id', $orphanedIds)->get();
        foreach ($orphandedRepresentations as $orphandedRepresentation) {
            DB::table('metrics_tmp AS mt')->insert(get_object_vars($orphandedRepresentation));
        }
        DB::table('metrics')->whereIn('representation_id', $orphanedIds)->delete();
    }

    public function createMetricsTmpTable()
    {
        Schema::create('metrics_tmp', function (Blueprint $table) {
            $table->string('load_id', 255);
            $table->bigInteger('context_id');
            $table->bigInteger('pkp_section_id')->nullable();
            $table->bigInteger('assoc_object_type')->nullable();
            $table->bigInteger('assoc_object_id')->nullable();
            $table->bigInteger('submission_id')->nullable();
            $table->bigInteger('representation_id')->nullable();
            $table->bigInteger('assoc_type');
            $table->bigInteger('assoc_id');
            $table->string('day', 8)->nullable();
            $table->string('month', 6)->nullable();
            $table->smallInteger('file_type')->nullable();
            $table->string('country_id', 2)->nullable();
            $table->string('region', 2)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('metric_type', 255);
            $table->integer('metric');
            $table->index(['load_id'], 'metrics_tmp_load_id');
            $table->index(['metric_type', 'context_id'], 'metrics_tmp_metric_type_context_id');
            $table->index(['metric_type', 'submission_id', 'assoc_type'], 'metrics_tmp_metric_type_submission_id_assoc_type');
            $table->index(['metric_type', 'context_id', 'assoc_type', 'assoc_id'], 'metrics_tmp_metric_type_submission_id_assoc');
        });
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
