<?php

/**
 * @file plugins/importexport/doaj/filter/DOAJJsonFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJXmlFilter
 * @ingroup plugins_importexport_doaj
 *
 * @brief Class that converts an Article to a DOAJ JSON string.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');


class DOAJJsonFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function DOAJJsonFilter($filterGroup) {
		$this->setDisplayName('DOAJ JSON export');
		parent::NativeExportFilter($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.doaj.filter.DOAJJsonFilter';
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $pubObjects array Array of PublishedArticles
	 * @return JSON string
	 */
	function &process(&$pubObjects) {
		
		// Create the JSON string Article JSON example bibJson https://github.com/DOAJ/harvester/blob/9b59fddf2d01f7c918429d33b63ca0f1a6d3d0d0/service/tests/fixtures/article.py
		
		// because we are using the Bulk API the JSON needs to be an array []
		
		$json = '';
		
		
		return $json;
	}


}

?>
