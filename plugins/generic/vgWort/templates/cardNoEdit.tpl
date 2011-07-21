{**
 * plugins/generic/vgWort/templates/cardNoEdit.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011  
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit VG Wort cardNo
 *
 *}
<!-- VG Wort -->
<tr valign="top">
	<td class="label">
		{fieldLabel name="authors-$authorIndex-cardNo" key="plugins.generic.vgWort.cardNo"}
	</td>
	<td class="value">
		<input type="text" name="authors[{$authorIndex|escape}][cardNo]" id="authors-{$authorIndex|escape}-cardNo" value="{$author.cardNo|escape}" size="30" maxlength="90" class="textField" /><br/>
		<span class="instruct">{translate key="plugins.generic.vgWort.cardNo.description"}</span>
	</td>
</tr>
<!-- /VG Wort -->

