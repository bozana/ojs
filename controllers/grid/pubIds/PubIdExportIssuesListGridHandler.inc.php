<?php

/**
 * @file controllers/grid/pubIds/PubIdExportIssuesListGridHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportIssuesListGridHandler
 * @ingroup controllers_grid_pubIds
 *
 * @brief Handle exportable submissions with pub ids list grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');

import('controllers.grid.pubIds.PubIdExportIssuesListGridCellProvider');

class PubIdExportIssuesListGridHandler extends GridHandler {
	/** @var boolean true if the current user has a managerial role */
	var $_isManager;

	/** @var ImportExportPlugin */
	var $_plugin;

	/**
	 * Constructor
	 */
	function PubIdExportIssuesListGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow')
		);
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		// Load submission-specific translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_PKP_SUBMISSION
		);

		// Fetch the authorized roles and determine if the user is a manager.
		$authorizedRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		$this->_isManager = in_array(ROLE_ID_MANAGER, $authorizedRoles);

		$pluginCategory = $request->getUserVar('category');
		$pluginPathName = $request->getUserVar('plugin');
		$this->_plugin = PluginRegistry::loadPlugin($pluginCategory, $pluginPathName);

		// Grid columns.
		$cellProvider = new PubIdExportIssuesListGridCellProvider($this->_plugin, $authorizedRoles);
		$this->addColumn(
			new GridColumn(
				'id',
				null,
				__('common.id'),
				'controllers/grid/gridCell.tpl',
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 10)
			)
		);
		$this->addColumn(
			new GridColumn(
				'identification',
				'issue.issue',
				null,
				null,
				$cellProvider,
				array('html' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);
		$this->addColumn(
			new GridColumn(
				'pubId',
				null,
				$this->_plugin->getPubIdDisplayType(),
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 15)
			)
		);
		$this->addColumn(
			new GridColumn(
				'status',
				'common.status',
				null,
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 10)
			)
		);
	}


	//
	// Implemented methods from GridHandler.
	//

	/**
	 * Get the row handler - override the parent row handler. We do not need grid row actions.
	 * @return GridRow
	 */
	protected function getRowInstance() {
		return new GridRow();
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.selectableItems.SelectableItemsFeature');
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new SelectableItemsFeature(), new PagingFeature());
	}

	/**
	 * @copydoc GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		return array_merge(parent::getRequestArgs(), array('category' => $this->_plugin->getCategory(), 'plugin' => basename($this->_plugin->getPluginPath())));
	}

	/**
	 * @copydoc GridHandler::isDataElementSelected()
	 */
	function isDataElementSelected($gridDataElement) {
		return false; // Nothing is selected by default
	}

	/**
	 * @copydoc GridHandler::getSelectName()
	 */
	function getSelectName() {
		return 'selectedIssues';
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$context = $request->getContext();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		return $issueDao->getByPubIdType($this->_plugin->getPubIdType(), $context->getId(), $this->getGridRangeInfo($request, $this->getId()));
	}

}

?>
