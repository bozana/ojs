<?php

/**
 * @file controllers/grid/pubIds/PubIdAssignmentsGridHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdAssignmentsGridHandler
 * @ingroup controllers_grid_pubIds
 *
 * @brief Handle pub ids assignment list grid requests.
 */

// Import grid base classes.
import('lib.pkp.classes.controllers.grid.GridHandler');

import('controllers.grid.pubIds.PubIdAssignmentsGridRow');

class PubIdAssignmentsGridHandler extends GridHandler {

	/** @var PubIdPlugin pub id plugin */
	var $_pubIdPlugin;

	/** @var string publication object tpye ('Issue' or 'Article') */
	var $_pubObjectType;

	/** @var integer publication object id */
	var $_pubObjectId;

	/**
	 * Constructor
	 */
	function PubIdAssignmentsGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
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

		// Load specific translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_PKP_SUBMISSION
		);

		$context = $request->getContext();
		$pubIdType = $request->getUserVar('pubIdType');
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $context->getId());
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				if ($pubIdPlugin->getPubIdType() == $pubIdType) {
					$this->_pubIdPlugin  = $pubIdPlugin;
					break;
				}
			}
		}
		$this->_pubObjectType = $request->getUserVar('pubObjectType');
		$this->_pubObjectId = $request->getUserVar('pubObjectId');

		// Grid columns.
		import('controllers.grid.pubIds.PubIdAssignmentsGridCellProvider');
		$pubIdAssignmentsGridCellProvider = new PubIdAssignmentsGridCellProvider($this->_pubIdPlugin);

		$this->addColumn(
			new GridColumn(
				'pubId',
				null,
				$this->_pubIdPlugin->getPubIdDisplayType(),
				null,
				$pubIdAssignmentsGridCellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'objectType',
				'common.object',
				null,
				null,
				$pubIdAssignmentsGridCellProvider,
				array('width' => 15)
			)
		);
		$this->addColumn(
			new GridColumn(
				'objectName',
				'common.name',
				null,
				null,
				$pubIdAssignmentsGridCellProvider
			)
		);

	}


	//
	// Implemented methods from GridHandler.
	//

	/**
	 * @see GridHandler::getRowInstance()
	 * @return PubIdAssignmentsGridRow
	 */
	function &getRowInstance() {
		$row = new PubIdAssignmentsGridRow();
		return $row;
	}


	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		$context = $request->getContext();
		$dataArray = $this->_pubIdPlugin->getSubPubObjects($this->_pubObjectType, $this->_pubObjectId, $context->getId());
		return $dataArray;

	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		if (!$request->getUserVar('hideSelectColumn')) {
			import('lib.pkp.classes.controllers.grid.feature.selectableItems.SelectableItemsFeature');
			return array(new SelectableItemsFeature());
		} else {
			return array();
		}
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
		return 'selectedObjects';
	}

}

?>
