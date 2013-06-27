<?php

/**
 * @file tests/functional/plugins/generic/recommendByAuthor/FunctionalRecommendByAuthorPluginTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalRecommendByAuthorPluginTest
 * @ingroup tests_functional_plugins_generic_recommendByAuthor
 * @see ArticleSearch
 *
 * @brief Integration/Functional test for the "recommend by author" plugin.
 *
 * FEATURE: recommend most-read articles by the same author
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalRecommendByAuthorPluginTest extends WebTestCase {
	private $pluginStateRBA, $pluginStateOAS;

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('plugin_settings', 'metrics');
	}

	//
	// Implement template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		// Enable the plugin for the lucene-test journal.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$this->pluginStateRBA = $pluginSettingsDao->getSetting(2, 'recommendbyauthorplugin', 'enabled');
		$this->pluginStateOAS = $pluginSettingsDao->getSetting(2, 'oasplugin', 'enabled');
		$pluginSettingsDao->updateSetting(2, 'recommendbyauthorplugin', 'enabled', true);
		$pluginSettingsDao->updateSetting(0, 'oasplugin', 'enabled', true);
	}

	/**
	 * @see PKPTestCase::tearDown()
	 */
	protected function tearDown() {
		parent::tearDown();
		// Restore the plugin state.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(2, 'recommendbyauthorplugin', 'enabled', $this->pluginStateRBA);
		$pluginSettingsDao->updateSetting(0, 'oasplugin', 'enabled', $this->pluginStateOAS);
	}

	//
	// Tests
	//
	/**
	 * SCENARIO: recommend most-read articles by the same author
	 *    WHEN I open an article page with an article by the test author
	 *         "Arthur McAutomatic"
	 *    THEN I will see the first page of a paged list with all articles
	 *         of that author.
	 *
	 * SCENARIO: articles recommended by author (paging)
	 *   GIVEN I am looking at an article page with an article by the
	 *         test author "Arthur McAutomatic"
	 *    WHEN I click on the second page
	 *    THEN I'll see the second page of all articles of that author.
	 */
	function testRecommendByAuthorArticleList() {
		$sampleArticleUrl = $this->baseUrl . '/index.php/lucene-test/article/view/23';

		// Test article list
		$this->verifyAndOpen($sampleArticleUrl);

		$this->assertElementPresent('articlesBySameAuthorList');
		$this->assertText('css=#articlesBySameAuthorList ul>li', 'Arthur McAutomatic, Ranking Test Article 1 , lucene-test: Vol 1');

		// Test pagination
		$pageLinks = 'articlesBySameAuthorPages';

		// Check the set of paging links below the result set.
		$this->assertText($pageLinks, 'regexp:^1 2 3 > >>');

		// Test turning the page: click the link for the next page.
		$this->clickAndWait('link=>');

		// Check the set of paging links of the second page.
		$this->assertText($pageLinks, 'regexp:^<< < 1 2 3 > >>');
	}
}
?>
