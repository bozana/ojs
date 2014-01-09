<?php
/**
 * @defgroup controllers_grid_vgwort PixelTagsGrid
 * The PixelTagGrid implements the management interface allowing editors to
 * manage pixel tags.
 */

/**
 * @file controllers/grid/PixelTagGridHandler.inc.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: November 01, 2013
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PixelTagGridHandler
 * @ingroup controllers_grid_vgwort
 *
 * @brief Handle pixel tag grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.vgWort.controllers.grid.PixelTagGridRow');

class PixelTagGridHandler extends GridHandler {

	/** @var int pixel tags status */
	var $pixelTagStatus;

	/**
	 * Constructor
	 */
	function PixelTagGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_EDITOR, ROLE_ID_MANAGER),
			array(
				'fetchGrid',
				'deletePixelTag', 'registerPixelTag'
			)
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request, $args) {
		parent::initialize($request, $args);

		if ($request->getUserVar('pixelTagStatus')) {
			$this->pixelTagStatus = $request->getUserVar('pixelTagStatus');
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);

		// Grid columns.
		import('plugins.generic.vgWort.controllers.grid.PixelTagGridCellProvider');
		$pixelTagGridCellProvider = new PixelTagGridCellProvider();

		$this->addColumn(
			new GridColumn(
				'privateCode',
				'plugins.generic.vgWort.pixelTag.privateCode',
				null,
				'controllers/grid/gridCell.tpl',
				$pixelTagGridCellProvider
			)
		);
		if ($this->pixelTagStatus == '' || $this->pixelTagStatus == PT_STATUS_AVAILABLE) {
			$this->addColumn(
				new GridColumn(
					'publicCode',
					'plugins.generic.vgWort.pixelTag.publicCode',
					null,
					'controllers/grid/gridCell.tpl',
					$pixelTagGridCellProvider
				)
			);
			$this->addColumn(
				new GridColumn(
					'ordered',
					'plugins.generic.vgWort.pixelTag.date_ordered',
					null,
					'controllers/grid/gridCell.tpl',
					$pixelTagGridCellProvider
				)
			);
		}
		if ($this->pixelTagStatus == '') {
			$this->addColumn(
				new GridColumn(
					'status',
					'common.status',
					null,
					'controllers/grid/gridCell.tpl',
					$pixelTagGridCellProvider
				)
			);
		}
		if ($this->pixelTagStatus == PT_STATUS_AVAILABLE) {
			$this->addColumn(
				new GridColumn(
					'domain',
					'plugins.generic.vgWort.pixelTag.domain',
					null,
					'controllers/grid/gridCell.tpl',
					$pixelTagGridCellProvider
				)
			);
		}
		if ($this->pixelTagStatus == PT_STATUS_UNREGISTERED || $this->pixelTagStatus == PT_STATUS_REGISTERED) {
			$this->addColumn(
				new GridColumn(
					'authors',
					'article.authors',
					null,
					'controllers/grid/gridCell.tpl',
					$pixelTagGridCellProvider
				)
			);
			$this->addColumn(
				new GridColumn(
					'title',
					'article.title',
					null,
					'controllers/grid/gridCell.tpl',
					$pixelTagGridCellProvider
				)
			);
		}
		if ($this->pixelTagStatus == PT_STATUS_UNREGISTERED) {
			$this->addColumn(
				new GridColumn(
					'assigned',
					'plugins.generic.vgWort.pixelTag.date_assigned',
					null,
					'controllers/grid/gridCell.tpl',
					$pixelTagGridCellProvider
				)
			);
		}
		if ($this->pixelTagStatus == PT_STATUS_REGISTERED) {
			$this->addColumn(
				new GridColumn(
					'registered',
					'plugins.generic.vgWort.pixelTag.date_registered',
					null,
					'controllers/grid/gridCell.tpl',
					$pixelTagGridCellProvider
				)
			);
		}

	}

	/**
	 * Get the row handler - override the default row handler
	 * @return PixelTagGridRow
	 */
	function getRowInstance() {
		return new PixelTagGridRow($this->pixelTagStatus);
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request) {
		$context = $request->getContext();
		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');

		$fieldOptions = Array(
			PT_FIELD_PRIVCODE => __('plugins.generic.vgWort.pixelTag.privateCode'),
			PT_FIELD_PUBCODE => __('plugins.generic.vgWort.pixelTag.publicCode')
		);

		$filterData = array(
			'fieldOptions' => $fieldOptions,
			'pixelTagStatus' => $this->pixelTagStatus
		);

		return parent::renderFilter($request, $filterData);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 * @return array Filter selection data.
	 */
	function getFilterSelectionData($request) {
		$searchField = null;
		$search = $request->getUserVar('search');
		if (!empty($search)) {
			$searchField = $request->getUserVar('searchField');
		}

		return $filterSelectionData = array(
			'searchField' => $searchField,
			'search' => $search ? $search : ''
		);
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 * @return string Filter template.
	 */
	function getFilterForm() {
		$vgWortPlugin = PluginRegistry::getPlugin('generic', 'vgwortplugin');
		//$template = $vgWortPlugin->getTemplatePath() . 'controllers/grid/pixelTagGridFilter' . $this->pixelTagStatus .'.tpl';
		$template = $vgWortPlugin->getTemplatePath() . 'controllers/grid/pixelTagGridFilter.tpl';
		return $template;
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		$journal = $request->getJournal();
		$sortBy = 'pixel_tag_id';
		$sortDirection = SORT_DIRECTION_DESC;
		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		$pixelTags = $pixelTagDao->getPixelTagsByJournalId(
			$journal->getId(),
			$filter['searchField']?$filter['searchField']:null,
			$filter['search']?$filter['search']:null,
			$this->pixelTagStatus,
			$rangeInfo,
			$sortBy,
			$sortDirection
		);
		return $pixelTags;
	}

	//
	// Public operations
	//
	function deletePixelTag($args = array(), $request) {
		//$pixelTagId = (int) array_shift($args);
		$pixelTagId = $request->getUserVar('rowId');
		if (!$pixelTagId) $pixelTagId = $request->getUserVar('pixelTagId');

		$router = $request->getRouter();
		$journal = $router->getContext($request);
		$journalId = $journal->getId();

		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$pixelTag = $pixelTagDao->getPixelTag($pixelTagId, $journalId);
		if (isset($pixelTag) && $pixelTag->getStatus() == PT_STATUS_AVAILABLE) {
			$pixelTagDao->deleteObject($pixelTag);
		}
		return DAO::getDataChangedEvent($pixelTagId);
	}

	function registerPixelTag($args = array(), $request) {
		//$pixelTagId = (int) $request->getUserVar('pixelTagId');
		$pixelTagId = $request->getUserVar('rowId');
		if (!$pixelTagId) $pixelTagId = $request->getUserVar('pixelTagId');

		$router = $request->getRouter();
		$journal = $router->getContext($request);
		$journalId = $journal->getId();

		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$pixelTag = $pixelTagDao->getPixelTag($pixelTagId, $journalId);
		// the pixel tag exists, it is unregistered and not removed
		if (isset($pixelTag) && $pixelTag->getStatus() == PT_STATUS_UNREGISTERED && !$pixelTag->getDateRemoved()) {
			// check if the requirements for the registration are fulfilled
			import('plugins.generic.vgWort.classes.VGWortEditorAction');
			$vgWortEditorAction = new VGWortEditorAction();
			/*
$file = 'debug.txt';
$current = file_get_contents($file);
$current .= print_r($pixelTag, true);
file_put_contents($file, $current);
*/
			$checkResult = $vgWortEditorAction->check($pixelTag);
			$isError = !$checkResult[0];
			if ($isError) {
				$errors[] = $checkResult[1];
			} else {
				// register
				$registerResult = $vgWortEditorAction->newMessage($pixelTagId, $request);
				$isError = !$registerResult[0];
				$errors[] = $registerResult[1];
				if (!$isError) {
					// update the registered pixel tag
					$pixelTag->setDateRegistered(Core::getCurrentDate());
					$pixelTag->setStatus(PT_STATUS_REGISTERED);
					$pixelTagDao->updateObject($pixelTag);
					// send a notification email to the authors
					$vgWortEditorAction->notifyAuthors($journal, $pixelTag);
				}
			}
		}
		/*
		$dispatcher = $request->getDispatcher();
		// FIXME: Find a better way to reload the containing tabs.
		// Without this, issues don't move between tabs properly.
		return $request->redirectUrlJson($dispatcher->url($request, ROUTE_PAGE, null, 'manageIssues'));
		*/
		return DAO::getDataChangedEvent($pixelTagId);
	}

















	/**
	 * An action to add a new issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addIssue($args, $request) {
		// Calling editIssueData with an empty ID will add
		// a new issue.
		return $this->editIssueData($args, $request);
	}

	/**
	 * An action to edit a issue
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$templateMgr = TemplateManager::getManager($request);
		if ($issue) $templateMgr->assign('issueId', $issue->getId());
		$json = new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issue.tpl'));
		return $json->getString();
	}

	/**
	 * An action to edit a issue's identifying data
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editIssueData($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.IssueForm');
		$issueForm = new IssueForm($issue);
		$issueForm->initData($request);
		$json = new JSONMessage(true, $issueForm->fetch($request));
		return $json->getString();
	}

	/**
	 * An action to upload an issue file. Used for both covers and stylesheets.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function uploadFile($args, $request) {
		$user = $request->getUser();

		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
		if ($temporaryFile) {
			$json = new JSONMessage(true);
			$json->setAdditionalAttributes(array(
				'temporaryFileId' => $temporaryFile->getId()
			));
		} else {
			$json = new JSONMessage(false, __('common.uploadFailed'));
		}

		return $json->getString();
	}

	/**
	 * Update a issue
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.IssueForm');
		$issueForm = new IssueForm($issue);
		$issueForm->readInputData();

		if ($issueForm->validate($request)) {
			$issueId = $issueForm->execute($request);
			return DAO::getDataChangedEvent($issueId);
		} else {
			$json = new JSONMessage(true, $issueForm->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * An action to edit a issue's cover
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editCover($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.CoverForm');
		$coverForm = new CoverForm($issue);
		$coverForm->initData($request);
		$json = new JSONMessage(true, $coverForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update an issue cover
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateCover($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.CoverForm');
		$coverForm = new CoverForm($issue);
		$coverForm->readInputData();

		if ($coverForm->validate($request)) {
			$coverForm->execute($request);
			return DAO::getDataChangedEvent($issue->getId());
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}

	/**
	 * Removes an issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		$isBackIssue = $issue->getPublished() > 0 ? true: false;

		// remove all published articles and return original articles to editing queue
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		if (isset($publishedArticles) && !empty($publishedArticles)) {
			// Insert article tombstone if the issue is published
			import('classes.article.ArticleTombstoneManager');
			$articleTombstoneManager = new ArticleTombstoneManager();
			foreach ($publishedArticles as $article) {
				if ($isBackIssue) {
					$articleTombstoneManager->insertArticleTombstone($article, $journal);
				}
				$articleDao->changeStatus($article->getId(), STATUS_QUEUED);
				$publishedArticleDao->deletePublishedArticleById($article->getPublishedArticleId());
			}
		}

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteObject($issue);
		if ($issue->getCurrent()) {
			$issues = $issueDao->getPublishedIssues($journal->getId());
			if (!$issues->eof()) {
				$issue = $issues->next();
				$issue->setCurrent(1);
				$issueDao->updateObject($issue);
			}
		}

		return DAO::getDataChangedEvent($issueId);
	}

	/**
	 * Display the table of contents
	 * @param $request PKPRequest
	 */
	function issueToc($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$templateMgr->assign('issue', $issue);
		$json = new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issueToc.tpl'));
		return $json->getString();
	}

	/**
	 * Displays the issue galleys page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function issueGalleys($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		$templateMgr = TemplateManager::getManager($request);
		import('classes.issue.IssueAction');
		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign('issue', $issue);

		$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$templateMgr->assign('issueGalleys', $issueGalleyDao->getByIssueId($issue->getId()));

		$json = new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issueGalleys.tpl'));
		return $json->getString();
	}

	/**
	 * Publish issue
	 * @param $args array
	 * @param $request Request
	 */
	function publishIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		$journal = $request->getJournal();
		$journalId = $journal->getId();

		$articleSearchIndex = null;
		if (!$issue->getPublished()) {
			// Set the status of any attendant queued articles to STATUS_PUBLISHED.
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
			foreach ($publishedArticles as $publishedArticle) {
				$article = $articleDao->getById($publishedArticle->getId());
				if ($article && $article->getStatus() == STATUS_QUEUED) {
					$article->setStatus(STATUS_PUBLISHED);
					$article->stampStatusModified();
					$articleDao->updateObject($article);
					if (!$articleSearchIndex) {
						import('classes.search.ArticleSearchIndex');
						$articleSearchIndex = new ArticleSearchIndex();
					}
					$articleSearchIndex->articleMetadataChanged($publishedArticle);
				}
				// delete article tombstone
				$tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO');
				$tombstoneDao->deleteByDataObjectId($article->getId());
			}
		}

		$issue->setCurrent(1);
		$issue->setPublished(1);
		$issue->setDatePublished(Core::getCurrentDate());

		// If subscriptions with delayed open access are enabled then
		// update open access date according to open access delay policy
		if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $journal->getSetting('enableDelayedOpenAccess')) {

			$delayDuration = $journal->getSetting('delayedOpenAccessDuration');
			$delayYears = (int)floor($delayDuration/12);
			$delayMonths = (int)fmod($delayDuration,12);

			$curYear = date('Y');
			$curMonth = date('n');
			$curDay = date('j');

			$delayOpenAccessYear = $curYear + $delayYears + (int)floor(($curMonth+$delayMonths)/12);
 			$delayOpenAccessMonth = (int)fmod($curMonth+$delayMonths,12);

			$issue->setAccessStatus(ISSUE_ACCESS_SUBSCRIPTION);
			$issue->setOpenAccessDate(date('Y-m-d H:i:s',mktime(0,0,0,$delayOpenAccessMonth,$curDay,$delayOpenAccessYear)));
		}

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateCurrent($journalId,$issue);

		if ($articleSearchIndex) $articleSearchIndex->articleChangesFinished();

		// Send a notification to associated users
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$notificationUsers = array();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$allUsers = $userGroupDao->getUsersByContextId($journalId);
		while ($user = $allUsers->next()) {
			$notificationUsers[] = array('id' => $user->getId());
		}
		foreach ($notificationUsers as $userRole) {
			$notificationManager->createNotification(
				$request, $userRole['id'], NOTIFICATION_TYPE_PUBLISHED_ISSUE,
				$journalId
			);
		}
		$notificationManager->sendToMailingList($request,
			$notificationManager->createNotification(
				$request, UNSUBSCRIBED_USER_NOTIFICATION, NOTIFICATION_TYPE_PUBLISHED_ISSUE,
				$journalId
			)
		);

		$dispatcher = $request->getDispatcher();
		// FIXME: Find a better way to reload the containing tabs.
		// Without this, issues don't move between tabs properly.
		return $request->redirectUrlJson($dispatcher->url($request, ROUTE_PAGE, null, 'manageIssues'));
	}

	/**
	 * Unpublish a previously-published issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function unpublishIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$journal = $request->getJournal();

		$issue->setCurrent(0);
		$issue->setPublished(0);
		$issue->setDatePublished(null);

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateObject($issue);

		// insert article tombstones for all articles
		import('classes.article.ArticleTombstoneManager');
		$articleTombstoneManager = new ArticleTombstoneManager();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issue->getId());
		foreach ($publishedArticles as $article) {
			$articleTombstoneManager->insertArticleTombstone($article, $journal);
		}

		$dispatcher = $request->getDispatcher();
		// FIXME: Find a better way to reload the containing tabs.
		// Without this, issues don't move between tabs properly.
		return $request->redirectUrlJson($dispatcher->url($request, ROUTE_PAGE, null, 'manageIssues'));
	}
}

?>
