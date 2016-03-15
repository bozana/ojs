<?php

/**
 * @file controllers/grid/pubIds/PubIdAssignmentsGridRow.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdAssignmentsGridRow
 * @ingroup controllers_grid_pubIds
 *
 * @brief Language grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PubIdAssignmentsGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function PubIdAssignmentsGridRow() {
		parent::GridRow();
	}


	//
	// Overridden methods from GridRow
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		$rowData = $this->getData();
	}
}

?>
