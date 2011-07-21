{**
 * @file plugins/generic/vgWort/templates/pixelTagsHeader.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 19, 2011  
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for the pixel tags pages.
 *
 *}
{strip}
{assign var="pageCrumbTitle" value="plugins.generic.vgWort.editor.pixelTags"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li{if $returnPage == ''} class="current"{/if}><a href="{url op="pixelTags"}">{translate key="plugins.generic.vgWort.all"}</a></li>
	<li{if $returnPage == 'available'} class="current"{/if}><a href="{url op="pixelTags" path="available"}">{translate key="plugins.generic.vgWort.available"} {if $pixelTagsCounts[$smarty.const.PT_STATUS_AVAILABLE] <= $vgWortPixelTagMin}<span class="formError">({$pixelTagsCounts[$smarty.const.PT_STATUS_AVAILABLE]})</span>{else}({$pixelTagsCounts[$smarty.const.PT_STATUS_AVAILABLE]}){/if}</a></li>
	<li{if $returnPage == 'unregistered'} class="current"{/if}><a href="{url op="pixelTags" path="unregistered"}">{translate key="plugins.generic.vgWort.unregistered"} ({$pixelTagsCounts[$smarty.const.PT_STATUS_UNREGISTERED]})</a></li>
	<li{if $returnPage == 'registered'} class="current"{/if}><a href="{url op="pixelTags" path="registered"}">{translate key="plugins.generic.vgWort.registered"} ({$pixelTagsCounts[$smarty.const.PT_STATUS_REGISTERED]})</a></li>
	<li{if $returnPage == 'statistics'} class="current"{/if}><a href="{url op="pixelStatistics"}">{translate key="plugins.generic.vgWort.editor.statistics"}</a></li>
</ul>
