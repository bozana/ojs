<?php

/**
 * @file tests/functional/plugins/generic/lucene/FunctionalLucenePluginBaseTestCase.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginBaseTestCase
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies (base class with common functionality).
 */


import('tests.functional.pages.search.FunctionalSearchBaseTestCase');

class FunctionalLucenePluginBaseTestCase extends FunctionalSearchBaseTestCase {

	//
	// Implement template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'enabled', true);
	}
}
?>
