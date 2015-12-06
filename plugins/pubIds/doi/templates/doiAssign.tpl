{**
 * @file plugins/pubIds/doi/templates/doiAssign.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Assign URN to an object option.
 *}

	{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
	{assign var=enableObjectDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`Doi")}
	{if $enableObjectDoi}
		{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
		{capture assign=assignCheckboxLabel}{translate key="plugins.pubIds.doi.editor.assignDoi" pubObjectType=$translatedObjectType}{/capture}
		{fbvFormSection list=true}
			{if $issueId}
				{assign var="checked" value=false}
			{else}
				{assign var="checked" value=true}
			{/if}
			{if !$canBeAssigned}
				{assign var="checked" value=false}
				{assign var="disabled" value=true}
				<p class="pkp_help">{translate key="plugins.pubIds.doi.editor.assignDoi.disabled"}</p>
			{/if}
			{fbvElement type="checkbox" id="assignDoi" checked=$checked value="1" label=$assignCheckboxLabel translate=false disabled=$disabled}
		{/fbvFormSection}
	{/if}
