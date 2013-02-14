{**
 * controllers/tab/settings/siteAccessOptions/form/siteAccessOptionsForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site access options management form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#siteAccessOptionsForm').pkpHandler('$.pkp.controllers.tab.settings.siteAccessOptions.form.SiteAccessOptionsFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="siteAccessOptionsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AccessSettingsTabHandler" op="saveFormData" tab="siteAccessOptions"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="siteAccessOptionsFormNotification"}

	{fbvFormArea id="siteAccess" class="border" title="manager.setup.siteAccess"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="restrictSiteAccess" value="1" checked=$restrictSiteAccess label="manager.setup.restrictSiteAccess"}
			{fbvElement type="checkbox" id="restrictArticleAccess" value="1" checked=$restrictArticleAccess label="manager.setup.restrictArticleAccess"}
			{fbvElement type="checkbox" id="showGalleyLinks" value="1" checked=$showGalleyLinks label="manager.setup.showGalleyLinksDescription"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="userRegistration" class="border" title="manager.setup.userRegistration"}
		{fbvFormSection list=true}
			{fbvElement type="radio" id="disableUserReg-0" name="disableUserReg" value="0" checked=!$disableUserReg label="manager.setup.enableUserRegistration"}
			<div style="padding-left: 20px;">
				{fbvElement type="checkbox" id="allowRegReader" value="1" checked=$allowRegReader disabled=$disableUserReg label="manager.setup.enableUserRegistration.reader"}
				{fbvElement type="checkbox" id="allowRegAuthor" value="1" checked=$allowRegAuthor disabled=$disableUserReg label="manager.setup.enableUserRegistration.author"}
				{fbvElement type="checkbox" id="allowRegReviewer" value="1" checked=$allowRegReviewer disabled=$disableUserReg label="manager.setup.enableUserRegistration.reviewer"}
			</div>
			{fbvElement type="radio" id="disableUserReg-1" name="disableUserReg" value="1" checked=$disableUserReg label="manager.setup.disableUserRegistration"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons id="siteAccessFormSubmit" submitText="common.save" hideCancel=true}
</form>