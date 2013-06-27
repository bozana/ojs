<?php

/**
 * @file tests/functional/pages/search/FunctionalSearchBaseTestCase.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalSearchBaseTestCase
 * @ingroup tests_functional_pages_search
 * @see ArticleSearch
 *
 * @brief Integration/Functional test for article search (base class with
 *   common functionality).
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalSearchBaseTestCase extends WebTestCase {

	protected $simpleSearchForm = '//form[@id="simpleSearchForm"]//';
	private $lucenePluginState;

	//
	// Implement template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		// Save the state of the Lucene plugin.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$this->lucenePluginState = $pluginSettingsDao->getSetting(0, 'luceneplugin', 'enabled');
	}

	/**
	 * @see PKPTestCase::tearDown()
	 */
	protected function tearDown() {
		parent::tearDown();
		// Restore the state of the Lucene plugin.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'enabled', $this->lucenePluginState);
	}


	//
	// Protected helper methods
	//
	/**
	 * Execute a simple search.
	 *
	 * @param $searchPhrase string
	 * @param $searchField
	 * @param $articles integer|array a list of article
	 *  ids that must appear in the result set
	 * @param $notArticles integer|array a list of article
	 *  ids that must not appear in the result. Can be '*'
	 *  to exclude any additional result.
	 * @param $locale string
	 * @param $journal string the context path of the journal to test
	 */
	protected function simpleSearch($searchPhrase, $searchField = 'query', $articles = array(), $notArticles = array(), $locale = 'en_US', $journal = 'lucene-test') {
		// Translate scalars to arrays.
		if (!is_array($articles)) $articles = array($articles);
		if ($notArticles !== '*' && !is_array($notArticles)) $notArticles = array($notArticles);

		try {
			// Open the test journal home page.
			$testJournal = $this->baseUrl . '/index.php/' . $journal;
			$this->verifyAndOpen($testJournal);

			// Select the locale.
			$selectedValue = $this->getSelectedValue('name=locale');
			if ($selectedValue != $locale) {
				$this->selectAndWait('name=locale', 'value=' . $locale);
			}

			// Hack to work around timing problems in phpunit 3.4...
			$this->waitForElementPresent($this->simpleSearchForm . 'input[@id="simpleQuery"]');
			$this->waitForElementPresent('name=searchField');

			// Enter the search phrase into the simple search field.
			$this->type($this->simpleSearchForm . 'input[@id="simpleQuery"]', $searchPhrase);

			// Select the search field.
			$this->select('name=searchField', 'value=' . $searchField);

			// Click the "Search" button.
			$this->clickAndWait($this->simpleSearchForm . 'input[@type="submit"]');

			// Check whether the result set contains the
			// sample articles.
			foreach($articles as $id) {
				$this->assertElementPresent('//table[@class="listing"]//a[contains(@href, "index.php/' . $journal . '/article/view/' . $id . '")]');
			}

			// Make sure that the result set does not contain
			// the articles in the "not article" list.
			if ($notArticles === '*') {

			} else {
				foreach($notArticles as $id) {
					$this->assertElementNotPresent('//table[@class="listing"]//a[contains(@href, "index.php/' . $journal . '/article/view/' . $id . '")]');
				}
			}
		} catch(Exception $e) {
			throw $this->improveException($e, "example $searchPhrase ($locale)");
		}
	}

	/**
	 * Execute a simple search across journals.
	 *
	 * @param $searchTerm string
	 */
	protected function simpleSearchAcrossJournals($searchTerm, $locale = 'en_US') {
		// Open the test installation's home page.
		$homePage = $this->baseUrl . '/index.php';
		$this->verifyAndOpen($homePage);

		// Select the locale.
		$selectedValue = $this->getSelectedValue('name=locale');
		if ($selectedValue != $locale) {
			$this->selectAndWait('name=locale', 'value=' . $locale);
		}

		// Hack to work around timing problems in phpunit 3.4...
		$this->waitForElementPresent($this->simpleSearchForm . 'input[@id="simpleQuery"]');
		$this->waitForElementPresent('name=searchField');

		// Enter the search term into the simple search box.
		$this->type($this->simpleSearchForm . 'input[@id="simpleQuery"]', $searchTerm);

		// Click the "Search" button.
		$this->clickAndWait($this->simpleSearchForm . 'input[@type="submit"]');
	}

	/**
	 * Check the ranking of four test articles.
	 * @param $expectedRanking array
	 * @param $searchFirst boolean
	 * @param $rowOffset int
	 */
	protected function checkRanking($expectedRanking, $searchFirst = true, $rowOffset = 4) {
		// Execute a search that shows four articles and check that
		// they are presented in the expected order.
		$weightedSearch = '+ranking +("article 1"^1.2 "article 2"^1.2 "article 3" "article 4")';
		if ($searchFirst) $this->simpleSearch($weightedSearch);
		$row = 3; // The first table row containing an article.
		foreach ($expectedRanking as $currentArticle) {
			$articleTitle = $this->getTable("css=table.listing.$row.1");
			self::assertEquals("Ranking Test Article $currentArticle", $articleTitle);
			$row += $rowOffset; // One result takes several rows (3 w/o highlighting, 4 when it is enabled).
		}
	}

	/**
	 * Check result ordering example.
	 * @param $example array
	 */
	protected function checkResultOrderingExample($example) {
		// Expand the example.
		list($selectField, $value, $expectedFirstResult) = $example;

		// Save order and direction for debugging.
		static $searchResultOrder, $searchResultOrderDir;
		$$selectField = $value;

		try {
			// Select the next order criterium or direction.
			if ($this->getSelectedValue($selectField) != $value) {
				$this->selectAndWait($selectField, "value=$value");
			}

			// Check the first result to make sure ordering works
			// correctly.
			if (is_scalar($expectedFirstResult)) $expectedFirstResult = array($expectedFirstResult);
			$this->assertAttribute(
				'css=table.listing a.file:first-child@href',
				'.*/article/view/(' . implode('|', $expectedFirstResult) . ')$'
			);
		} catch (Exception $e) {
			throw $this->improveException($e, "example $searchResultOrder - $searchResultOrderDir");
		}
	}

	/**
	 * Check the "similar documents" feature independently
	 * of the search implementation.
	 */
	protected function checkSimDocs() {
		// Execute a simple search that returns at least one result.
		$this->simpleSearch('lucene');

		// Check that the link "similar documents" is present.
		$this->assertElementPresent('link=similar documents');

		// Click one of the "similar documents" buttons.
		$this->clickAndWait('css=a.file[href="' . $this->baseUrl . '/index.php/lucene-test/search/similarDocuments?articleId=3"]');

		// Check that a search for similar articles has been executed.
		$this->waitForLocation('*lucene-test/search/search*');
		$this->waitForElementPresent('name=query');
		$this->assertValue('name=query', '*article*');
		$this->assertValue('name=query', '*test*');
	}
}
?>
