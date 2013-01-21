<?php

/**
 * @file plugins/generic/oas/OasPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OasPlugin
 * @ingroup plugins_generic_oas
 *
 * @brief OA-S plugin - turns OJS into a OA-S data provider.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

// Our own and OA-S classification types.
define('OAS_PLUGIN_CLASSIFICATION_BOT', 'bot');
define('OAS_PLUGIN_CLASSIFICATION_ADMIN', 'administrative');

// Maximum time (in minutes) to stage usage events.
define('OAS_PLUGIN_MAX_STAGING_TIME', '15');

// Time interval (in minutes) between two SALT download attempts.
define('OAS_PLUGIN_SALT_URL', 'https://oas.sulb.uni-saarland.de/salt/salt_value.txt');
define('OAS_PLUGIN_SALT_DOWNLOAD_INTERVAL', '15');

class OasPlugin extends GenericPlugin {

	/** @var integer */
	var $_currentEventId;

	/** @var OasEventStagingDAO */
	var $_oasEventStagingDao;



	/**
	 * @see PKPPlugin::register()
	 */
	public function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;

		if($success && $this->getEnabled()) {
			// Hook to register the report plugin.
			HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));

			// Hooks to log usage events.
			HookRegistry::register('TemplateManager::display', array($this, 'startUsageEvent'));
			HookRegistry::register('ArticleHandler::viewFile', array($this, 'startUsageEvent'));
			HookRegistry::register('ArticleHandler::downloadFile', array($this, 'startUsageEvent'));
			HookRegistry::register('ArticleHandler::downloadSuppFile', array($this, 'startUsageEvent'));
			HookRegistry::register('IssueHandler::viewFile', array($this, 'startUsageEvent'));

			// Hook triggered after file download finishes.
			HookRegistry::register('FileManager::downloadFileFinished', array($this, 'endUsageEvent'));

			// Log a usage event.
			HookRegistry::register('OasPlugin::usageEvent', array($this, 'logUsageEvent'));
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.oas.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.oas.description');
	}

	/**
	 * @see PKPPlugin::getInstallSchemaFile()
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/schema.xml';
	}

	/**
	 * @see PKPPlugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}

	/**
	 * @see PKPPlugin::getInstallSitePluginSettingsFile()
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Register the OA-S report plugin.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		if ($category == 'reports') {
			$this->import('OasReportPlugin');
			$reportPlugin = new OasReportPlugin();
			$plugins[$reportPlugin->getSeq()][$reportPlugin->getPluginPath()] =& $reportPlugin;
		}
		return false;
	}

	/**
	 * Automatic plugin maintenance:
	 * 1) event table maintenance
	 * 2) salt management
	 *
	 * @return string the current or updated SALT value
	 */
	function doMaintenance() {
		$currentTs = time();

		// Event table maintenance.
		$lastEventTableMaintenanceTs = $this->getSetting(0, 'lastEventTableMaintenanceTs');
		if (($currentTs - OAS_PLUGIN_MAX_STAGING_TIME * 60) > $lastEventTableMaintenanceTs) {
			$this->_oasEventStagingDao->clearExpiredEvents();
		}

		// Salt management.
		$salt = $this->getSetting(0, 'salt');
		$saltTs = $this->getSetting(0, 'saltTs');
		if (empty($salt) || $saltTs == 0 || date('YYYYMM', $saltTs) != date('YYYYMM', $currentTs)) {
			$lastSaltDownloadTs = $this->getSetting(0, 'lastSaltDownloadTs');
			if (($currentTs - OAS_PLUGIN_SALT_DOWNLOAD_INTERVAL * 60) > $lastSaltDownloadTs) {
				import('lib.pkp.classes.webservice.WebService');
				import('lib.pkp.classes.webservice.WebServiceRequest');
				$wsReq = new WebServiceRequest(OAS_PLUGIN_SALT_URL);
				$wsReq->setAccept('text/plain');
				$ws = new WebService();
				$ws->setAuthUsername($this->getSetting(0, 'saltApiUsername'));
				$ws->setAuthPassword($this->getSetting(0, 'saltApiPassword'));
				$newSalt = $ws->call($wsReq);
				$this->updateSetting(0, 'lastSaltDownloadTs', $currentTs);
				if($ws->getLastResponseStatus() == '200' && !empty($newSalt) && $newSalt != $salt) {
					$this->updateSetting(0, 'saltTs', $currentTs);
					$this->updateSetting(0, 'salt', $newSalt);
					$salt = $newSalt;
				}
			}
		}

		return $salt;
	}

	/**
	 * Start preparing a usage event.
	 *
	 * @param $hookName string
	 * @param $args array
	 */
	function startUsageEvent($hookName, $args) {
		$request = $this->getRequest();

		// Check whether we are in journal context.
		$journal =& $request->getJournal();
		if (!$journal) return false;

		// Prepare request information.
		$downloadSuccess = false;
		switch ($hookName) {

			// Article abstract, HTML galley and remote galley.
			case 'TemplateManager::display':
				// We are only interested in access to the article abstract/galley view page.
				$page = $request->getRequestedPage();
				$op = $request->getRequestedOp();
				if ($page != 'article' || !($op == 'view' || $op == 'articleView')) return false;

				$templateManager = $args[0];
				$galley = $templateManager->get_template_vars('galley'); /* @var $galley ArticleGalley */
				$article = $templateManager->get_template_vars('article');
				if ($galley) {
					if ($galley->isHTMLGalley() || $galley->getRemoteURL()) {
						$pubObject = $galley;
						$assocType = ASSOC_TYPE_GALLEY;
						$canonicalUrlParams = array($article->getId(), $pubObject->getBestGalleyId($journal));
					} else {
						// This is an access to an intermediary galley page which we
						// do not count.
						return false;
					}
				} else {
					$pubObject = $article;
					$assocType = ASSOC_TYPE_ARTICLE;
					$canonicalUrlParams = array($pubObject->getBestArticleId($journal));
				}
				// The article and HTML/remote galley pages do not download anything.
				$downloadSuccess = true;
				$canonicalUrlOp = 'view';
				break;

			// Article galley (except for HTML and remote galley).
			case 'ArticleHandler::viewFile':
			case 'ArticleHandler::downloadFile':
				$pubObject = $args[1];
				$assocType = ASSOC_TYPE_GALLEY;
				$canonicalUrlOp = 'download';
				$article = $args[0];
				$canonicalUrlParams = array($article->getId(), $pubObject->getBestGalleyId($journal));
				break;

			// Supplementary file.
			case 'ArticleHandler::downloadSuppFile':
				$pubObject = $args[1];
				$assocType = ASSOC_TYPE_SUPP_FILE;
				$canonicalUrlOp = 'downloadSuppFile';
				$article = $args[0];
				$canonicalUrlParams = array($article->getId(), $pubObject->getBestSuppFileId($journal));
				break;

			// Issue galley.
			case 'IssueHandler::viewFile':
				$pubObject = $args[1];
				$assocType = ASSOC_TYPE_ISSUE_GALLEY;
				$canonicalUrlOp = 'download';
				$issue = $args[0];
				$canonicalUrlParams = array($issue->getId(), $pubObject->getBestGalleyId($journal));
				break;

			default:
				// Why are we called from an unknown hook?
				assert(false);
		}

		// Timestamp.
		$time = Core::getCurrentDate();

		// Actual document size, MIME type.
		$router = $request->getRouter();
		if ($assocType == ASSOC_TYPE_ARTICLE) {
			// Article abstract.
			$docSize = 0;
			$mimeType = 'text/html';
		} else {
			// Files.
			$docSize = $pubObject->getFileSize();
			$mimeType = $pubObject->getFileType();
		}

		// Canonical URL.
		if ($assocType == ASSOC_TYPE_ISSUE) {
			$canonicalUrlPage = 'issue';
		} else {
			$canonicalUrlPage = 'article';
		}
		$canonicalUrl = $router->url(
			$request, null, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams
		);

		// Public identifiers.
		$identifiers = array();
		if (!is_a($pubObject, 'IssueGalley')) {
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $journal->getId());
			if (is_array($pubIdPlugins)) {
				foreach ($pubIdPlugins as $pubIdPlugin) {
					if (!$pubIdPlugin->getEnabled()) continue;
					$pubId = $pubIdPlugin->getPubId($pubObject);
					if ($pubId) {
						$identifiers[$pubIdPlugin->getPubIdType()] = $pubId;
					}
				}
			}
		}

		// Service URI.
		$serviceUri = $router->url($request, $journal->getPath());

		// IP and Host.
		$ip = $request->getRemoteAddr();
		$host = null;
		if (isset($_SERVER['REMOTE_HOST'])) {
			// We do NOT actively look up the remote host to
			// avoid the performance penalty. We only set the remote
			// host if we get it "for free".
			$host = $_SERVER['REMOTE_HOST'];
		}

		// HTTP user agent.
		$userAgent = $request->getUserAgent();

		// HTTP referrer.
		$referrer = $_SERVER['HTTP_REFERER'];

		// User and roles.
		$user = $request->getUser();
		$roles = array();
		if ($user) {
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$rolesByContext = $roleDao->getByUserIdGroupedByContext($user->getId());
			foreach (array(CONTEXT_SITE, $journal->getId()) as $context) {
				if(isset($rolesByContext[$context])) {
					foreach ($rolesByContext[$context] as $role) {
						$roles[] = $role->getRoleId();
					}
				}
			}
		}

		// Try a simple classification of the request.
		$classification = null;
		if (!empty($roles)) {
			// Access by editors, authors, etc.
			$internalRoles = array_diff($roles, array(ROLE_ID_READER));
			if (!empty($internalRoles)) {
				$classification = OAS_PLUGIN_CLASSIFICATION_ADMIN;
			}
		}
		if ($request->isBot()) {
			// The bot classification overwrites other classifications.
			$classification = OAS_PLUGIN_CLASSIFICATION_BOT;
		}
		// TODO: Classify LOCKSS or similar as 'internal' access.

		/*
		 * Comparison of our event log format with Apache log parameters...
		 *
		 * 1) default parameters:
		 * %h: remote hostname or IP => $ip, $host
		 * %l: remote logname (identd) => not supported, see $user, $roles instead
		 * %u: remote user => not supported, see $user, $roles instead
		 * %t: request time => $time
		 * %r: query => derived objects: $pubObject, $assocType, $canonicalUrl, $identifiers, $serviceUri, $classification
		 * %s: status => not supported (always 200 in our case)
		 * %b: response size => $docSize
		 *
		 * 2) other common parameters
		 * %O: bytes sent => not supported (cannot be reliably determined from within PHP)
		 * %X: connection status => $downloadSuccess (not reliable!)
		 * %{ContentType}o: => $mimeType
		 * %{User-agent}i: => $userAgent
		 * %{Referer}i: => $referrer
		 *
		 * Several items, e.g. time etc., may differ from what Apache
		 * would actually log. But the differences do not matter for our use
		 * cases.
		 */

		// Collect all information into an array.
		$usageEvent = compact(
			'time', 'pubObject', 'assocType', 'canonicalUrl', 'mimeType',
			'identifiers', 'docSize', 'downloadSuccess', 'serviceUri',
			'ip', 'host', 'user', 'roles', 'userAgent', 'referrer',
			'classification'
		);

		// Prefetch the DAO so that it is available even after download finishes.
		// (OJS will clear the registry before downloading files.)
		$this->import('OasEventStagingDAO');
		$this->_oasEventStagingDao = new OasEventStagingDAO();
		// We don't really have to register the DAO but let's do it for the sake of consistency.
		DAORegistry::registerDAO('OasContextObjectDAO', $this->_oasEventStagingDao);

		// Check whether we have outstanding maintenance jobs.
		$salt = $this->doMaintenance();

		// Stage the usage event.
		$this->_currentEventId = $this->_oasEventStagingDao->stageUsageEvent($usageEvent, $salt);
	}

	/**
	 * Finalize a usage event.
	 *
	 * @param $hookName string
	 * @param $args array
	 */
	function endUsageEvent($hookName, $args) {
		// Check whether we got the event DAO (should be the case
		// if our event flow is correct).
		assert($this->_oasEventStagingDao);
		if (!$this->_oasEventStagingDao) return;

		// Check whether the download finished on the
		// end user's side. (This won't work 100%
		// reliably if the response is buffered by the
		// web server but at least it shouldn't produce
		// false negatives.)
		if (connection_aborted()) {
			$downloadSuccess = false;
		} else {
			$downloadSuccess = $args[0];
		}

		// Update the usage event.
		$this->_oasEventStagingDao->setDownloadSuccess($this->_currentEventId, $downloadSuccess);
	}
}

?>
