{**
 * plugins/generic/vgWort/templates/assignPixelTag.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 19, 2011
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Assign VG Wort pixel tag to the article
 *
 *}
<!-- VG Wort -->
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
<div id="vgWort">
<h3>{translate key="plugins.generic.vgWort.editor.vgWort"}</h3>

{if !isset($pixelTag)}

<p>{translate key="plugins.generic.vgWort.assignDescription"}</p>

<form action="{url router=$smarty.const.ROUTE_PAGE page="management" op="assignPixelTag" path=$submission->getId()}" method="post">

{assign var=errorCode value=$smarty.get.errorCode}
{if $errorCode}
	<span class="pkp_form_error">{translate key="form.errorsOccurred"}:</span>
	<ul class="pkp_form_error_list"><li>{translate key="plugins.generic.vgWort.assign.errorCode$errorCode"}</li></ul>
{/if}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><label for="vgWortTextType">{translate key="plugins.generic.vgWort.textType"}</label></td>
		<td width="80%" class="value">
			<select name="vgWortTextType" size="1" class="selectMenu" onChange="javascript:hideDescription(this.value=={$smarty.const.TYPE_LYRIC})">
					{html_options_translate options=$typeOptions selected=$vgWortTextType}
			</select> <input value="{translate key="plugins.generic.vgWort.assign"}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.vgWort.confirmAssign"}')" class="button defaultButton" type="submit">
			<br />
			<span id="textTypeDescription" class="instruct"{if $vgWortTextType == $smarty.const.TYPE_LYRIC} style="visibility:hidden"{/if}>{translate key="plugins.generic.vgWort.textType.description"}</span>
		</td>
	</tr>
</table>

</form>

{else}

{if $pixelTag->getDateRemoved()}
<p>{translate key="plugins.generic.vgWort.reinsertDescription"}</p>
{else}
<p>{translate key="plugins.generic.vgWort.removeDescription"}</p>
{/if}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.vgWort.pixelTag.status"}</td>
		<td width="30%" class="value">{$pixelTag->getStatusString()}</td>
		<td width="50%" class="value">
		{if $pixelTag->getDateRemoved()}
			<a href="{url op="reinsertPixelTag" path=$pixelTag->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.vgWort.confirmReinsert"}')" class="action">{translate key="plugins.generic.vgWort.reinsert"}</a>
		{else}
			<a href="{url op="removePixelTag" path=$pixelTag->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.vgWort.confirmRemove"}')" class="action">{translate key="plugins.generic.vgWort.remove"}</a>
		{/if}
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.vgWort.textType"}</td>
		<td width="30%" class="value">{$pixelTag->getTextTypeString()}</td>
		<td width="50%" class="value">
		{if $pixelTag->getStatus() == PT_STATUS_REGISTERED}
			&nbsp;
		{else}
			<form action="{url op="changeTextType" path=$pixelTag->getId()}" method="post">{translate key="plugins.generic.vgWort.changeTextType"} <select name="vgWortTextType" size="1" class="selectMenu" onChange="javascript:hideDescription(this.value=={$smarty.const.TYPE_LYRIC})">{html_options_translate options=$typeOptions selected=$vgWortTextType}</select> <input type="submit" value="{translate key="common.record"}" class="button" />
			<br />
			<span id="textTypeDescription" class="instruct"{if $vgWortTextType == $smarty.const.TYPE_LYRIC} style="visibility:hidden"{/if}>{translate key="plugins.generic.vgWort.textType.description"}</span>
			</form>
		{/if}
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.vgWort.pixelTag.privateCode"}</td>
		<td width="30%" class="value">{$pixelTag->getPrivateCode()}</td>
		<td width="50%" class="value">&nbsp;</td>
	</tr>
</table>
{/if}
</div>

<div class="separator"></div>
<!-- /VG Wort -->
