{**
 * plugins/generic/vgWort/templates/pixelTagsTabs.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: October 21, 2013
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * VG Wort PixelTags Tabs
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.vgWort.editor.pixelTags"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#pixelTagsTabs').pkpHandler(
				'$.pkp.controllers.TabHandler',
				{ldelim}
					notScrollable: true
				{rdelim}
			);
	{rdelim});
</script>
<div id="pixelTagsTabs">
	<ul>
		<li><a href="#allPixelTags">{translate key="plugins.generic.vgWort.all"}</a></li>
		<li><a href="#availablePixelTags">{translate key="plugins.generic.vgWort.available"}</a></li>
		<li><a href="#unregisteredPixelTags">{translate key="plugins.generic.vgWort.unregistered"}</a></li>
		<li><a href="#registeredPixelTags">{translate key="plugins.generic.vgWort.registered"}</a></li>
		<li><a href="{url op="pixelTags" path="statistics"}">{translate key="plugins.generic.vgWort.editor.statistics"}</a></li>
	</ul>

	<div id="allPixelTags">
		{url|assign:allPixelTagsGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.grid.PixelTagGridHandler" op="fetchGrid" escape=false}
		{load_url_in_div id="allPixelTagsGridContainer" url=$allPixelTagsGridUrl}
	</div>
	<div id="availablePixelTags">
		{url|assign:availablePixelTagsGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.grid.PixelTagGridHandler" op="fetchGrid" pixelTagStatus=$smarty.const.PT_STATUS_AVAILABLE escape=false}
		{load_url_in_div id="availablePixelTagsGridContainer" url=$availablePixelTagsGridUrl}
	</div>
	<div id="unregisteredPixelTags">
		{url|assign:unregisteredPixelTagsGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.grid.PixelTagGridHandler" op="fetchGrid" pixelTagStatus=$smarty.const.PT_STATUS_UNREGISTERED escape=false}
		{load_url_in_div id="unregisteredPixelTagsGridContainer" url=$unregisteredPixelTagsGridUrl}
	</div>
	<div id="registeredPixelTags">
		{url|assign:registeredPixelTagsGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.grid.PixelTagGridHandler" op="fetchGrid" pixelTagStatus=$smarty.const.PT_STATUS_REGISTERED escape=false}
		{load_url_in_div id="registeredPixelTagsGridContainer" url=$registeredPixelTagsGridUrl}
	</div>
</div>
{include file="common/footer.tpl"}