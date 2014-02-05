{**
 * @file plugins/generic/vgWort/templates/pixelTagsAvailable.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: February 04, 2014
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of availabe/free pixel tags for editor management.
 *
 *}
{assign var="pageTitle" value="plugins.generic.vgWort.pixelTagsAvailable"}

{include file="../plugins/generic/vgWort/templates/pixelTagsHeader.tpl"}
<br />
{include file="../plugins/generic/vgWort/templates/pixelTagSearch.tpl"}
<br />
{include file="../plugins/generic/vgWort/templates/formErrors.tpl"}
<br />

{assign var=colspan value="5"}
{assign var=colspanPage value="2"}
<div id="pixelTags">
<table width="100%" class="listing">
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="25%">{translate key="plugins.generic.vgWort.pixelTag.privateCode"}</td>
		<td width="25%">{translate key="plugins.generic.vgWort.pixelTag.publicCode"}</td>
		<td width="25%">{translate key="plugins.generic.vgWort.pixelTag.domain"}</td>
		<td width="10%">{translate key="plugins.generic.vgWort.pixelTag.date_ordered"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>

{iterate from=pixelTags item=pixelTag}
	<tr valign="top">
		<td>{$pixelTag->getPrivateCode()|escape}</td>
		<td>{$pixelTag->getPublicCode()|escape}</td>
		<td>{$pixelTag->getDomain()|escape}</td>
		<td>{$pixelTag->getDateOrdered()|date_format:$dateFormatShort}</td>
		<td align="right"><a href="{url op="deletePixelTag" path=$pixelTag->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.vgWort.editor.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="{if $pixelTags->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $pixelTags->wasEmpty()}
	<tr>
		<td colspan="{$colspan}" class="nodata">{translate key="plugins.generic.vgWort.none"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="{$colspanPage}" align="left">{page_info iterator=$pixelTags}</td>
		<td colspan="{$colspanPage+1}" align="right">{page_links anchor="pixelTags" name="pixelTags" iterator=$pixelTags}</td>
	</tr>
{/if}
</table>
</div>
<br />

<form id="orderPixelTagsForm" action="{url op="pixelTags" path="available"}" method="post">
<p>
{translate key="plugins.generic.vgWort.editor.pixelTagCount"}
<input type="hidden" name="action" value="order" />
<input type="text" name="count" id="count" value="" size="5" maxlength="5" class="textField" />
<input type="submit" value="{translate key="plugins.generic.vgWort.editor.order"}" class="button defaultButton" />
</p>
</form>

{include file="common/footer.tpl"}
