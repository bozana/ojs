{**
 * plugins/importexport/crossref/templates/index.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.crossref.displayName"}
{include file="common/header.tpl"}
{/strip}

{if !empty($configurationErrors) || !$currentContext->getSetting('publisherInstitution')|escape}
	{assign var="allowExport" value=false}
{else}
	{assign var="allowExport" value=true}
{/if}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#importExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
		//$('#importExportTabs').tabs('option', 'cache', true);
	{rdelim});
</script>
<div id="importExportTabs">
	<ul>
		<li><a href="#settings-tab">{translate key="plugins.importexport.common.settings"}</a></li>
		{if $allowExport}
			<li><a href="#exportSubmissions-tab">{translate key="plugins.importexport.common.export.articles"}</a></li>
			<li><a href="#exportIssues-tab">{translate key="plugins.importexport.common.export.issues"}</a></li>
		{/if}
	</ul>
	<div id="settings-tab">
		{if !$allowExport}
			<div class="pkp_notification" id="crossrefConfigurationErrors">
				{foreach from=$configurationErrors item=configurationError}
					{if $configurationError == $smarty.const.DOI_EXPORT_CONFIGERROR_DOIPREFIX}
						{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=crossrefConfigurationErrors notificationStyleClass=notifyWarning notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.common.error.DOIsNotAvailable"|translate}
					{elseif $configurationError == $smarty.const.DOI_EXPORT_CONFIGERROR_SETTINGS}
						{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=crossrefConfigurationErrors notificationStyleClass=notifyWarning notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.common.error.pluginNotConfigured"|translate}
					{/if}
				{/foreach}
				{if !$currentContext->getSetting('publisherInstitution')|escape}
						{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=crossrefConfigurationErrors notificationStyleClass=notifyWarning notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.crossref.error.publisherNotConfigured"|translate}
				{/if}
			</div>
		{/if}

		{url|assign:crossrefSettingsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.plugins.settingsPluginGridHandler" op="manage" plugin="CrossRefExportPlugin" category="importexport" escape=false}
		{load_url_in_div id="crossrefSettingsGridContainer" url=$crossrefSettingsGridUrl}
	</div>

	{if $allowExport}
		<div id="exportSubmissions-tab">
			<script type="text/javascript">
				$(function() {ldelim}
					// Attach the form handler.
					$('#exportSubmissionXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
				{rdelim});
			</script>
			<form id="exportSubmissionXmlForm" class="pkp_form" action="{plugin_url path="exportSubmissions"}" method="post">
				{fbvFormArea id="submissionsXmlForm"}
					{url|assign:submissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.pubIds.PubIdExportSubmissionsListGridHandler" op="fetchGrid" plugin="crossref" category="importexport" escape=false}
					{load_url_in_div id="submissionsListGridContainer" url=$submissionsListGridUrl}
					{fbvFormButtons hideCancel="true"}
				{/fbvFormArea}
			</form>
		</div>

		<div id="exportIssues-tab">
			<script type="text/javascript">
				$(function() {ldelim}
					// Attach the form handler.
					$('#exportIssuesXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
				{rdelim});
			</script>
			<form id="exportIssuesXmlForm" class="pkp_form" action="{plugin_url path="exportIssues"}" method="post">
				{fbvFormArea id="issuesXmlForm"}
					{url|assign:issuesListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.pubIds.PubIdExportIssuesListGridHandler" op="fetchGrid" plugin="crossref" category="importexport" escape=false}
					{load_url_in_div id="issuesListGridContainer" url=$issuesListGridUrl}
					{fbvFormButtons hideCancel="true"}
				{/fbvFormArea}
			</form>
		</div>
	{/if}
</div>

{include file="common/footer.tpl"}

