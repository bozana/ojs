<?php

/**
 * @file Jobs/Statistics/LoadMetricsDataJob.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LoadMetricsDataJob
 * @ingroup jobs
 *
 * @brief Class to handle the usage metrics data loading as a Job
 */

namespace APP\Jobs\Statistics;

use APP\statistics\StatisticsHelper;
use PKP\db\DAORegistry;
use PKP\Domains\Jobs\Exceptions\JobException;
use PKP\Support\Jobs\BaseJob;
use PKP\task\FileLoader;

class LoadMetricsDataJob extends BaseJob
{
    /**
     * The load ID = usage stats log file name
     */
    protected string $loadId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $loadId)
    {
        parent::__construct();
        $this->loadId = $loadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $loadSuccessful = $this->_loadData();
        if (!$loadSuccessful) {
            // Move the archived file back to staging
            $filename = $this->loadId;
            $archivedFilePath = StatisticsHelper::getUsageStatsDirPath() . DIRECTORY_SEPARATOR . FileLoader::FILE_LOADER_PATH_ARCHIVE . DIRECTORY_SEPARATOR . $filename;
            if (!file_exists($archivedFilePath)) {
                $filename .= '.gz';
                $archivedFilePath = StatisticsHelper::getUsageStatsDirPath() . DIRECTORY_SEPARATOR . FileLoader::FILE_LOADER_PATH_ARCHIVE . DIRECTORY_SEPARATOR . $filename;
            }
            $stagingPath = StatisticsHelper::getUsageStatsDirPath() . DIRECTORY_SEPARATOR . FileLoader::FILE_LOADER_PATH_STAGING . DIRECTORY_SEPARATOR . $filename;

            if (!rename($archivedFilePath, $stagingPath)) {
                $message = __('admin.job.loadMetricsData.returnToStaging.error', ['file' => $filename,
                    'archivedFilePath' => $archivedFilePath, 'stagingPath' => $stagingPath]);
            } else {
                $message = __('admin.job.loadMetricsData.error', ['file' => $filename]);
            }
            $this->failed(new JobException($message));
            return;
        }

        $temporaryTotalsDao = DAORegistry::getDAO('TemporaryTotalsDAO'); /* @var TemporaryTotalsDAO $temporaryTotalsDao */
        $temporaryItemInvestigationsDao = DAORegistry::getDAO('TemporaryItemInvestigationsDAO'); /* @var TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao */
        $temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /* @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */
        $temporaryInstitutionDao = DAORegistry::getDAO('TemporaryInstitutionsDAO'); /* @var TemporaryInstitutionsDAO $temporaryInstitutionDao */

        $temporaryTotalsDao->deleteByLoadId($this->loadId);
        $temporaryItemInvestigationsDao->deleteByLoadId($this->loadId);
        $temporaryItemRequestsDao->deleteByLoadId($this->loadId);
        $temporaryInstitutionDao->deleteByLoadId($this->loadId);
    }

    /**
     * Load the entries inside the temporary database associated with
     * the passed load id to the metrics tables.
     */
    private function _loadData(): bool
    {
        $temporaryTotalsDao = DAORegistry::getDAO('TemporaryTotalsDAO'); /* @var TemporaryTotalsDAO $temporaryTotalsDao */
        $temporaryItemInvestigationsDao = DAORegistry::getDAO('TemporaryItemInvestigationsDAO'); /* @var TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao */
        $temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /* @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */

        $temporaryTotalsDao->removeDoubleClicks(StatisticsHelper::COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS);
        $temporaryItemInvestigationsDao->removeUniqueClicks();
        $temporaryItemRequestsDao->removeUniqueClicks();

        $temporaryTotalsDao->loadMetricsContext($this->loadId);
        $temporaryTotalsDao->loadMetricsIssue($this->loadId);
        $temporaryTotalsDao->loadMetricsSubmission($this->loadId);

        // Geo database only contains total and unique investigations (no extra requests differentiation)
        $temporaryTotalsDao->deleteSubmissionGeoDailyByLoadId($this->loadId); // always call first, before loading the data
        $temporaryTotalsDao->loadMetricsSubmissionGeoDaily($this->loadId);
        $temporaryItemInvestigationsDao->loadMetricsSubmissionGeoDaily($this->loadId);

        $temporaryTotalsDao->deleteCounterSubmissionDailyByLoadId($this->loadId); // always call first, before loading the data
        $temporaryTotalsDao->loadMetricsCounterSubmissionDaily($this->loadId);
        $temporaryItemInvestigationsDao->loadMetricsCounterSubmissionDaily($this->loadId);
        $temporaryItemRequestsDao->loadMetricsCounterSubmissionDaily($this->loadId);

        $temporaryTotalsDao->deleteCounterSubmissionInstitutionDailyByLoadId($this->loadId); // always call first, before loading the data
        $temporaryTotalsDao->loadMetricsCounterSubmissionInstitutionDaily($this->loadId);
        $temporaryItemInvestigationsDao->loadMetricsCounterSubmissionInstitutionDaily($this->loadId);
        $temporaryItemRequestsDao->loadMetricsCounterSubmissionInstitutionDaily($this->loadId);

        return true;
    }
}
