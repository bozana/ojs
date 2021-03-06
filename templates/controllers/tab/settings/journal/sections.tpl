{**
 * templates/controllers/tab/settings/journal/sections.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of sections in journal management.
 *
 *}

{url|assign:sectionsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.sections.SectionGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="sectionsGridContainer" url=$sectionsGridUrl}
