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


<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#assignPixelTagForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true,
			{rdelim}
		);
	{rdelim});
</script>
<form class="pkp_form"  id="assignPixelTagForm" action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.tab.vgWortEntry.VGWortEntryTabHandler" op="assignPixelTag"}" method="post">
	<input type="hidden" name="submissionId" id="submissionId" value="{$submissionId|escape}" />

{if !isset($pixelTag)}
	<input type="hidden" name="function" id="function" value="assign" />

	<div id="description">{translate key="plugins.generic.vgWort.assignDescription"}</div>

	{if $errorCode}
		<span class="pkp_form_error">{translate key="form.errorsOccurred"}:</span>
		<ul class="pkp_form_error_list"><li>{translate key="plugins.generic.vgWort.assign.errorCode$errorCode"}</li></ul>
	{/if}

	<table width="100%" class="data">
		<tr valign="top">
			<td width="20%" class="label"><label for="vgWortTextType">{translate key="plugins.generic.vgWort.textType"}</label></td>
			<td width="80%" class="value">
				<select name="vgWortTextType" size="1" class="selectMenu">
						{html_options_translate options=$typeOptions selected=$vgWortTextType}
				</select> <input name="assign" value="{translate key="plugins.generic.vgWort.assign"}" class="button defaultButton" type="submit">
				<br />
				<span id="textTypeDescription" class="instruct">{translate key="plugins.generic.vgWort.textType.description"}</span>
			</td>
		</tr>
	</table>

{else}
	<input type="hidden" name="function" id="function" value="update" />
	{fbvFormArea id="pixelTagDataArea" class="border"}
	<table width="100%" class="pkp_listing">
		<tr valign="top">
			<td>{translate key="plugins.generic.vgWort.pixelTag.status"}</td>
			<td>{$pixelTag->getStatusString()}</td>
		</tr>
		<tr valign="top">
			<td>{translate key="plugins.generic.vgWort.textType"}</td>
			<td>{$pixelTag->getTextTypeString()}</td>
		</tr>
		<tr valign="top">
			<td>{translate key="plugins.generic.vgWort.pixelTag.privateCode"}</td>
			<td>{$pixelTag->getPrivateCode()}</td>
		</tr>
	</table>
	{/fbvFormArea}
	{include file="../plugins/generic/vgWort/templates/changeAssignment.tpl" readOnly=$formParams.readOnly}
{/if}
</form>


