<?php

/**
 * @file plugins/generic/vgWort/classes/form/PixelTagForm.inc.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: February 06, 2014
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PixelTagForm
 * @ingroup plugins_generic_vgwort
 *
 * @brief Form for creation and inserting of a migrated and already registered pixel tag
 */

import('lib.pkp.classes.form.Form');

class PixelTagForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function PixelTagForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'pixelTagForm.tpl');
		$this->addCheck(new FormValidator($this, 'privateCode', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.privateCodeRequired'));
		$this->addCheck(new FormValidator($this, 'publicCode', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.publicCodeRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'privateCode', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.privateCodeAlphaNum'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'publicCode', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.publicCodeAlphaNum'));
		$this->addCheck(new FormValidatorLength($this, 'privateCode', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.privateCodeLength', '==', 32));
		$this->addCheck(new FormValidatorLength($this, 'publicCode', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.publicCodeLength', '==', 32));
		$this->addCheck(new FormValidator($this, 'domain', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.domainRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'domain', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.domainPattern', '/^vg[0-9][0-9]\.met\.vgwort\.de$/'));
		$this->addCheck(new FormValidator($this, 'articleId', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.articleIDRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'articleId', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.articleIDDoesNotExist', create_function('$articleId,$journalId,$articleDao', '$article = $articleDao->getArticle($articleId, $journalId); return isset($article);'), array($this->journalId, DAORegistry::getDAO('ArticleDAO'))));
		$this->addCheck(new FormValidatorCustom($this, 'articleId', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.vgWort.create.articleIDPixelTagExists', create_function('$articleId,$journalId,$pixelTagDao', '$pixelTag = $pixelTagDao->getPixelTagByArticleId($journalId, $articleId); return !isset($pixelTag);'), array($this->journalId, DAORegistry::getDAO('PixelTagDAO'))));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display($request) {
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('statusOptions', PixelTag::getStatusOptions());
		$templateMgr->assign('typeOptions', PixelTag::getTextTypeOptions());
		parent::display($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'privateCode',
				'publicCode',
				'domain',
				'dateOrderedYear',
				'dateOrderedMonth',
				'dateOrderedDay',
				'articleId',
				'vgWortTextType',
				'dateAssignedYear',
				'dateAssignedMonth',
				'dateAssignedDay',
				'dateRegisteredYear',
				'dateRegisteredMonth',
				'dateRegisteredDay'
			)
		);
		// Format the dates
		$this->_data['dateOrdered'] = $this->_data['dateOrderedYear'] . '-' . $this->_data['dateOrderedMonth'] . '-' . $this->_data['dateOrderedDay'] . ' 00:00:00';
		$this->_data['dateAssigned'] = $this->_data['dateAssignedYear'] . '-' . $this->_data['dateAssignedMonth'] . '-' . $this->_data['dateAssignedDay'] . ' 00:00:00';
		$this->_data['dateRegistered'] = $this->_data['dateRegisteredYear'] . '-' . $this->_data['dateRegisteredMonth'] . '-' . $this->_data['dateRegisteredDay'] . ' 00:00:00';
	}

	/**
	 * Save form.
	 */
	function execute() {
		$journalId = $this->journalId;

		$pixelTagDao =& DAORegistry::getDAO('PixelTagDAO');
		$pixelTag = new PixelTag();
		$pixelTag->setJournalId($journalId);
		$pixelTag->setPrivateCode($this->getData('privateCode'));
		$pixelTag->setPublicCode($this->getData('publicCode'));
		$pixelTag->setDomain($this->getData('domain'));
		$pixelTag->setDateOrdered(strtotime($this->getData('dateOrdered')));
		$pixelTag->setStatus(PT_STATUS_REGISTERED);
		$pixelTag->setArticleId((int)$this->getData('articleId'));
		$pixelTag->setTextType((int)$this->getData('vgWortTextType'));
		$pixelTag->setDateAssigned(strtotime($this->getData('dateAssigned')));
		$pixelTag->setDateRegistered(strtotime($this->getData('dateRegistered')));
		$pixelTagId = $pixelTagDao->insertObject($pixelTag);
	}
}

?>
