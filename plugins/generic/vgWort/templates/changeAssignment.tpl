{**
 * plugins/generic/vgWort/templates/changeAssignment.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: December 11, 2013
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * VG Wort Change the Pixel Tag Assignment
 *
 *}

	{fbvFormArea id="changePixelTagAssignmentFormArea" class="border"}
		{fbvFormSection list="true"}
			{if $pixelTag->getDateRemoved()}
				{assign var="checked" value=true}
				{assign var="description" value="plugins.generic.vgWort.reinsertDescription"}
			{else}
				{assign var="checked" value=false}
				{assign var="description" value="plugins.generic.vgWort.removeDescription"}
			{/if}
			{fbvElement type="checkbox" label="plugins.generic.vgWort.remove" id="removePixelTag" maxlength="40" checked=$checked}
		{/fbvFormSection}
		{fbvFormSection description=$description}{/fbvFormSection}

		{if $pixelTag->getStatus() != PT_STATUS_REGISTERED}
		{fbvFormSection label="plugins.generic.vgWort.changeTextType"}
			{fbvElement type="select" id="vgWortTextType" from=$typeOptions selected=$vgWortTextType translate=true disabled=$readOnly size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection description="plugins.generic.vgWort.textType.description"}{/fbvFormSection}
		{/if}
		{fbvFormButtons name="changePixelTagAssignment" id="changePixelTagAssignment" submitText="common.save" hideCancel=true}
	{/fbvFormArea}

