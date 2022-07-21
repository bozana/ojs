<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_MetricsGeo.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_MetricsGeo
 * @brief Migrate submission stats Geo data from the old DB table metrics into the new DB table metrics_submission_geo_daily, then aggregate monthly.
 */

namespace APP\migration\upgrade\v3_4_0;

class I6782_MetricsGeo extends \PKP\migration\upgrade\v3_4_0\I6782_MetricsGeo
{
    protected function getMetricType(): string
    {
        return 'ojs::counter';
    }
}
