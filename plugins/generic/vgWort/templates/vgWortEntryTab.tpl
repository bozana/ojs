{**
 * plugins/generic/vgWort/templates/vgWortEntryTab.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: December 11, 2013
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * VG Wort Assign Pixel Tag Tab
 *
 *}

<li>
	<a title="vgWort" href="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.tab.vgWortEntry.VGWortEntryTabHandler" tab="vgWort" op="assignPixelTag" submissionId=$submissionId}">{translate key="plugins.generic.vgWort.editor.vgWort"}</a>
</li>
