{**
 * plugins/generic/vgWort/templates/settingsForm.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011  
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * VG Wort plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.vgWort.manager.settings.vgWortSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="vgWortSettings">
<div id="description">{translate key="plugins.generic.vgWort.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="vgWortUserId" required="true" key="plugins.generic.vgWort.manager.settings.vgWortUserId"}</td>
		<td width="80%" class="value"><input type="text" name="vgWortUserId" id="vgWortUserId" value="{$vgWortUserId|escape}" size="15" maxlength="25" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="vgWortUserPassword" required="true" key="plugins.generic.vgWort.manager.settings.vgWortUserPassword"}</td>
		<td width="80%" class="value"><input type="password" name="vgWortUserPassword" id="vgWortUserPassword" value="{$vgWortUserPassword|escape}" size="15" maxlength="25" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="vgWortEditors" required="true" key="plugins.generic.vgWort.manager.settings.editors"}</td>
		<td width="80%" class="value">
			<select name="vgWortEditors[]" size="5" multiple="multiple" id="vgWortEditors" class="selectMenu">
				{html_options options=$editors selected=$vgWortEditors}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel suppressId="true" required="true" key="plugins.generic.vgWort.manager.settings.vgWortNotification"}</td>
		<td width="80%" class="value">
		{translate key="plugins.generic.vgWort.manager.settings.vgWortPixelTagMin"}: <input type="text" name="vgWortPixelTagMin" id="vgWortPixelTagMin" value="{$vgWortPixelTagMin|escape}" size="2" maxlength="5" class="textField" /> <span class="instruct">{translate key="plugins.generic.vgWort.manager.settings.vgWortPixelTagMinDescription"}</span>
		</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
