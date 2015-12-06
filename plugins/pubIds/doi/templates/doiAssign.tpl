{**
 * @file plugins/pubIds/doi/templates/doiAssign.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Assign URN to an object option.
 *}
{assign var=storedPubId value=$pubObject->getStoredPubId($pubIdPlugin->getPubIdType())}
{if empty($storedPubId)}
    {capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
    {capture assign=assignCheckboxLabel}{translate key="plugins.pubIds.doi.editor.assignDoi" pubObjectType=$translatedObjectType}{/capture}
    {fbvFormSection list=true}
        {if $issueId}
            {assign var="checked" value=false}
        {else}
            {assign var="checked" value=true}
        {/if}
        {fbvElement type="checkbox" id="assignDoi" checked=$checked value="1" label=$assignCheckboxLabel translate=false}
    {/fbvFormSection}
{else}
    <p>{$storedPubId}</p>
{/if}