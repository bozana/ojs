<?php

/**
 * @file classes/services/StatsIssueService.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssueService
 * @ingroup services
 *
 * @brief Helper class that encapsulates issue statistics business logic
 */

namespace APP\services;

use APP\core\Application;
use APP\statistics\StatisticsHelper;
use PKP\plugins\HookRegistry;
use PKP\services\PKPStatsService;
use stdClass;

class StatsIssueService extends PKPStatsService
{
    /**
     * A callback to be used with array_map() to return all
     * issue IDs from the records.
     */
    public function filterIssueIds(stdClass $record): int
    {
        return $record->issue_id;
    }

    /**
     * A callback to be used with array_filter() to return records for
     * the TOC views.
     */
    public function filterRecordTOC(stdClass $record): bool
    {
        return $record->assoc_type == Application::ASSOC_TYPE_ISSUE;
    }

    /**
     * A callback to be used with array_filter() to return records for
     * the issue galley views.
     */
    public function filterRecordIssueGalley(stdClass $record): bool
    {
        return $record->assoc_type == Application::ASSOC_TYPE_ISSUE_GALLEY;
    }

    /**
     * Get columns used by this service,
     * to get issue metrics.
     */
    public function getStatsColumns(): array
    {
        return [
            StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID,
            StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID,
            StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID,
            StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE,
            StatisticsHelper::STATISTICS_DIMENSION_MONTH,
            StatisticsHelper::STATISTICS_DIMENSION_DAY,
        ];
    }

    public function getTotalCount(array $args): int
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = array_merge($defaultArgs, $args);
        $metricsQB = $this->getQueryBuilder($args);

        HookRegistry::call('StatsIssue::getTotalCount::queryBuilder', [&$metricsQB, $args]);

        $groupBy = [StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID];
        $metricsQB = $metricsQB->getSum($groupBy);

        return $metricsQB->get()->count();
    }

    public function getTotalMetrics(array $args): array
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = array_merge($defaultArgs, $args);
        $metricsQB = $this->getQueryBuilder($args);

        HookRegistry::call('StatsIssue::getTotalMetrics::queryBuilder', [&$metricsQB, $args]);

        $groupBy = [StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID];
        $metricsQB = $metricsQB->getSum($groupBy);

        $args['orderDirection'] === StatisticsHelper::STATISTICS_ORDER_ASC ? 'asc' : 'desc';
        $metricsQB->orderBy(StatisticsHelper::STATISTICS_METRIC, $args['orderDirection']);

        if (isset($args['count'])) {
            $metricsQB->limit($args['count']);
            if (isset($args['offset'])) {
                $metricsQB->offset($args['offset']);
            }
        }

        return $metricsQB->get()->toArray();
    }

    public function getMetricsByType(array $args): array
    {
        $defaultArgs = $this->getDefaultArgs();
        $args = array_merge($defaultArgs, $args);
        $metricsQB = $this->getQueryBuilder($args);

        HookRegistry::call('StatsIssue::getMetricsByType::queryBuilder', [&$metricsQB, $args]);

        // get toc and galley views for the issue
        $groupBy = [StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE];
        $metricsQB = $metricsQB->getSum($groupBy);
        return $metricsQB->get()->toArray();
    }

    /**
     * @copydoc PKPStatsServie::prepareStatsArgs()
     */
    public function prepareStatsArgs(array $filters = []): array
    {
        $args = [];
        $validColumns = $this->getStatsColumns();
        foreach ($filters as $filterColumn => $value) {
            if (!in_array($filterColumn, $validColumns)) {
                continue;
            }
            switch ($filterColumn) {
                case StatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID:
                    $args['contextIds'] = $value;
                    break;
                case StatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE:
                    $args['assocTypes'] = $value;
                    break;
                case StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID:
                    $args['issueIds'] = $value;
                    break;
                case StatisticsHelper::STATISTICS_DIMENSION_ISSUE_GALLEY_ID:
                    $args['issueGalleyIds'] = $value;
                    break;
                case StatisticsHelper::STATISTICS_DIMENSION_MONTH:
                case StatisticsHelper::STATISTICS_DIMENSION_DAY:
                    $args['timeInterval'] = $filterColumn;
                    if (isset($value['from'])) {
                        $args['dateStart'] = $value['from'];
                    }
                    if (isset($value['to'])) {
                        $args['dateEnd'] = $value['to'];
                    }
                    break;
            }
        }
        return $args;
    }

    /**
     * @copydoc PKPStatsService::getDefaultArgs()
     */
    public function getDefaultArgs(): array
    {
        return [
            'dateStart' => StatisticsHelper::STATISTICS_EARLIEST_DATE,
            'dateEnd' => date('Y-m-d', strtotime('yesterday')),

            // Require a context to be specified to prevent unwanted data leakage
            // if someone forgets to specify the context. If you really want to
            // get data across all contexts, pass an empty `contextId` arg.
            'contextIds' => [\PKP\core\PKPApplication::CONTEXT_ID_NONE],
        ];
    }

    /**
     * Get a QueryBuilder object with the passed args
     *
     * @param array $args See self::prepareStatsArgs()
     *
     */
    public function getQueryBuilder(array $args = []): \APP\services\queryBuilders\StatsIssueQueryBuilder
    {
        $statsQB = new \APP\services\queryBuilders\StatsIssueQueryBuilder();
        $statsQB
            ->filterByContexts($args['contextIds'])
            ->before($args['dateEnd'])
            ->after($args['dateStart']);

        if (!empty(($args['issueIds']))) {
            $statsQB->filterByIssues($args['issueIds']);
        }

        if (!empty(($args['issueGalleyIds']))) {
            $statsQB->filterByIssueGalleys($args['issueGalleyIds']);
        }

        if (!empty($args['assocTypes'])) {
            $statsQB->filterByAssocTypes($args['assocTypes']);
        }

        HookRegistry::call('StatsIssue::queryBuilder', [&$statsQB, $args]);

        return $statsQB;
    }
}
