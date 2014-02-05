{**
 * plugins/generic/vgWort/templates/pixelTagForm.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: February 04, 2014
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Pixel tag
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.vgWort.pixelTag.pixelTag"}
{include file="common/header.tpl"}
{/strip}

{literal}
<script type="text/javascript">
<!--
// hide the text type description
function hideDescription(hide) {
	if (hide) document.getElementById('textTypeDescription').style.visibility='hidden';
	else document.getElementById('textTypeDescription').style.visibility='visible';
}
// -->
</script>
{/literal}
<div id="pixelTag">
<form method="post" action="{url op="savePixelTag"}">
{include file="common/formErrors.tpl"}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="privateCode" required="true" key="plugins.generic.vgWort.pixelTag.privateCode"}</td>
		<td width="80%" class="value"><input type="text" name="privateCode" id="privateCode" value="{$privateCode|escape}" size="32" maxlength="32" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publicCode" required="true" key="plugins.generic.vgWort.pixelTag.publicCode"}</td>
		<td width="80%" class="value"><input type="text" name="publicCode" id="publicCode" value="{$publicCode|escape}" size="32" maxlength="32" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="domain" required="true" key="plugins.generic.vgWort.pixelTag.domain"}</td>
		<td width="80%" class="value"><input type="text" name="domain" id="domain" value="{$domain|escape}" size="15" maxlength="25" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="dateOrdered" required="true" key="plugins.generic.vgWort.pixelTag.date_ordered"}</td>
		<td class="value" id="dateOrdered">{html_select_date prefix="dateOrdered" all_extra="class=\"selectMenu\"" start_year="-5" }</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="articleId" required="true" key="plugins.generic.vgWort.pixelTag.articleId"}</td>
		<td width="80%" class="value">
			<input type="text" name="articleId" id="articleId" value="{$articleId|escape}" size="15" maxlength="10" class="textField" />
			<br />
			<span id="articleIdNote" class="instruct">{translate key="plugins.generic.vgWort.pixelTag.articleId.note"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel mane="vgWortTextType" required="true" key="plugins.generic.vgWort.textType"}</td>
		<td width="80%" class="value">
			<select name="vgWortTextType" size="1" class="selectMenu" onChange="javascript:hideDescription(this.value=={$smarty.const.TYPE_LYRIC})">
					{html_options_translate options=$typeOptions}
			</select>
			<br />
			<span id="textTypeDescription" class="instruct">{translate key="plugins.generic.vgWort.textType.description"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="dateAssigned" required="true" key="plugins.generic.vgWort.pixelTag.date_assigned"}</td>
		<td class="value" id="dateAssigned">{html_select_date prefix="dateAssigned" all_extra="class=\"selectMenu\"" start_year="-5" }</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="dateRegistered" required="true" key="plugins.generic.vgWort.pixelTag.date_registered"}</td>
		<td class="value" id="dateRegistered">{html_select_date prefix="dateRegistered" all_extra="class=\"selectMenu\"" start_year="-5" }</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
