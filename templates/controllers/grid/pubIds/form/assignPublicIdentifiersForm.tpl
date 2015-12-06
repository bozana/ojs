{**
 * templates/controllers/grid/pubIds/form/assignPublicIdentifiersForm.tpl
 *
 * Copyright (c) 2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *} 
 <script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#assignPublicIdentifierForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true
			{rdelim}
		);
	{rdelim});
</script>
{if $pubObject instanceof Issue}
	<form class="pkp_form" id="assignPublicIdentifierForm" method="post" action="{url component="grid.issues.FutureIssueGridHandler" op="publishIssue" issueId=$pubObject->getId() confirmed=true escape=false}">
{elseif $pubObject instanceof Representation}
	<form class="pkp_form" id="assignPublicIdentifierForm" method="post" action="{url component="grid.articleGalleys.ArticleGalleyGridHandler" op="setApproved" submissionId=$pubObject->getSubmissionId() representationId=$pubObject->getId() newApprovedState=$approval confirmed=true escape=false}">
{elseif $pubObject instanceof SubmissionFile}
	<form class="pkp_form" id="assignPublicIdentifierForm" method="post" action="{url component="grid.articleGalleys.ArticleGalleyGridHandler" op="setProofFileCompletion" fileId=$pubObject->getFileId() revision=$pubObject->getRevision() submissionId=$pubObject->getSubmissionId() approval=$approval confirmed=true escape=false}">
{/if}
{fbvFormArea id="confirmationText"}
	<p>{$confirmationText}</p>
{/fbvFormArea}
{if $approval}
	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{assign var=pubIdAssignFile value=$pubIdPlugin->getPubIdAssignFile()}
		{assign var=canBeAssigned value=$pubIdPlugin->canBeAssigned($pubObject)}
		{include file="$pubIdAssignFile" pubIdPlugin=$pubIdPlugin pubObject=$pubObject canBeAssigned=$canBeAssigned}
	{/foreach}
{/if}
{fbvFormButtons id="assignPublicIdentifierForm" submitText="common.ok"}
</form>