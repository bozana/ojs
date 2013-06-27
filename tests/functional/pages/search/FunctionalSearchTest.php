<?php

/**
 * @file tests/functional/pages/search/FunctionalSearchTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalSearchTest
 * @ingroup tests_functional_pages_search
 * @see ArticleSearch
 *
 * @brief Integration/Functional test fir the OJS search.
 */


import('tests.functional.pages.search.FunctionalSearchBaseTestCase');

class FunctionalSearchTest extends FunctionalSearchBaseTestCase {

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('metrics');
	}

	//
	// Implement template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'enabled', false);
	}


	//
	// Tests
	//
	/**
	 * FEATURE: search result set sorting
	 *
	 * SCENARIO OUTLINE: Result ordering
	 *   GIVEN I am looking at the result page of a {search type}-journal
	 *         result set for the search phrase {keywords}
	 *    WHEN I select {order criterium} and {order direction}
	 *    THEN I will see a different result list re-ordered by the
	 *         changed criterium and in the given direction. This
	 *         can be seen by looking at the first {article id} in
	 *         the result set.
	 *
	 * EXAMPLES:
	 *   search type | keywords                    | order criterium  | order direction      | article id
	 *   ================================================================================================
	 *   single      | chicken AND (wings OR feet) | relevance        | descending (default) | 3
	 *   single      | chicken AND (wings OR feet) | relevance        | ascending            | 5
	 *   single      | chicken AND (wings OR feet) | author           | ascending (default)  | 4
	 *   single      | chicken AND (wings OR feet) | author           | descending           | 5
	 *   single      | chicken AND (wings OR feet) | publication date | descending (default) | 5
	 *   single      | chicken AND (wings OR feet) | article title    | ascending (default)  | 5
	 *   single      | chicken AND (wings OR feet) | article title    | descending           | 4
	 *   multi       | test NOT ranking            | issue publ. date | descending (default) | 3 or 4
	 *   multi       | test NOT ranking            | journal title    | ascending (default)  | 3 or 4
	 *   multi       | test NOT ranking            | journal title    | descending           | 1
	 *
	 * SCENARIO: Journal title ordering: single-journal search
	 *    WHEN I am doing a single-journal search
	 *    THEN I will not be able to order the result
	 *         set by journal title.
	 *
	 * SCENARIO: Journal title ordering: multi-journal search
	 *    WHEN I am doing a multi-journal search
	 *    THEN I can order the result set by journal
	 *         title.
	 */
	function testResultOrdering() {
		// Test ordering of a single-journal search.
		$singleJournalExamples = array(
			array('searchResultOrder', 'score', 3), // Default: descending
			array('searchResultOrderDir', 'asc', 5),
			array('searchResultOrder', 'authors', 4), // Default: ascending
			array('searchResultOrderDir', 'desc', 5),
			array('searchResultOrder', 'publicationDate', 5), // Default: descending
			array('searchResultOrder', 'title', 5), // Default: ascending
			array('searchResultOrderDir', 'desc', 4)
		);
		// Execute a query that produces a different score for all
		// articles in the result set.
		$this->simpleSearch('("test article" AND author) OR "chicken have wings" OR "authorname"');
		foreach($singleJournalExamples as $example) {
			$this->checkResultOrderingExample($example);
		}

		// Make sure that there is no journal-title ordering.
		$singleJournalOrderingOptions = $this->getSelectOptions('searchResultOrder');
		$this->assertFalse(in_array('Journal Title', $singleJournalOrderingOptions));

		// Test ordering of a multi-journal search.
		$multiJournalExamples = array(
			array('searchResultOrder', 'issuePublicationDate', array(3, 4)), // Default: descending
			array('searchResultOrder', 'journalTitle', array(3, 4)), // Default: ascending
			array('searchResultOrderDir', 'desc', 1)
		);
		$this->simpleSearchAcrossJournals('test NOT ranking');
		foreach($multiJournalExamples as $example) {
			$this->checkResultOrderingExample($example);
		}

		// Make sure that journal-title ordering is allowed.
		$multiJournalOrderingOptions = $this->getSelectOptions('searchResultOrder');
		$this->assertTrue(in_array('Journal Title', $multiJournalOrderingOptions));
	}

	/**
	 * FEATURE: search result set sorting by usage metric
	 *
	 * SCENARIO: sorting by metric effect (all time)
	 *   GIVEN I simulate a metrics table that establishes the following article
	 *         order by descending all-time usage statistics:
	 *           article 4, article 3, article 1, article 2
	 *     AND I executed a search that does not order articles by metric
	 *         [e.g. 'article']
	 *    WHEN I select the "Popularity (All Time)" order-by option
	 *    THEN the result will be re-ordered (default: descending order) by the
	 *         order established in the ranking file, i.e. article 4, 3, 1, 2.
	 *
	 * SCENARIO: sorting by metric effect (last month only)
	 *   GIVEN I simulate a metrics table that establishes the following article
	 *         order by descending all-time usage statistics:
	 *           article 4, article 3, article 1, article 2
	 *     AND I divide the statistics in time such that it establishes the
	 *         following article order by descending usage statistics for the
	 *         last month only:
	 *           article 4, article 3, article 2, article 1
	 *     AND I executed a search that does not order articles by metric
	 *         [e.g. 'article']
	 *    WHEN I select the "Popularity (Last Month)" order-by option
	 *    THEN the result will be re-ordered (default: descending order) by the
	 *         order established in the ranking file, i.e. article 4, 3, 2, 1.
	 */
	function testSortingByMetric() {
		// We have to enable a statistics plugin to test this feature.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'oasplugin', 'enabled', true);

		// Prepare the metrics table.
		// NB: We actually map the Gherkin article IDs to "real" article IDs
		// to make sure that the metrics DAO can handle them.
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$metricsDao->retrieve('TRUNCATE TABLE metrics');
		// The usage data of the current month will establish the order
		// article 4, 3, 2, 1 by last month's popularity.
		$record = array(
			'load_id' => 'functional test data',
			'assoc_type' => ASSOC_TYPE_ARTICLE,
			'day' => date('Ymd'),
			'metric_type' => 'oas::counter'
		);
		$metric = 5;
		foreach (array(9, 10, 11, 12) as $articleId) {
			$record['assoc_id'] = $articleId;
			$record['metric'] = $metric;
			$metricsDao->insertRecord($record);
			$metric += 5;
		}

		// Now add a single record older than one month that simulates
		// usage for article 1 higher than article 2.
		$record['day'] = date('Ymd', strtotime('-1 year'));
		$record['metric'] = 7;
		$record['assoc_id'] = 9;
		$metricsDao->insertRecord($record);

		// Make sure that we have additional popularity order-by options.
		$this->simpleSearch('article');
		$this->waitForElementPresent('name=searchResultOrder');
		$this->assertSelectHasOption('name=searchResultOrder', 'Popularity (All Time)');
		$this->assertSelectHasOption('name=searchResultOrder', 'Popularity (Last Month)');

		// If we sort by "Popularity (Last Month)" then we expect the result order 4, 3, 2, 1.
		$this->selectAndWait('name=searchResultOrder', "value=popularityMonth");
		$this->checkRanking(array(4, 3, 2, 1), false, 3);

		// If we sort by "Popularity (All Time)" then we expect articles one and two to be reversed.
		$this->selectAndWait('name=searchResultOrder', "value=popularityAll");
		$this->checkRanking(array(4, 3, 1, 2), false, 3);

		// Make a counter check.
		$metricsDao->purgeLoadBatch('functional test data');
		foreach (array(9, 10, 11, 12) as $articleId) {
			$record['assoc_id'] = $articleId;
			$record['metric'] = $metric;
			$metricsDao->insertRecord($record);
			$metric -= 5;
		}
		$this->refreshAndWait();
		$this->waitForElementPresent('name=searchResultOrder');
		$this->checkRanking(array(1, 2, 3, 4), false, 3);
	}

	/**
	 * FEATURE: search for similar documents
	 *
	 * SCENARIO: propose similar documents
	 *    WHEN I execute a simple search that returns at
	 *         least one result
	 *     AND the result has keywords set
	 *    THEN The result list will contain a button behind
	 *         each item of the result list: "similar documents"
	 *
	 * SCENARIO: find similar documents
	 *   GIVEN I executed a simple search that returned at
	 *         least one result
	 *     AND I see a "similar documents" button behind one or more
	 *         item(s) of the result list
	 *    WHEN I click the "similar documents" button of an item
	 *    THEN I'll see a result set containing articles containing
	 *         similar keywords as defined by solr's default
	 *         similarity algorithm.
	 */
	public function testSimilarDocuments() {
		$this->checkSimDocs();
	}
}
?>
