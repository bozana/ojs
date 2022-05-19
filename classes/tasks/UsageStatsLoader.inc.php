<?php

/**
 * @file classes/tasks/UsageStatsLoader.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsLoader
 * @ingroup tasks
 *
 * @brief Scheduled task to extract transform and load usage statistics data into database.
 */

namespace APP\tasks;

use APP\core\Application;
use APP\statistics\TemporaryItemInvestigationsDAO;
use APP\statistics\TemporaryItemRequestsDAO;
use APP\statistics\TemporaryTotalsDAO;
use PKP\db\DAORegistry;
use PKP\statistics\TemporaryInstitutionsDAO;
use PKP\task\PKPUsageStatsLoader;

class UsageStatsLoader extends PKPUsageStatsLoader
{
    private TemporaryInstitutionsDAO $temporaryInstitutionsDao;
    private TemporaryTotalsDAO $temporaryTotalsDao;
    private TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao;
    private TemporaryItemRequestsDAO $temporaryItemRequestsDao;

    /**
     * Constructor.
     */
    public function __construct($args)
    {
        $this->temporaryInstitutionsDao = DAORegistry::getDAO('TemporaryInstitutionsDAO'); /* @var TemporaryInstitutionsDAO $statsInstitutionDao */
        $this->temporaryTotalsDao = DAORegistry::getDAO('TemporaryTotalsDAO'); /* @var TemporaryTotalsDAO $temporaryTotalsDao */
        $this->temporaryItemInvestigationsDao = DAORegistry::getDAO('TemporaryItemInvestigationsDAO'); /* @var TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao */
        $this->temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /* @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */
        parent::__construct($args);
    }

    /**
     * @copydoc PKPUsageStatsLoader::deleteByLoadId()
     */
    protected function deleteByLoadId(string $loadId): void
    {
        $this->temporaryInstitutionsDao->deleteByLoadId($loadId);
        $this->temporaryTotalsDao->deleteByLoadId($loadId);
        $this->temporaryItemInvestigationsDao->deleteByLoadId($loadId);
        $this->temporaryItemRequestsDao->deleteByLoadId($loadId);
    }

    /**
     * @copydoc PKPUsageStatsLoader::insertTemporaryUsageStatsData()
     */
    protected function insertTemporaryUsageStatsData(object $entry, int $lineNumber, string $loadId): void
    {
        $this->temporaryInstitutionsDao->insert($entry->institutionIds, $lineNumber, $loadId);
        $this->temporaryTotalsDao->insert($entry, $lineNumber, $loadId);
        if (!empty($entry->submissionId)) {
            $this->temporaryItemInvestigationsDao->insert($entry, $lineNumber, $loadId);
            if ($entry->assocType == Application::ASSOC_TYPE_SUBMISSION_FILE) {
                $this->temporaryItemRequestsDao->insert($entry, $lineNumber, $loadId);
            }
        }
    }

    /**
     * @copydoc PKPUsageStatsLoader::checkForeignKeys()
     */
    protected function checkForeignKeys(object $entry): array
    {
        return $this->temporaryTotalsDao->checkForeignKeys($entry);
    }

    /**
     * @copydoc PKPUsageStatsLoader::getValidAssocTypes()
     */
    protected function getValidAssocTypes(): array
    {
        return [
            Application::ASSOC_TYPE_SUBMISSION_FILE,
            Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER,
            Application::ASSOC_TYPE_SUBMISSION,
            Application::ASSOC_TYPE_ISSUE_GALLEY,
            Application::ASSOC_TYPE_ISSUE,
            Application::ASSOC_TYPE_JOURNAL,
        ];
    }

    /**
     * @copydoc PKPUsageStatsLoader::isLogEntryValid()
     */
    protected function isLogEntryValid(object $entry): void
    {
        parent::isLogEntryValid($entry);
        if (!empty($entry->issueId)) {
            if (!is_int($entry->issueId)) {
                throw new \Exception(__('admin.scheduledTask.usageStatsLoader.invalidLogEntry.issueId'));
            } else {
                if ($entry->assocType == Application::ASSOC_TYPE_ISSUE && $entry->assocId != $entry->issueId) {
                    throw new \Exception(__('admin.scheduledTask.usageStatsLoader.invalidLogEntry.issueAssocTypeNoMatch'));
                }
            }
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\tasks\UsageStatsLoader', '\UsageStatsLoader');
}
