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
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
		
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$editors = array();
		$users =& $roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journalId);
		foreach ($users->toArray() as $user) {
			$editors[$user->getId()] = $user->getFullName();
		}
				
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('editors', $editors);
		parent::display();
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
		
		$this->_data = array(
			'vgWortEditors' => $plugin->getSetting($journalId, 'vgWortEditors'),
			'vgWortUserId' => $plugin->getSetting($journalId, 'vgWortUserId'),
			'vgWortUserPassword' => base64_decode($plugin->getSetting($journalId, 'vgWortUserPassword')),
			'vgWortPixelTagMin' => $plugin->getSetting($journalId, 'vgWortPixelTagMin')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('vgWortEditors', 'vgWortUserId', 'vgWortUserPassword', 'vgWortPixelTagMin'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		
		$plugin->updateSetting($journalId, 'vgWortEditors', $this->getData('vgWortEditors'), 'object');
		$plugin->updateSetting($journalId, 'vgWortUserId', $this->getData('vgWortUserId'), 'string');
		$plugin->updateSetting($journalId, 'vgWortUserPassword', base64_encode($this->getData('vgWortUserPassword')), 'string');
		$plugin->updateSetting($journalId, 'vgWortPixelTagMin', $this->getData('vgWortPixelTagMin'), 'int');
	}
}

?>
