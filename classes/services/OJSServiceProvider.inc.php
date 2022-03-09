<?php

/**
 * @file classes/services/OJSServiceProvider.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSServiceProvider
 * @ingroup services
 *
 * @brief Utility class to package all OJS services
 */

namespace APP\services;

use Pimple\Container;

use PKP\services\PKPFileService;
use PKP\services\PKPSchemaService;
use PKP\services\PKPSiteService;
use PKP\services\PKPStatsContextService;
use PKP\services\PKPStatsGeoService;

class OJSServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Registers services
     *
     */
    public function register(Container $pimple)
    {
        // File service
        $pimple['file'] = function () {
            return new PKPFileService();
        };

        // Section service
        $pimple['section'] = function () {
            return new SectionService();
        };

        // NavigationMenus service
        $pimple['navigationMenu'] = function () {
            return new NavigationMenuService();
        };

        // Galley service
        $pimple['galley'] = function () {
            return new GalleyService();
        };

        // Context service
        $pimple['context'] = function () {
            return new ContextService();
        };

        // Site service
        $pimple['site'] = function () {
            return new PKPSiteService();
        };

        // Schema service
        $pimple['schema'] = function () {
            return new PKPSchemaService();
        };

        // Context statistics service
        $pimple['contextStats'] = function () {
            return new PKPStatsContextService();
        };

        // Publication statistics service
        $pimple['publicationStats'] = function () {
            return new StatsPublicationService();
        };

        // Issue statistics service
        $pimple['issueStats'] = function () {
            return new StatsIssueService();
        };

        // Geo statistics service
        $pimple['geoStats'] = function () {
            return new PKPStatsGeoService();
        };

        // Editorial statistics service
        $pimple['editorialStats'] = function () {
            return new StatsEditorialService();
        };
    }
}
