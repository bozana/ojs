<?php

/**
 * @file classes/migration/upgrade/v3_4_0/PreflightCheckStatsMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PreflightCheckStatsMigration
 * @brief Check for common problems early in the upgrade process.
 */

namespace APP\migration\upgrade\v3_4_0;

use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;

class PreflightCheckStatsMigration extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     *
     * assoc_object_type and assoc_object_id, pkp_section_id will not be considered here, because they are not relevant for the migration
     */
    public function up(): void
    {
        try {
            // Clean orphaned metrics issue IDs
            // as m.assoc_id
            $orphanedIds = DB::table('metrics AS m')->leftJoin('issues AS i', 'm.assoc_id', '=', 'i.issue_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_ISSUE)->whereNull('i.issue_id')->distinct()->pluck('m.assoc_id');
            foreach ($orphanedIds as $issueId) {
                $this->_installer->log("Removing stats for issue {$issueId} because no issue with that ID could be found.");
                DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_ISSUE)->where('assoc_id', '=', $issueId)->delete();
            }
            // Clean orphaned metrics issue galley IDs
            $orphanedIds = DB::table('metrics AS m')->leftJoin('issue_galleys AS ig', 'm.assoc_id', '=', 'ig.galley_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_ISSUE_GALLEY)->whereNull('ig.galley_id')->distinct()->pluck('m.assoc_id');
            foreach ($orphanedIds as $issueGalleyId) {
                $this->_installer->log("Removing stats for issue galley {$issueGalleyId} because no issue galley with that ID could be found.");
                DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_ISSUE_GALLEY)->where('assoc_id', '=', $issueGalleyId)->delete();
            }

            // Clean orphaned metrics context IDs
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
            // as assoc_id
            $orphanedIds = DB::table('metrics AS m')->leftJoin('submissions AS s', 'm.assoc_id', '=', 's.submission_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION)->whereNull('s.submission_id')->distinct()->pluck('m.assoc_id');
            foreach ($orphanedIds as $submissionId) {
                $this->_installer->log("Removing stats for submission {$submissionId} because no submission with that ID could be found.");
                DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION)->where('assoc_id', '=', $submissionId)->delete();
            }
            // as submission_id
            $orphanedIds = DB::table('metrics AS m')->leftJoin('submissions AS s', 'm.submission_id', '=', 's.submission_id')->whereNotNull('m.submission_id')->whereNull('s.submission_id')->distinct()->pluck('m.submission_id');
            foreach ($orphanedIds as $submissionId) {
                $this->_installer->log("Removing stats for submission {$submissionId} because no submission with that ID could be found.");
                DB::table('metrics')->where('submission_id', '=', $submissionId)->delete();
            }

            // Clean orphaned metrics submission file IDs
            $orphanedIds = DB::table('metrics AS m')->leftJoin('submission_files AS sf', 'm.assoc_id', '=', 'sf.submission_file_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE)->whereNull('sf.submission_file_id')->distinct()->pluck('m.assoc_id');
            foreach ($orphanedIds as $submissionFileId) {
                $this->_installer->log("Removing stats for submission file {$submissionFileId} because no submission file with that ID could be found.");
                DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE)->where('assoc_id', '=', $submissionFileId)->delete();
            }
            // Clean orphaned metrics submission supp file IDs
            $orphanedIds = DB::table('metrics AS m')->leftJoin('submission_files AS sf', 'm.assoc_id', '=', 'sf.submission_file_id')->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER)->whereNull('sf.submission_file_id')->distinct()->pluck('m.assoc_id');
            foreach ($orphanedIds as $submissionSuppFileId) {
                $this->_installer->log("Removing stats for submission supplementary file {$submissionSuppFileId} because no submission supplementary file with that ID could be found.");
                DB::table('metrics')->where('assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER)->where('assoc_id', '=', $submissionSuppFileId)->delete();
            }

            // Clean orphaned metrics representation IDs
            $orphanedIds = DB::table('metrics AS m')->leftJoin('publication_galleys AS r', 'm.representation_id', '=', 'r.galley_id')->whereNotNull('m.representation_id')->whereNull('r.galley_id')->distinct()->pluck('m.representation_id');
            foreach ($orphanedIds as $representationId) {
                $this->_installer->log("Removing stats for article galley {$representationId} because no article galley with that ID could be found.");
                DB::table('metrics')->where('representation_id', '=', $representationId)->delete();
            }

            // Inform about probably old and not anymore supported assoc_type in the DB table metrics
            $oldAssocTypes = DB::table('metrics AS m')->whereNotIn('m.assoc_type', [Application::getContextAssocType(), Application::ASSOC_TYPE_ISSUE, Application::ASSOC_TYPE_ISSUE_GALLEY, Application::ASSOC_TYPE_SUBMISSION, Application::ASSOC_TYPE_SUBMISSION_FILE, Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER])->distinct()->pluck('m.assoc_type');
            foreach ($oldAssocTypes as $oldAssocType) {
                $this->_installer->log("The DB table metrics contains old and not anymore supported assoc_type {$oldAssocType} that will be not migrated to new metrics tables.");
                $this->_installer->log("An unrecognized value was found in the assoc_type column of the metrics. The unrecognized value was {$oldAssocType}. These statistics will not be included in statistics after the upgrade is complete.");
            }
        } catch (\Exception $e) {
            if ($fallbackVersion = $this->setFallbackVersion()) {
                $this->_installer->log("A pre-flight check failed. The software was successfully upgraded to {$fallbackVersion} but could not be upgraded further (to " . $this->_installer->newVersion->getVersionString() . '). Check and correct the error, then try again.');
            }
            throw ($e);
        }
    }

    public function down(): void
    {
        if ($fallbackVersion = $this->setFallbackVersion()) {
            $this->_installer->log("An upgrade step failed! Fallback set to {$fallbackVersion}. Check and correct the error and try the upgrade again. We recommend restoring from backup, though you may be able to continue without doing so.");
            // Prevent further downgrade migrations from executing.
            $this->_installer->migrations = [];
        }
    }

    /**
     * Store the fallback version in the database, permitting resumption of partial upgrades.
     *
     * @return ?string Fallback version, if one was identified
     */
    protected function setFallbackVersion(): ?string
    {
        if ($fallbackVersion = $this->_attributes['fallback'] ?? null) {
            $versionDao = DAORegistry::getDAO('VersionDAO'); /** @var VersionDAO $versionDao */
            $versionDao->insertVersion(\PKP\site\Version::fromString($fallbackVersion));
            return $fallbackVersion;
        }
        return null;
    }
}
