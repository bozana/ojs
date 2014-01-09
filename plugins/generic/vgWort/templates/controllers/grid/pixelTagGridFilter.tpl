{**
 * plugins/generic/vgWort/templates/controllers/grid/pixelTagGridFilter.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: November 01, 2013
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Filter template for pixel tag grid.
 *}
<script type="text/javascript">
	// Attach the form handler to the form.
	$('#pixelTagSearchForm{/literal}{$filterData.pixelTagStatus}{literal}').pkpHandler('$.pkp.controllers.form.ClientFormHandler',
		{ldelim}
			trackFormChanges: false
		{rdelim}
	);
</script>
<form class="pkp_form" id="pixelTagSearchForm{$filterData.pixelTagStatus}" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.vgwort.AllPixelTagsGridHandler" op="fetchGrid"}" method="post">
	--{$filterData.pixelTagStatus}--
	<input type="hidden" name="pixelTagStatus" value="{$filterData.pixelTagStatus}" />
	{fbvFormArea id="pixelTagSearchFormArea"}
		{fbvFormSection title="common.search" required="false" for="search"}
			{fbvElement type="text" name="search" id="search" value=$filterSelectionData.search size=$fbvStyles.size.LARGE inline="true"}
			{fbvElement type="select" name="searchField" id="searchField" from=$filterData.fieldOptions selected=$filterSelectionData.searchField size=$fbvStyles.size.SMALL translate=false inline="true"}
		{/fbvFormSection}
		{fbvFormButtons hideCancel=true submitText="common.search"}
	{/fbvFormArea}
</form>
<div class="pkp_helpers_clear">&nbsp;</div>

{if $filterData.pixelTagStatus == $smarty.const.PT_STATUS_AVAILABLE}
	<script type="text/javascript">
		// Attach the form handler to the form.
		$('#orderPixelTagsForm').pkpHandler('$.pkp.controllers.form.ClientFormHandler',
			{ldelim}
				trackFormChanges: false
			{rdelim}
		);
	</script>
	<div class="pkp_structure_search pkp_helpers_align_right">
		<form id="orderPixelTagsForm" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.vgwort.AllPixelTagsGridHandler" op="order"}" method="post">
			<fieldset>
				{translate key="plugins.generic.vgWort.editor.pixelTagCount"}
				<input type="hidden" name="action" value="order" />
				<input type="text" name="count" id="count" value="" size="5" maxlength="5" class="textField" />
				<input type="submit" value="{translate key="plugins.generic.vgWort.editor.order"}" class="button defaultButton" />
			</fieldset>
		</form>
	</div>
	<div class="pkp_helpers_clear">&nbsp;</div>
{/if}
