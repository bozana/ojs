{**
 * @file plugins/generic/vgWort/templates/formErrors.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 19, 2011  
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List errors that occurred during VG Wort form processing.
 *}
{if $isError}
	<div id="formErrors">
		<p>
		<span class="formError">{translate key="plugins.generic.vgWort.errorsOccurred"}:</span>
		<ul class="formErrorList">
		{foreach key=field item=message from=$errors}
			<li>{$message}</li>
		{/foreach}
		</ul>
		</p>
	</div>
{/if}

