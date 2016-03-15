<?php

/**
 * @file controllers/grid/pubIds/PubIdExportIssuesListGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportIssuesListGridCellProvider
 * @ingroup controllers_grid_pubIds
 *
 * @brief Class for a cell provider that can retrieve labels from submissions with pub ids
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class PubIdExportIssuesListGridCellProvider extends DataObjectGridCellProvider {
	/** @var ImportExportPlugin */
	var $_plugin;

	/**
	 * Constructor
	 */
	function PubIdExportIssuesListGridCellProvider($plugin, $authorizedRoles = null) {
		$this->_plugin  = $plugin;
		if ($authorizedRoles) {
			$this->_authorizedRoles = $authorizedRoles;
		}
		parent::DataObjectGridCellProvider();
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
		$publishedIssue = $row->getData();
		$columnId = $column->getId();
		assert(is_a($publishedIssue, 'DataObject') && !empty($columnId));

		$contextId = $publishedIssue->getJournalId();
		$contextDao = Application::getContextDAO();
		$context = $contextDao->getById($contextId);

		switch ($columnId) {
			case 'id':
				return array('label' => $publishedIssue->getId());
				break;
			case 'identification':
				return array('label' => $publishedIssue->getIssueIdentification());
				break;
			case 'pubId':
				return array('label' => $publishedIssue->getStoredPubId($this->_plugin->getPubIdType()));
				break;
			case 'status':
				$status = $publishedIssue->getData($this->_plugin->getStatusSettingName());
				$statusMappings = $this->_plugin->getStatusMapping();
				if ($status) assert(array_key_exists($status, $statusMappings));
				return array('label' => $status ? $statusMappings[$status] : '-');
				break;
		}
	}

}

?>
