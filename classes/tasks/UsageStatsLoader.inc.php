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
use PKP\task\PKPUsageStatsLoader;

class UsageStatsLoader extends PKPUsageStatsLoader
{
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
