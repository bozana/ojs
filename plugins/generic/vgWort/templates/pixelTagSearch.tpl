{**
 * @file plugins/generic/vgWort/templates/pixelTagSearch.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the pixel tags search mask/fields.
 *
 *}
<script type="text/javascript">
	// Attach the form handler to the form.
	$('#pixelTagsSearchForm').pkpHandler('$.pkp.controllers.form.ClientFormHandler',
		{ldelim}
			trackFormChanges: false
		{rdelim}
	);
</script>
<form id="pixelTagsSearchForm" method="post" action="{url op="pixelTags" path=$returnPage}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
	</select>
	<input type="text" size="30" name="search" class="textField" value="{$search|escape}" />
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>
