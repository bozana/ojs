<?php

/**
 * @file plugins/generic/vgWort/VGWortSettingsForm.inc.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 19, 2011
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VGWortSettingsForm
 * @ingroup plugins_generic_vgwort
 *
 * @brief Form for journal managers to setup VG Wort plugin
 */


import('lib.pkp.classes.form.Form');

class VGWortSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function VGWortSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'vgWortUserId', 'required', 'plugins.generic.vgWort.manager.settings.vgWortUserIdRequired'));
		$this->addCheck(new FormValidator($this, 'vgWortUserPassword', 'required', 'plugins.generic.vgWort.manager.settings.vgWortUserPasswordRequired'));
		$this->addCheck(new FormValidator($this, 'vgWortEditors', 'required', 'plugins.generic.vgWort.manager.settings.vgWortEditorsRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'vgWortPixelTagMin', 'required', 'plugins.generic.vgWort.manager.settings.vgWortPixelTagMinRequired', '/^([1-9]|10)$/'));
		$this->addCheck(new FormValidatorPost($this));

		$this->setData('pluginName', $plugin->getName());
	}

	/**
	 * Display the form.
	 */
	function fetch($request) {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$editors = array();
		$users =& $roleDao->getUsersByRoleId(ROLE_ID_MANAGER, $journalId);
		foreach ($users->toArray() as $user) {
			$editors[$user->getId()] = $user->getFullName();
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('editors', $editors);
		return parent::fetch($request);
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($journalId, $fieldName));
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($journalId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}

	//
	// Private helper methods
	//
	function _getFormFields() {
		return array(
			'vgWortEditors' => 'object',
			'vgWortUserId' => 'string',
			'vgWortUserPassword' => 'string',
			'vgWortPixelTagMin' => 'int'
		);
	}
}

?>
