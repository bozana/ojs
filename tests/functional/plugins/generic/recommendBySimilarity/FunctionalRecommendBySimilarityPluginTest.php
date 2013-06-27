<?php

/**
 * @file tests/functional/plugins/generic/recommendBySimilarity/FunctionalRecommendBySimilarityPluginTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalRecommendBySimilarityPluginTest
 * @ingroup tests_functional_plugins_generic_recommendBySimilarity
 * @see ArticleSearch
 *
 * @brief Integration/Functional test for the "recommend by similarity" plugin.
 *
 * FEATURE: recommend similar articles
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalRecommendBySimilarityPluginTest extends WebTestCase {
	private $pluginState;

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
		$this->pluginState = $pluginSettingsDao->getSetting(2, 'recommendbysimilarityplugin', 'enabled');
		$pluginSettingsDao->updateSetting(2, 'recommendbysimilarityplugin', 'enabled', true);
	}

	/**
	 * @see PKPTestCase::tearDown()
	 */
	protected function tearDown() {
		parent::tearDown();
		// Restore the plugin state.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(2, 'recommendbysimilarityplugin', 'enabled', $this->pluginState);
	}


	//
	// Tests
	//
	/**
	 * SCENARIO: recommend similar articles
	 *    WHEN I open an article abstract page
	 *    THEN I will see the first page of a paged list with all similar
	 *         articles.
	 *
	 * SCENARIO: similar articles (paging)
	 *   GIVEN I am looking at an article abstract page with a list of
	 *         similar articles
	 *    WHEN I click on the second page
	 *    THEN I'll see the second page of similar articles.
	 *
	 * SCENARIO: advanced search for similar articles
	 *   GIVEN I am looking at an article abstract page with a list of
	 *         similar articles
	 *    WHEN I click on the "start an advanced similarity search" link
	 *         below the article list
	 *    THEN I'll see the default search page with the same similarity
	 *         search preset.
	 */
	function testRecommendBySimilarityArticleList() {
		$sampleArticleUrl = $this->baseUrl . '/index.php/lucene-test/article/view/3';

		// Test article list
		$this->verifyAndOpen($sampleArticleUrl);

		$this->assertElementPresent('articlesBySimilarityList');
		$this->assertText('css=#articlesBySimilarityList ul>li', 'Another Author, Lucene Test Article 2 , lucene-test: Vol 1');

		// Test pagination
		$pageLinks = 'articlesBySimilarityPages';

		// Check the set of paging links below the result set.
		$this->assertText($pageLinks, 'regexp:^1 2 3 4 > >>');

		// Test turning the page: click the link for the next page.
		$this->clickAndWait('link=>');

		// Check the set of paging links of the second page.
		$this->assertText($pageLinks, 'regexp:^<< < 1 2 3 4 > >>');

		// Check the advanced search link.
		$this->clickAndWait('link=start an advanced similarity search');
		$this->waitForLocation('*lucene-test/search/search*');
		$this->waitForElementPresent('name=query');
		$this->assertValue('name=query', '*article*');
		$this->assertValue('name=query', '*test*');
	}
}
?>
