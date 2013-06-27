<?php

/**
 * @file tests/functional/plugins/generic/lucene/FunctionalLucenePluginRankingByMetricTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginRankingByMetricTest
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the "ranking-by-metric" and
 * "sorting-by-metric" features of the lucene plug-in.
 *
 * FEATURE: ranking and sorting by metric
 */


import('tests.functional.plugins.generic.lucene.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginRankingByMetricTest extends FunctionalLucenePluginBaseTestCase {
	private $tempDir, $extFilesDir;

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('plugin_settings', 'metrics');
	}

	/**
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// Make sure that relevant features are disabled by default.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'pullIndexing', false);
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', false);
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'sortingByMetric', false);

		// We have to enable a statistics plugin to test this feature.
		$pluginSettingsDao->updateSetting(0, 'oasplugin', 'enabled', true);

		// Move existing external field files to a temporary directory.
		$this->tempDir = tempnam(sys_get_temp_dir(), 'pkp');
		unlink($this->tempDir);
		mkdir($this->tempDir);
		$this->tempDir .= DIRECTORY_SEPARATOR;
		$this->extFilesDir = 'files' . DIRECTORY_SEPARATOR . 'lucene' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
		foreach (glob($this->extFilesDir . 'external_usageMetric*') as $source) {
			rename($source, $this->tempDir . basename($source));
		}
	}

	/**
	 * @see WebTestCase::tearDown()
	 */
	protected function tearDown() {
		// Delete external field files left over from tests.
		foreach (glob($this->extFilesDir . 'external_usageMetric*') as $source) {
			unlink($source);
		}

		// Restore external field files.
		foreach (glob($this->tempDir . 'external_usageMetric*') as $source) {
			rename($source, $this->extFilesDir . basename($source));
		}
		rmdir($this->tempDir);

		parent::tearDown();
	}


	//
	// Tests
	//
	/**
	 * SCENARIO: generate external boost file (disabled)
	 *   GIVEN I disabled the ranking-by-metric or pull indexing feature
	 *    WHEN I access the .../index/lucene/usageMetricBoost endpoint
	 *    THEN I download an empty text file.
	 *
	 * SCENARIO OUTLINE: generate external boost file for pull indexing
	 *   GIVEN I enabled the ranking-by-metric and the pull indexing feature
	 *     AND I collected the following usage data for the main metric (last month):
	 *           article 1: no usage data
	 *           article 2: 10 usage events
	 *           article 3: 15 usage events
	 *           article 4: 30 usage events
	 *     AND I collected the additional usage data for the main metric (one year ago):
	 *           article 1: 40 usage events
	 *    WHEN I access the .../index/lucene/usageMetricBoost endpoint with the
	 *         filter parameter set to {time span}
	 *    THEN I download a text {file with boost data} normalized by the formula
	 *         2 ^ ((2 * value / max-value) - 1)
	 *     AND the file is ordered by installation and article ID
	 *
	 * EXAMPLES:
	 *   time span | file with boost data
	 *   ==========|=====================================================
	 *   month     | test-inst-1: no entry (i.e. defaults to 1.0 in Solr)
	 *             | test-inst-2=1.25992
	 *             | test-inst-3=1.41421
	 *             | test-inst-4=2
	 *   all       | test-inst-1=2
	 *             | test-inst-2=1.18921
	 *             | test-inst-3=1.29684
	 *             | test-inst-4=1.68179
	 */
	function testGenerateExternalBoostFile() {
		// Prepare the metrics table.
		// NB: We actually map the Gherkin article IDs to "real" article IDs
		// to make sure that the metrics DAO can handle them.
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$metricsDao->retrieve('TRUNCATE TABLE metrics');
		$records = array(                            // article 1: no record
			array('assoc_id' => 10, 'metric' => 10), // article 2: 10 usage events
			array('assoc_id' => 11, 'metric' => 15), // article 3: 15 usage events
			array('assoc_id' => 12, 'metric' => 30)  // article 4: 30 usage events
		);
		foreach ($records as $record) {
			$record['load_id'] = 'functional test data';
			$record['assoc_type'] = ASSOC_TYPE_ARTICLE;
			$record['day'] = date('Ymd');
			$record['metric_type'] = 'oas::counter';
			$metricsDao->insertRecord($record);
		}
		$record['day'] = date('Ymd', strtotime('-1 year'));
		$record['metric'] = 40;
		$record['assoc_id'] = 9;
		$metricsDao->insertRecord($record);

		// Disable the ranking-by-metric feature.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', false);

		// Check that the boost file is empty.
		$handlerUrl = $this->baseUrl . '/index.php/index/lucene/usageMetricBoost?filter=';
		$curlCh = curl_init();
		curl_setopt($curlCh, CURLOPT_URL, $handlerUrl . 'all');
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curlCh);
		$this->assertEquals('', $response);

		// Enable the ranking-by-metric and pull indexing feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', true);
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'pullIndexing', true);

		// Check the "all time" boost file.
		$response = curl_exec($curlCh);
		$this->assertEquals("test-inst-9=2\ntest-inst-10=1.18921\ntest-inst-11=1.29684\ntest-inst-12=1.68179\n", $response);

		// Check "last month"'s boost file.
		curl_setopt($curlCh, CURLOPT_URL, $handlerUrl . 'month');
		$response = curl_exec($curlCh);
		$this->assertEquals("test-inst-10=1.25992\ntest-inst-11=1.41421\ntest-inst-12=2\n", $response);
	}

	/**
	 * SCENARIO: update boost file (button)
	 *   GIVEN I enabled the ranking-by-metric feature
	 *    WHEN I open the plugin settings page
	 *    THEN I'll see a button "Update Ranking Data"
	 *
	 * SCENARIO: update boost file (execute)
	 *   GIVEN I enabled the ranking-by-metric feature
	 *     AND I am on the plugin settings page
	 *    WHEN I click on the "Update Ranking Data" button
	 *    THEN current usage statistics will be copied to the index
	 *     AND I'll see the effect immediately in the search results.
	 */
	function testUpdateBoostFile() {
		// Disable the ranking-by-metric feature.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', false);

		// Open the plugin settings page.
		$this->logIn();
		$pluginSettings = $this->baseUrl . '/index.php/lucene-test/manager/plugin/generic/luceneplugin/settings';
		$this->verifyAndOpen($pluginSettings);

		// Check that there is no "Update Ranking Data" button.
		$this->assertElementNotPresent('name=updateBoostFile');

		// Enable the ranking-by-metric feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', true);

		// Refresh the plugin settings page.
		$this->refreshAndWait();

		// Check that the "Update Ranking Data" button is now present.
		$this->waitForElementPresent('name=updateBoostFile');

		// Copy "old" test ranking data to the index.
		$this->copyTestRankingFiles();

		// Check that the ranking corresponds to the "old" ranking data.
		$this->checkRanking(array(4, 3, 2, 1));

		// Prepare "new" test ranking data in the metrics table.
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$metricsDao->retrieve('TRUNCATE TABLE metrics');
		$records = array(                            // article 2: no record, defaults to boost 1.0
				array('assoc_id' => 9, 'metric' => 15), // article 1: 10 usage events
				array('assoc_id' => 11, 'metric' => 5), // article 3: 5 usage events
				array('assoc_id' => 12, 'metric' => 30)  // article 4: 30 usage events
		);
		foreach ($records as $record) {
			$record['load_id'] = 'functional test data';
			$record['assoc_type'] = ASSOC_TYPE_ARTICLE;
			$record['day'] = '20130415';
			$record['metric_type'] = 'oas::counter';
			$metricsDao->insertRecord($record);
		}

		// Click the "Update Ranking Data" button.
		$this->verifyAndOpen($pluginSettings);
		$this->waitForElementPresent('name=updateBoostFile');
		$this->clickAndWait('name=updateBoostFile');

		// Check that the new ranking data immediately affects search results.
		$this->checkRanking(array(4, 1, 3, 2));
	}


	/**
	 * SCENARIO: ranking-by-metric effect
	 *   GIVEN I disabled the ranking-by-metric feature
	 *     AND I executed a search that shows four articles with ranking
	 *         weights such that their ranking is uniquely defined as
	 *         1) "article 1", 2) "article 2", 3) "article 3", 4) "article 4"
	 *         [e.g. '+ranking +("article 1"^1.2 "article 2"^1.2 "article 3"
	 *         "article 4")']
	 *     AND I place a external ranking file into the lucene index folder
	 *         with the following metric boost data:
	 *           article 1: 1 (no explicit data - via default value)
	 *           article 2: 1.1
	 *           article 3: 1.5
	 *           article 4: 2
	 *    WHEN I enable the ranking-by-metric feature
	 *     AND I re-execute the exact same search
	 *    THEN I'll see the ranking order of the articles reversed.
	 */
	function testRankingByMetricEffect() {
		// Activate the external ranking files.
		$this->copyTestRankingFiles();

		// Disable the ranking-by-metric feature.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', false);

		// Check the initial ranking.
		$this->checkRanking(array(1, 2, 3, 4));

		// Enable the ranking-by-metric feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'rankingByMetric', true);

		// Check that the ranking order of the articles was reversed.
		$this->checkRanking(array(4, 3, 2, 1));
	}

	/**
	 * SCENARIO: sorting by metric option
	 *   GIVEN I enabled the sorting-by-metric feature
	 *    WHEN I execute a search
	 *    THEN I'll see additional order-by options "Popularity (All Time)"
	 *         and "Popularity (Last Month)".
	 *
	 * SCENARIO OUTLINE: sorting by metric effect
	 *   GIVEN I enabled the sorting-by-metric feature
	 *     AND I placed an external ranking file into the lucene index
	 *         folder that establishes a well-ordered {ranking order}
	 *     AND I executed a search that does not order articles by metric
	 *         [e.g. '+ranking +("article 1"^1.2 "article 2"^1.2 "article 3"
	 *         "article 4")']
	 *    WHEN I select the "Popularity ({time filter})" order-by option
	 *    THEN the result will be re-ordered (default: descending order) by the
	 *         {ranking order} established in the ranking file.
	 *
	 * EXAMPLES:
	 *   time filter | ranking order
	 *   ============|===========================================
	 *   All Time    | article 4, article 3, article 2, article 1
	 *   Last Month  | article 3, article 4, article 1, article 2
	 */
	function testSortingByMetric() {
		// Execute a search (not influenced by statistics).
		$this->checkRanking(array(1, 2, 3, 4));

		// Make sure that the popularity options are missing unless
		// the sorting-by-metric feature was enabled.
		$this->waitForElementPresent('name=searchResultOrder');
		$this->assertSelectNotHasOption('name=searchResultOrder', 'Popularity (All Time)');
		$this->assertSelectNotHasOption('name=searchResultOrder', 'Popularity (Last Month)');

		// Enable the sorting-by-metric feature.
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'sortingByMetric', true);

		// Execute a search (which should not be influenced by statistics
		// even if we load a ranking file).
		$this->copyTestRankingFiles();
		$this->checkRanking(array(1, 2, 3, 4));

		// Make sure that we have a additional poprularity order-by options.
		$this->waitForElementPresent('name=searchResultOrder');
		$this->assertSelectHasOption('name=searchResultOrder', 'Popularity (All Time)');
		$this->assertSelectHasOption('name=searchResultOrder', 'Popularity (Last Month)');

		// Check whether we get the expected result orders for "all time" and
		// "last month" popularity ordering.
		$this->selectAndWait('name=searchResultOrder', "value=popularityAll");
		$this->checkRanking(array(4, 3, 2, 1), false);
		$this->selectAndWait('name=searchResultOrder', "value=popularityMonth");
		$this->checkRanking(array(3, 4, 1, 2), false);
	}


	//
	// Private helper methods.
	//
	/**
	 * Copy an external ranking file to the Solr server and
	 * delete the external file cache.
	 */
	private function copyTestRankingFiles() {
		// Copy the external ranking test files into the lucene data folder.
		foreach (array('All', 'Month') as $filter) {
			$fileName = "external_usageMetric$filter.00000000";
			copy(
				dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $fileName,
				$this->extFilesDir . $fileName
			);
		}

		// Make the Lucene server aware of the new files.
		$this->verifyAndOpen('http://localhost:8983/solr/ojs/reloadExternalFiles');
	}
}
?>
