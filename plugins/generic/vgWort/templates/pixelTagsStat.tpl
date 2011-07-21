{**
 * @file plugins/generic/vgWort/templates/pixelTagsStat.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011  
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display VG Wort pixel tags statistics.
 *
 *}
{assign var="pageTitle" value="plugins.generic.vgWort.pixelTagsStat"}

{include file="../plugins/generic/vgWort/templates/pixelTagsHeader.tpl"}
<br />
{include file="../plugins/generic/vgWort/templates/formErrors.tpl"}
<br />

<p>Ordered pixel tags till now: {$orderedPixelTillToday}</p>

<p>Tracked pixel tags till now: {$startedPixelTillToday}</p>

<br />

{assign var=colspan value="5"}
{assign var=colspanPage value="3"}

<table width="100%" class="listing">
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="15%">{translate key="plugins.generic.vgWort.month"}</td>
		<td width="20%">{translate key="plugins.generic.vgWort.orderedPixel"}</td>
		<td width="20%">{translate key="plugins.generic.vgWort.startedPixel"}</td>
		<td width="15%">{translate key="plugins.generic.vgWort.minAccess"}</td>
		<td width="30%">{translate key="plugins.generic.vgWort.minAccessNoMessage"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>

{foreach from=$qualityControlValues item=qualityControlValue}
	<tr valign="top">
		<td>{$qualityControlValue->year|escape}-{$qualityControlValue->month|escape}</td>
		<td>{$qualityControlValue->orderedPixel|escape}</td>
		<td>{$qualityControlValue->startedPixel|escape}</td>
		<td>{$qualityControlValue->minAccess|escape}</td>
		<td>{$qualityControlValue->minAccessNoMessage|escape}</td>
	</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
