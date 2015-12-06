{**
 * templates/controllers/tab/pubIds/form/publicIdentifiersForm.tpl
 *
 * Copyright (c) 2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#publicIdentifiersForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true
			{rdelim}
		);
	{rdelim});
</script>
{if $pubObject instanceof Article}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveForm"}">
		<input type="hidden" name="submissionId" value="{$pubObject->getId()|escape}" />
		<input type="hidden" name="stageId" value="{$stageId|escape}" />
		<input type="hidden" name="tabPos" value="2" />
		<input type="hidden" name="displayedInContainer" value="{$formParams.displayedInContainer|escape}" />
		<input type="hidden" name="tab" value="identifiers" />
{elseif $pubObject instanceof ArticleGalley}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url component="grid.articleGalleys.ArticleGalleyGridHandler" op="updateIdentifiers" submissionId=$pubObject->getSubmissionId() representationId=$pubObject->getId() escape=false}">
{elseif $pubObject instanceof SubmissionFile}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url component="api.file.ManageFileApiHandler" op="updateIdentifiers" fileId=$pubObject->getFileId() revision=$pubObject->getRevision() submissionId=$pubObject->getSubmissionId() stageId=$stageId fileStageId=$pubObject->getFileStage() escape=false}">
{else} {* $pubObject instanceof Issue *}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="updateIdentifiers" issueId=$pubObject->getId()}">
{/if}
{include file="common/formErrors.tpl"}
{fbvFormSection}
	{fbvElement type="text" label="submission.publisherId" id="publisherId" name="publisherId" value=$publisherId size=$fbvStyles.size.MEDIUM}
{/fbvFormSection}

{foreach from=$pubIdPlugins item=pubIdPlugin}
	{assign var=pubIdMetadataFile value=$pubIdPlugin->getPubIdMetadataFile()}
	{assign var=pubIdType value=$pubIdPlugin->getPubIdType()}
	{include file="$pubIdMetadataFile" pubObject=$pubObject}

	{if $pubIdPlugin->canBeAssigned($pubObject)}
		{assign var=pubIdAssignFile value=$pubIdPlugin->getPubIdAssignFile()}
		{include file="$pubIdAssignFile" pubIdPlugin=$pubIdPlugin pubObject=$pubObject}
	{/if}
{/foreach}

{fbvFormButtons id="publicIdentifiersFormSubmit" submitText="common.save"}

</form>