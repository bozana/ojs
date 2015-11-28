<?php

/**
 * @file controllers/tab/issueEntry/form/PublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicIdentifiersForm
 * @ingroup controllers_tab_issueEntry_form_PublicIdentifiersForm
 *
 * @brief Displays a submission's pub ids form.
 */

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.plugins.PKPPubIdPluginHelper');

class PublicIdentifiersForm extends Form {

	/** @var int The context id */
	var $_contextId;

	/** @var object The pub object the identifiers are edited of
	 * (Submission, Issue)
	 */
	var $_pubObject;

	/** @var int The current stage id, WORKFLOW_STAGE_ID_ */
	var $_stageId;

	/**
	 * @var array Parameters to configure the form template.
	 */
	var $_formParams;

	/**
	 * Constructor.
	 * @param $pubObject object
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function PublicIdentifiersForm($pubObject, $stageId = null, $formParams = null) {
		parent::Form('controllers/tab/pubIds/form/publicIdentifiersForm.tpl');

		$this->_pubObject = $pubObject;
		$this->_stageId = $stageId;
		$this->_formParams = $formParams;

		$request = Application::getRequest();
		$context = $request->getContext();
		$this->_contextId = $context->getId();

		$this->addCheck(new FormValidatorPost($this));

		// action links for pub id reset requests
		import('lib.pkp.classes.plugins.PKPPubIdPluginHelper');
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->setLinkActions($this->getContextId(), $this, $pubObject);

		if (is_a($pubObject, 'Issue')) {

		}
	}

	/**
	 * Fetch the HTML contents of the form.
	 * @param $request PKPRequest
	 * return string
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $this->getContextId());
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->assign('pubObject', $this->getPubObject());
		$templateMgr->assign('stageId', $this->getStageId());
		$templateMgr->assign('formParams', $this->getFormParams());
		return parent::fetch($request);
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$pubObject = $this->getPubObject();
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->init($this->getContextId(), $this, $pubObject);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the pub object
	 * @return object
	 */
	function getPubObject() {
		return $this->_pubObject;
	}

	/**
	 * Get the stage id
	 * @return integer WORKFLOW_STAGE_ID_
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the context id
	 * @return integer
	 */
	function getContextId() {
		return $this->_contextId;
	}

	/**
	 * Get the extra form parameters.
	 */
	function getFormParams() {
		return $this->_formParams;
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->readInputData($this->getContextId(), $this);
	}

	/**
	 * @copydoc Form::validate()
	 */
	function validate() {
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->validate($this->getContextId(), $this, $this->getPubObject());
		return parent::validate();
	}

	/**
	 * Save the metadata and store the catalog data for this published
	 * monograph.
	 */
	function execute($request) {
		parent::execute($request);

		$pubObject = $this->getPubObject();
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->execute($this->getContextId(), $this, $pubObject);

		if (is_a($pubObject, 'Article')) {
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$articleDao->updateObject($pubObject);
		} elseif (is_a($pubObject, 'SubmissionFile')) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
			$submissionFileDao->updateObject($pubObject);
		} elseif (is_a($pubObject, 'Issue')) {
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issueDao->updateObject($pubObject);
		}
	}

	/**
	 * Clear pub id.
	 * @param $actionType string (s. pub id plugin function getClearActionName)
	 */
	function clearPubId($actionType) {
		$pubIdPluginHelper = new PKPPubIdPluginHelper();
		$pubIdPluginHelper->clearPubId($actionType, $this->getPubObject());
	}

	/**
	 * Clear or exclude issue objects pub ids.
	 * @param $actionType string
	 *  (s. pub id plugin function getClearActionName and getExcludeFormFieldName)
	 */
	function issueObjectsPubIdsActions($actionType) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				if ($pubIdPlugin->getClearActionName() == $actionType) { // clear
					$pubIdPlugin->issueObjectsPubIdsActions(false, true, $this->getPubObject());
				} elseif ($pubIdPlugin->getExcludeFormFieldName() == $actionType) { // exclude
					$pubIdPlugin->issueObjectsPubIdsActions(true, false, $this->getPubObject());
				}
			}
		}
	}

}

?>
