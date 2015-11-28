{**
 * plugins/pubIds/urn/templates/urnSuffixEdit.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit custom URN suffix for an object (issue, article, file)
 *
 *}
{if $pubObject}
    {assign var=pubObjectType value=$pubIdPlugin->getPubObjectType($pubObject)}
    {assign var=enableObjectURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enable`$pubObjectType`URN")}
    {if $enableObjectURN}
        {assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
        {fbvFormArea id="pubIdURNFormArea" class="border" title="plugins.pubIds.urn.editor.urn"}
        {assign var=formArea value=true}
        {if !$excludeURN}
            {if $pubIdPlugin->getSetting($currentJournal->getId(), 'urnSuffix') == 'customId' || $storedPubId}
                {if empty($storedPubId)} {* edit custom suffix *}
                    {fbvFormSection}
                        <p class="pkp_help">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.description"}</p>
                        {fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnPrefix" id="urnPrefix" disabled=true value=$pubIdPlugin->getSetting($currentJournal->getId(), 'urnPrefix') size=$fbvStyles.size.SMALL}
                        {fbvElement type="text" label="plugins.pubIds.urn.manager.settings.urnSuffix" id="urnSuffix" value=$urnSuffix size=$fbvStyles.size.MEDIUM}
                    {/fbvFormSection}
                {else} {* stored pub id and clear option *}
                    <p>
                        {$storedPubId|escape}<br />
                        {include file="linkAction/linkAction.tpl" action=$clearPubIdLinkActionURN contextId="publicIdentifiersForm"}
                    </p>
                {/if}
            {else} {* pub id preview *}
                <p>{$pubIdPlugin->getPubId($pubObject, true)|escape}</p>
                {capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
                <p class="pkp_help">{translate key="plugins.pubIds.urn.editor.urnNotYetGenerated" pubObjectType=$translatedObjectType}</p>
            {/if}
        {/if}

        {* exclude option *}
        {if !$storedPubId}
            {fbvFormSection list="true"}
                {if $excludeURN}
                    {assign var="checked" value=true}
                {else}
                    {assign var="checked" value=false}
                {/if}
                {capture assign=translatedObjectType}{translate key="plugins.pubIds.urn.editor.urnObjectType"|cat:$pubObjectType}{/capture}
                {capture assign=excludeCheckBoxLabel}{translate key="plugins.pubIds.urn.editor.excludePubObject" pubObjectType=$translatedObjectType}{/capture}
                {fbvElement type="checkbox" id="excludeURN" name="excludeURN" value="1" checked=$checked label=$excludeCheckBoxLabel translate=false}
            {/fbvFormSection}
        {/if}
        {/fbvFormArea}
    {/if}
    {* issue pub object *}
	{if $pubObjectType == 'Issue'}
		{assign var=enableArticleURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableArticleURN")}
		{assign var=enableSubmissionFileURN value=$pubIdPlugin->getSetting($currentJournal->getId(), "enableSubmissionFileURN")}
		{if $enableArticleURN || $enableSubmissionFileURN}
            {if !$formArea}
                {assign var="formAreaTitle" value="plugins.pubIds.urn.editor.urn"}
            {else}
                {assign var="formAreaTitle" value=""}
            {/if}
            {fbvFormArea id="pubIdURNIssueobjectsFormArea" class="border" title=$formAreaTitle}
			{fbvFormSection list="true" description="plugins.pubIds.urn.editor.excludeIssueObjectsURN.description"}
                {include file="linkAction/linkAction.tpl" action=$excludeIssueObjectsLinkActionURN contextId="publicIdentifiersForm"}
			{/fbvFormSection}
			{fbvFormSection list="true" description="plugins.pubIds.urn.editor.clearIssueObjectsURN.description"}
				{include file="linkAction/linkAction.tpl" action=$clearIssueObjectsPubIdsLinkActionURN contextId="publicIdentifiersForm"}
			{/fbvFormSection}
			{/fbvFormArea}
		{/if}
	{/if}
{/if}
