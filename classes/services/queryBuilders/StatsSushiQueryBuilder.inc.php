<?php

/**
 * @file classes/services/queryBuilders/StatsSushiQueryBuilder.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsSushiQueryBuilder
 * @ingroup query_builders
 *
 * @brief Helper class to construct a query to fetch COUNTER stats records from the
 *  metrics_counter_submission_monthly or metrics_counter_submission_institution_monthly table.
 */

namespace APP\services\queryBuilders;

use Illuminate\Support\Facades\DB;
use PKP\plugins\HookRegistry;
use PKP\services\queryBuilders\PKPStatsQueryBuilder;
use PKP\statistics\PKPStatisticsHelper;

class StatsSushiQueryBuilder extends PKPStatsQueryBuilder
{
    /** @var array Include records for the submissions that have these years of publications (YOP) */
    protected array $yearsOfPublication = [];

    /** @var array Include records for these submissions */
    protected array $submissionIds = [];

    /** @var int Include records for this institution */
    protected int $institutionId = 0;

    /**
     * Set the year of publication (YOP) of submissions to get records for
     *
     * @param array $yearsOfPublication
     *
     * @return \APP\services\queryBuilders\StatsSushiQueryBuilder
     */
    public function filterByYOP($yearsOfPublication): self
    {
        $this->yearsOfPublication = $yearsOfPublication;
        return $this;
    }

    /**
     * Set the submissions to get records for
     *
     * @param array|int $submissionIds
     *
     * @return \APP\services\queryBuilders\StatsSushiQueryBuilder
     */
    public function filterBySubmissions($submissionIds): self
    {
        $this->submissionIds = is_array($submissionIds) ? $submissionIds : [$submissionIds];
        return $this;
    }

    /**
     * Set the institution to get records for
     *
     *
     * @return \APP\services\queryBuilders\StatsSushiQueryBuilder
     */
    public function filterByInstitution(int $institutionId): self
    {
        $this->institutionId = $institutionId;
        return $this;
    }

    /**
     * @copydoc PKPStatsQueryBuilder::getSum()
     */
    public function getSum(array $groupBy = []): \Illuminate\Database\Query\Builder
    {
        $selectColumns = $groupBy;
        $q = $this->_getObject();
        // consider YOP
        if (in_array('YOP', $selectColumns)) {
            // left join the table publications, if the filter is not set i.e./and the left join is not considered yet in _getObject()
            if (empty($this->yearsOfPublication)) {
                $q->leftJoin('publications as p', function ($q) {
                    $q->on('p.submission_id', '=', 'm.submission_id')
                        ->where('p.version', '=', 1);
                });
            }
            foreach ($selectColumns as $i => $selectColumn) {
                if ($selectColumn == 'YOP') {
                    $selectColumns[$i] = DB::raw('YEAR(STR_TO_DATE(p.date_published, "%Y-%m-%d")) as YOP');
                    break;
                }
            }
        }

        // Build the select and group by clauses.
        if (!empty($selectColumns)) {
            $q->select($selectColumns);
            if (!empty($groupBy)) {
                $q->groupBy($groupBy);
            }
        }
        $q->addSelect(DB::raw('SUM(metric_investigations) AS metric_investigations'));
        $q->addSelect(DB::raw('SUM(metric_investigations_unique) AS metric_investigations_unique'));
        $q->addSelect(DB::raw('SUM(metric_requests) AS metric_requests'));
        $q->addSelect(DB::raw('SUM(metric_requests_unique) AS metric_requests_unique'));

        return $q;
    }

    /**
     * @copydoc PKPStatsQueryBuilder::_getObject()
     */
    protected function _getObject(): \Illuminate\Database\Query\Builder
    {
        if ($this->institutionId === 0) {
            // consider only monthly DB table
            $q = DB::table('metrics_counter_submission_monthly as m');
        } else {
            // consider only monthly DB table
            $q = DB::table('metrics_counter_submission_institution_monthly as m');
        }

        if (!empty($this->yearsOfPublication)) {
            $q->leftJoin('publications as p', function ($q) {
                $q->on('p.submission_id', '=', 'm.submission_id')
                    ->where('p.version', '=', 1);
            });
            foreach ($this->yearsOfPublication as $yop) {
                if (preg_match('/\d{4}/', $yop)) {
                    $q->where(DB::raw('YEAR(STR_TO_DATE(p.date_published, "%Y-%m-%d"))'), '=', $yop);
                } elseif (preg_match('/\d{4}-\d{4}/', $yop)) {
                    $years = explode('-', $yop);
                    $q->whereBetween(DB::raw('YEAR(STR_TO_DATE(p.date_published, "%Y-%m-%d"))'), $years);
                }
            }
        }

        if (!empty($this->contextIds)) {
            $q->whereIn('m.' . PKPStatisticsHelper::STATISTICS_DIMENSION_CONTEXT_ID, $this->contextIds);
        }

        if (!empty($this->submissionIds)) {
            $q->whereIn('m.' . PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID, $this->submissionIds);
        }

        $q->whereBetween('m.' . PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH, [date_format(date_create($this->dateStart), 'Ym'), date_format(date_create($this->dateEnd), 'Ym')]);

        HookRegistry::call('StatsSushi::queryObject', [&$q, $this]);

        return $q;
    }
}
