{**
 * @file plugins/generic/vgWort/templates/pixelTagsUnregistered.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011  
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of unregistered pixel tags.
 *
 *}
{assign var="pageTitle" value="plugins.generic.vgWort.pixelTagsUnregistered"}

{include file="../plugins/generic/vgWort/templates/pixelTagsHeader.tpl"}
<br />
{include file="../plugins/generic/vgWort/templates/pixelTagSearch.tpl"}
<br />
{include file="../plugins/generic/vgWort/templates/formErrors.tpl"}
<br />

<form id="unregisteredPixelTags" action="{url page="registerPixelTags" op=""}" method="post">

{assign var=colspan value="5"}
{assign var=colspanPage value="2"}
<div id="pixelTags">
<table width="100%" class="listing">
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="25%">{translate key="plugins.generic.vgWort.pixelTag.privateCode"}</td>
		<td width="20%">{translate key="article.authors"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="10%">{translate key="plugins.generic.vgWort.pixelTag.date_assigned"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>

{iterate from=pixelTags item=pixelTag}
{assign var=article value=$pixelTag->getArticle()}

	<tr valign="top">
		<td>{$pixelTag->getPrivateCode()|escape}</td>
		<td>{$article->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submission" path=$pixelTag->getArticleId()}" class="action">{$article->getLocalizedTitle()|strip_unsafe_html|truncate:40:"..."}</a></td>
		<td>{$pixelTag->getDateAssigned()|date_format:$dateFormatShort}</td>
		<td align="right">
			{if $pixelTag->getDateRemoved()}
				{translate key="plugins.generic.vgWort.pixelTag.removed"}
			{else}
			    <a href="{url op="pixelTags" path="unregistered" action=register pixelTagId=$pixelTag->getId()}" class="action">{translate key="plugins.generic.vgWort.editor.register"}</a>
			{/if}
		</td>
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
</form>

{include file="common/footer.tpl"}
