{**
 * @file plugins/pubIds/doi/templates/doiSuffixEdit.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit DOI meta-data.
 *}
{if $pubObject}
	{assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
    {assign var=enableObjectDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`Doi")}
    {if $enableObjectDoi}
        {assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
        {fbvFormArea id="pubIdDOIFormArea" class="border" title="plugins.pubIds.doi.editor.doi"}
            {assign var=formArea value=true}
            {if $pubIdPlugin->getSetting($currentJournal->getId(), 'doiSuffix') == 'customId' || $storedPubId}
                {if empty($storedPubId)} {* edit custom suffix *}
                    {fbvFormSection}
                        <p class="pkp_help">{translate key="plugins.pubIds.doi.manager.settings.doiSuffix.description"}</p>
                            {fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiPrefix" id="doiPrefix" disabled=true value=$pubIdPlugin->getSetting($currentJournal->getId(), 'doiPrefix') size=$fbvStyles.size.SMALL}
                            {fbvElement type="text" label="plugins.pubIds.doi.manager.settings.doiSuffix" id="doiSuffix" value=$doiSuffix size=$fbvStyles.size.MEDIUM}
                    {/fbvFormSection}
                {else} {* stored pub id and clear option *}
                    <p>
                        {$storedPubId|escape}<br />
                        {include file="linkAction/linkAction.tpl" action=$clearPubIdLinkActionDoi contextId="publicIdentifiersForm"}
                    </p>
                {/if}
            {else} {* pub id preview *}
                <p>{$pubIdPlugin->getPubId($pubObject, true)|escape}</p>
                {capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
                <p class="pkp_help">{translate key="plugins.pubIds.doi.editor.doiNotYetGenerated" pubObjectType=$translatedObjectType}</p>
            {/if}

            {* assign option *}
            {if !$storedPubId}
                {fbvFormSection list="true"}
                    {capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
                    {capture assign=assignCheckBoxLabel}{translate key="plugins.pubIds.doi.editor.assignDoi" pubObjectType=$translatedObjectType}{/capture}
                    {fbvElement type="checkbox" id="assignDoi" name="assignDoi" value="1" checked=$assignDoi|compare:true label=$assignCheckBoxLabel translate=false}
                    <p class="pkp_help">{translate key="plugins.pubIds.doi.editor.assignDoi.description" pubObjectType=$translatedObjectType}</p>
                {/fbvFormSection}
            {/if}
        {/fbvFormArea}
    {/if}
	{* issue pub object *}
	{if $pubObjectType == 'Issue'}
		{assign var=enableArticleDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleDoi")}
		{assign var=enableSubmissionFileDoi value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableSubmissionFileDoi")}
		{if $enableArticleDoi || $enableSubmissionFileDoi}
            {if !$formArea}
                {assign var="formAreaTitle" value="plugins.pubIds.doi.editor.doi"}
            {else}
                {assign var="formAreaTitle" value=""}
            {/if}
            {fbvFormArea id="pubIdDOIFormArea" class="border" title=$formAreaTitle}
            {fbvFormSection list="true" description="plugins.pubIds.doi.editor.assignIssueObjectsDoi.description"}
                {include file="linkAction/linkAction.tpl" action=$assignIssueObjectsPubIdsLinkActionDoi contextId="publicIdentifiersForm"}
            {/fbvFormSection}
            {fbvFormSection list="true" description="plugins.pubIds.doi.editor.unassignIssueObjectsDoi.description"}
                {include file="linkAction/linkAction.tpl" action=$unassignIssueObjectsPubIdsLinkActionDoi contextId="publicIdentifiersForm"}
            {/fbvFormSection}
			{fbvFormSection list="true" description="plugins.pubIds.doi.editor.clearIssueObjectsDoi.description"}
                {include file="linkAction/linkAction.tpl" action=$clearIssueObjectsPubIdsLinkActionDoi contextId="publicIdentifiersForm"}
			{/fbvFormSection}
			{/fbvFormArea}
		{/if}
	{/if}
{/if}
