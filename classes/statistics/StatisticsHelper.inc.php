<?php

/**
* @file classes/statistics/StatisticsHelper.inc.php
*
* Copyright (c) 2013-2021 Simon Fraser University
* Copyright (c) 2003-2021 John Willinsky
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
*
* @class StatisticsHelper
* @ingroup statistics
*
* @brief Statistics helper class.
*/

namespace APP\statistics;

use APP\core\Application;
use APP\i18n\AppLocale;

use PKP\statistics\PKPStatisticsHelper;

class StatisticsHelper extends PKPStatisticsHelper
{
    public const STATISTICS_DIMENSION_ISSUE_GALLEY_ID = 'issue_galley_id';
    public const STATISTICS_DIMENSION_ISSUE_ID = 'issue_id';

    /**
     * @see PKPStatisticsHelper::getReportObjectTypesArray()
     */
    protected function getReportObjectTypesArray()
    {
        $objectTypes = parent::getReportObjectTypesArray();
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
        $objectTypes = $objectTypes + [
            Application::ASSOC_TYPE_JOURNAL => __('context.context'),
            Application::ASSOC_TYPE_SECTION => __('section.section'),
            Application::ASSOC_TYPE_ISSUE => __('issue.issue'),
            Application::ASSOC_TYPE_ISSUE_GALLEY => __('editor.issues.galley'),
            Application::ASSOC_TYPE_SUBMISSION => __('common.publication'),
            Application::ASSOC_TYPE_SUBMISSION_FILE => __('submission.galleyFiles'),
            Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER => __('submission.supplementary'), // __('article.suppFile')
        ];
        return $objectTypes;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\statistics\StatisticsHelper', '\StatisticsHelper');
    define('STATISTICS_DIMENSION_ISSUE_ID', \StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID);
}
