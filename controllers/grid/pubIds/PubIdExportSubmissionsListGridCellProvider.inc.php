<?php

/**
 * @file controllers/grid/pubIds/PubIdExportSubmissionsListGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportSubmissionsListGridCellProvider
 * @ingroup controllers_grid_pubIds
 *
 * @brief Class for a cell provider that can retrieve labels from submissions with pub ids
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class PubIdExportSubmissionsListGridCellProvider extends DataObjectGridCellProvider {
	/** @var ImportExportPlugin */
	var $_plugin;

	/**
	 * Constructor
	 */
	function PubIdExportSubmissionsListGridCellProvider($plugin, $authorizedRoles = null) {
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
		$publishedSubmission = $row->getData();
		$columnId = $column->getId();
		assert(is_a($publishedSubmission, 'DataObject') && !empty($columnId));

		$contextId = $publishedSubmission->getContextId();
		$contextDao = Application::getContextDAO();
		$context = $contextDao->getById($contextId);

		switch ($columnId) {
			case 'id':
				return array('label' => $publishedSubmission->getId());
				break;
			case 'title':
				$this->_titleColumn = $column;
				$title = $publishedSubmission->getLocalizedTitle();
				if (empty($title)) $title = __('common.untitled');
				$authorsInTitle = $publishedSubmission->getShortAuthorString();
				$title = $authorsInTitle . '; ' . $title;
				return array('label' => $title);
				break;
			case 'author':
				return array('label' => $publishedSubmission->getAuthorString(true));
				break;
			case 'issue':
				assert(is_a($publishedSubmission, 'PublishedArticle'));
				$issueId = $publishedSubmission->getIssueId();
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issue = $issueDao->getById($issueId, $contextId);
				return array('label' => $issue->getIssueIdentification());
				break;
			case 'pubId':
				return array('label' => $publishedSubmission->getStoredPubId($this->_plugin->getPubIdType()));
				break;
			case 'status':
				$status = $publishedSubmission->getData($this->_plugin->getStatusSettingName());
				$statusMappings = $this->_plugin->getStatusMapping();
				if ($status) assert(array_key_exists($status, $statusMappings));
				return array('label' => $status ? $statusMappings[$status] : '-');
				break;
		}
	}

}

?>
