{**
 * plugins/generic/vgWort/templates/settingsForm.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * VG Wort plugin settings
 *
 *}

<div id="description">{translate key="plugins.generic.vgWort.manager.settings.description"}</div>
<br />
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#vgWortSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
--{$pluginName}--
<form class="pkp_form" id="vgWortSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="plugin" category="generic" plugin=$pluginName verb="settings" save="true"}">
	{include file="common/formErrors.tpl"}
	{fbvFormArea id="vgWortUserIdPassword" title="plugins.generic.vgWort.manager.settings.vgWortUserIdPassword" class="border"}
		{fbvFormSection description="plugins.generic.vgWort.manager.settings.vgWortUserIdPassword.description"}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.vgWort.manager.settings.vgWortUserId" for="vgWortUserId" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" name="vgWortUserId" id="vgWortUserId" value=$vgWortUserId}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.vgWort.manager.settings.vgWortUserPassword" for="vgWortUserPassword" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" name="vgWortUserPassword" id="vgWortUserPassword" value=$vgWortUserPassword}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="vgWortEditors" title="plugins.generic.vgWort.manager.settings.editors" class="border"}
		{fbvFormSection description="plugins.generic.vgWort.manager.settings.editors.description"}
		{/fbvFormSection}
		{fbvFormSection for="vgWortEditors"}
			{fbvElement type="select" name="vgWortEditors[]" id="vgWortEditors" from=$editors selected=$vgWortEditors translate=false disabled=$readOnly multiple="multiple" inline=true}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="vgWortNotification" title="plugins.generic.vgWort.manager.settings.vgWortNotification" class="border"}
		{fbvFormSection description="plugins.generic.vgWort.manager.settings.vgWortNotification.description"}
		{/fbvFormSection}
		{fbvFormSection for="vgWortPixelTagMin" size=$fbvStyles.size.SMALL}
			{fbvElement type="text" name="vgWortPixelTagMin" id="vgWortPixelTagMin" value=$vgWortPixelTagMin}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
