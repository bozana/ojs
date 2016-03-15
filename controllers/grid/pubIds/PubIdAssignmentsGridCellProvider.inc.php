<?php

/**
 * @file controllers/grid/pubIds/PubIdAssignmentsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportSubmissionsListGridCellProvider
 * @ingroup controllers_grid_pubIdExports
 *
 * @brief Class for a cell provider that can retrieve labels from submissions with pub ids
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class PubIdAssignmentsGridCellProvider extends GridCellProvider {
	/** @var string pub id type */
	var $_pubIdPlugin;

	/**
	 * Constructor
	 */
	function PubIdAssignmentsGridCellProvider($pubIdPlugin) {
		$this->_pubIdPlugin  = $pubIdPlugin;
		parent::GridCellProvider();
	}


	//
	// Template methods from GridCellProvider
	//
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$pubObject = $row->getData();
		$columnId = $column->getId();
		assert(is_a($pubObject, 'DataObject') && !empty($columnId));

		switch ($columnId) {
			case 'objectType':
				$pubObjectType = $this->_pubIdPlugin->getPubObjectType($pubObject);
				$displayPubObjectType = $this->_pubIdPlugin->getDisplayPubObjectType($pubObjectType);
				return array('label' => $displayPubObjectType);
				break;
			case 'objectName':
				$objectName = method_exists($pubObject, 'getLocalizedTitle') ? $pubObject->getLocalizedTitle() : $pubObject->getLocalizedName();
				return array('label' => $objectName);
				break;
			case 'pubId':
				$pubId = $this->_pubIdPlugin->getPubId($pubObject, true);
				return array('label' => $pubId ? $pubId : '-');
				break;
			case 'assign':
				return array('label' => '-');
				break;
		}

	}

}

?>
