<?php

/**
 * @file classes/services/StatsSushiService.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsSushiService
 * @ingroup services
 *
 * @brief Helper class that encapsulates COUNTER R5 SUSHI statistics business logic
 */

namespace APP\services;

use PKP\plugins\HookRegistry;

class StatsSushiService
{
    /**
     * Get a QueryBuilder object with the passed args
     */
    public function getQueryBuilder($args = []): \APP\services\queryBuilders\StatsSushiQueryBuilder
    {
        $statsQB = new \APP\services\queryBuilders\StatsSushiQueryBuilder();
        $statsQB
            ->filterByContexts($args['contextIds'])
            ->filterByInstitution((int) $args['institutionId'])
            ->before($args['dateEnd'])
            ->after($args['dateStart']);

        if (!empty($args['yearsOfPublication'])) {
            $statsQB->filterByYOP($args['yearsOfPublication']);
        }
        if (!empty($args['submissionIds'])) {
            $statsQB->filterBySubmissions($args['submissionIds']);
        }

        HookRegistry::call('StatsSushi::queryBuilder', [&$statsQB, $args]);

        return $statsQB;
    }
}
