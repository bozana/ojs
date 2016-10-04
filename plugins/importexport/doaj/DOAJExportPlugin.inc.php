<?php

/**
 * @file plugins/importexport/doaj/DOAJExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJExportPlugin
 * @ingroup plugins_importexport_doaj
 *
 * @brief DOAJ export plugin
 */

import('classes.plugins.PubObjectsExportPlugin');

define('DOAJ_XSD_URL', 'http://www.doaj.org/schemas/doajArticles.xsd');

define('DOAJ_API_DEPOSIT_OK', 201);

define('DOAJ_API_URL', 'http://doaj.org/api/v1/');
define('DOAJ_API_URL_DEV', 'http://testdoaj.cottagelabs.com/api/v1/');
define('DOAJ_API_OPERATION', 'bulk/articles');
# We should probably use bulk articles: "A list/array of article JSON objects that you would like to create or update"?
# /api/v1/articles 
# /api/v1/bulk/articles 

class DOAJExportPlugin extends PubObjectsExportPlugin {
	/**
	 * Constructor
	 */
	function DOAJExportPlugin() {
		parent::PubObjectsExportPlugin();
	}

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'DOAJExportPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.doaj.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.doaj.description');
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	function display($args, $request) {
		parent::display($args, $request);
		switch (array_shift($args)) {
			case 'index':
			case '':
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
		}
	}

	/**
	 * Get the plugin ID used as plugin settings prefix.
	 * @return string
	 */
	function getPluginSettingsPrefix() {
		return 'doaj';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>doaj-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'DOAJExportDeployment';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'DOAJSettingsForm';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::depositXML()
	 */
function depositXML($objects, $context, $filename) {
		
		$curlCh = curl_init();
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

		# filename; Article JSON example https://github.com/DOAJ/harvester/blob/9b59fddf2d01f7c918429d33b63ca0f1a6d3d0d0/service/tests/fixtures/article.py
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $jsondata );

		$endpoint = ($this->isTestMode($context) ? DOAJ_API_URL_DEV : DOAJ_API_URL);
		$apiKey = $this->getSetting($context->getId(), 'apiKey');
		$params = 'api_key=' . $apiKey;

		curl_setopt(
			$curlCh,
			CURLOPT_URL,
			$endpoint . DOAJ_API_OPERATION . (strpos($endpoint,'?')===false?'?':'&') . $params
		);
		
		$response = curl_exec($curlCh);

		if ($response === false) {
			$result = array(array('plugins.importexport.doaj.register.error.mdsError', 'No response from server.'));
		} elseif ( $status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE) != DOAJ_API_DEPOSIT_OK ) {
			$result = array(array('plugins.importexport.doaj.register.error.mdsError', "$status - $response"));
		} else {
			// Deposit was received
			$result = true;
			foreach ($objects as $object) {
				// set the status
				$object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_REGISTERED);
				// Update the object
				$this->updateObject($object);
			}
		}
		curl_close($curlCh);
		return $result;
		
	}
}

?>
