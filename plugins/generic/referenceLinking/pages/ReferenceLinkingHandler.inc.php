<?php

/**
 * @file plugins/generic/referenceLinking/pages/ReferenceLinkingHandler.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferenceLinkingHandler
 * @ingroup plugins_generic_referenceLinking
 *
 * @brief Handle requests for Reference Linking functions.
 */

import('classes.handler.Handler');

class ReferenceLinkingHandler extends Handler {

	/**
	 * Display Reference Linking test page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$journal = $request->getJournal();

		$crossrefUrl = null;
		if (function_exists('getallheaders')) {
			$file = 'debugRefLinking.txt';
			$current = file_get_contents($file);
	    	$current .= print_r("++ getallheaders exist: \n", true);
			foreach (getallheaders() as $name => $value) {
       			if (strpos($name, 'crossref') !== false) {
					if ($name == 'CROSSREF-RETRIEVE-URL') {
						$crossrefUrl = $value;
					}
				}
	    		$current .= print_r("$name: $value\n", true);
			}
	    	$current .= print_r("++++++\n", true);
			file_put_contents($file, $current);
		} else {
			$file = 'debugRefLinking.txt';
			$current = file_get_contents($file);
			$current .= print_r("++ getallheaders DOES NOT exist: \n", true);
       		foreach ($_SERVER as $name => $value) {
       			if (strpos($name, 'crossref') !== false) {
       				if ($name == 'CROSSREF-RETRIEVE-URL') {
						$crossrefUrl = $value;
					}
       				$current .= print_r("$name: $value\n", true);
       			}
       		}
	    	$current .= print_r("++++++\n", true);
			file_put_contents($file, $current);
		}

		$file = 'debugRefLinking.txt';
		$current = file_get_contents($file);
		$current .= print_r("++++ CROSSREF-RETRIEVE-URL : \n", true);
	    $current .= print_r("$crossrefUrl\n", true);
	    $current .= print_r("++++++\n", true);
	    file_put_contents($file, $current);

	    if ($crossrefUrl) {
			$curlCh = curl_init();
			if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
				curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
				curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
				if ($username = Config::getVar('proxy', 'username')) {
					curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
				}
			}
			curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
			//$username = $this->getSetting($context->getId(), 'username');
			//$password = $this->getSetting($context->getId(), 'password');
	    	$username = 'pkptemp';
	    	$password = 'pkp914';
			curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");

	    	curl_setopt($curlCh, CURLOPT_URL, $crossrefUrl);
			$response = curl_exec($curlCh);
			if ($response && curl_getinfo($curlCh, CURLINFO_HTTP_CODE) == 200) {
				$file = 'debugRefLinking.txt';
				$current = file_get_contents($file);
				$current .= print_r("++++ CROSSREF-RETRIEVE-URL content : \n", true);
			    $current .= print_r("$response\n", true);
			    $current .= print_r("++++++\n", true);
			    file_put_contents($file, $current);
			}
	    	curl_close($curlCh);
	    }

		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$referenceLinkingPlugin = $this->_getReferenceLinkingPlugin();

		$templateMgr->addStyleSheet('referenceLinking', $request->getBaseUrl() . '/' . $referenceLinkingPlugin->getStyleSheet());
		$templateMgr->display($referenceLinkingPlugin->getTemplatePath() . 'referenceLinking.tpl');
	}

	/**
	 * Get the plugin object
	 * @return ReferenceLinkingPlugin
	 */
	function _getReferenceLinkingPlugin() {
		$plugin = PluginRegistry::getPlugin('generic', REFERENCE_LINKING_PLUGIN_NAME);
		return $plugin;
	}

}

?>
