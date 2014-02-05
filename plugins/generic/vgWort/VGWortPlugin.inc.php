<?php

/**
 * @file plugins/generic/vgWort/VGWortPlugin.inc.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: February 04, 2014
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VGWortPlugin
 * @ingroup plugins_generic_vgWort
 *
 * @brief VG Wort plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');


class VGWortPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			$this->addLocaleData();
			$this->addHelpData();
			$this->import('classes.PixelTag');
			$this->import('classes.PixelTagDAO');
			$pixelTagDao = new PixelTagDAO($this->getName());
			$returner =& DAORegistry::registerDAO('PixelTagDAO', $pixelTagDao);

			// pixel tags operations can be done just by editors specified in the plug-in settings
			if ($this->authorize()) {
				// Editor link to VG Wort pages
				HookRegistry::register('Templates::Editor::Index::AdditionalItems', array($this, 'displayEditorHomeLinks'));
			}
			// Handler for editor VG Wort pages
			HookRegistry::register('LoadHandler', array($this, 'setupEditorHandler'));

			// Insert new field into the author metadata submission form (submission step 3) and metadata form
			HookRegistry::register('Templates::Author::Submit::Authors', array($this, 'metadataFieldEdit'));
			HookRegistry::register('Templates::Submission::MetadataEdit::Authors', array($this, 'metadataFieldEdit'));

			// Hook for initData in two forms -- init the new field
			HookRegistry::register('metadataform::initdata', array($this, 'metadataInitData'));
			HookRegistry::register('authorsubmitstep3form::initdata', array($this, 'metadataInitData'));

			// Hook for save in two forms -- execute
			HookRegistry::register('Author::Form::Submit::AuthorSubmitStep3Form::Execute', array($this, 'metadataExecute'));
			HookRegistry::register('Submission::Form::MetadataForm::Execute', array($this, 'metadataExecute'));

			// Hook for save in two forms -- add validation for the new field
			HookRegistry::register('authorsubmitstep3form::Constructor', array($this, 'addCheck'));
			HookRegistry::register('metadataform::Constructor', array($this, 'addCheck'));

			// Add element for AuthorDAO for storage
			HookRegistry::register('authordao::getAdditionalFieldNames', array($this, 'authorSubmitGetFieldNames'));

			// Hook for article galley view -- add the pixel tag
			HookRegistry::register ('TemplateManager::display', array($this, 'handleTemplateDisplay'));

		}
		return $success;
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.vgWort.displayName');
	}

	function getDescription() {
		if ($this->requirementsFulfilled()) return Locale::translate('plugins.generic.vgWort.description');
		return Locale::translate('plugins.generic.vgWort.descriptionDisabled');
	}

	/**
	 * Check whether or not the requirements for this plug-in are fullfilled
	 * @return boolean
	 */
	function requirementsFulfilled() {
		$isPHPVersion = checkPhpVersion('5.0.1');
		$isSoapExtension = in_array('soap', get_loaded_extensions());
		$isOpenSSL = in_array('openssl', get_loaded_extensions());

		return $isPHPVersion && $isSoapExtension && $isOpenSSL;
	}

	/**
	 * Get the path and filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/xml/schema.xml';
	}

	/**
	 * Get the path and filename of the email keys for this plugin.
	 */
	function getInstallEmailTemplatesFile() {
		return $this->getPluginPath() . '/xml/emailTemplates.xml';
	}

	/**
     * Get the path and filename of the help mapping file.
     */
    function getHelpMappingFilename() {
    	return $this->getPluginPath() . '/xml/help.xml';
    }

	/**
	 * Get the path and filename of the email locale data for this plugin.
	 */
	function getInstallEmailTemplateDataFile() {
		return $this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml';
	}

	/**
	 * Get the template path for this plugin.
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	 * Get the handler path for this plugin.
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}

	/**
	 * Get the path and stylesheet for this plugin.
	 *
	 */
	function getStyleSheet() {
		return $this->getPluginPath() . '/styles/vgWort.css';
	}

	/**
	 * Display VG Wort management link in editor home.
	 */
	function displayEditorHomeLinks($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$templateMgr = TemplateManager::getManager();
		$output .= '<h3>' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.vgWort.editor.vgWort'), $smarty) . '</h3><ul class="plain"><li>&#187; <a href="' . $templateMgr->smartyUrl(array('op'=>'pixelTags'), $smarty) . '">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.vgWort.editor.pixelTags'), $smarty) . '</a></li></ul>';
		return false;
	}

	/**
	 * Enable editor pixel tags management.
	 */
	function setupEditorHandler($hookName, $params) {
		$page =& $params[0];

		if ($page == 'editor') {
			$op =& $params[1];

			if ($op) {
				$editorPages = array(
					'pixelTags',
					'deletePixelTag',
					'assignPixelTag',
					'removePixelTag',
					'reinsertPixelTag',
					'changeTextType',
					'pixelStatistics',
					'createPixelTag',
					'savePixelTag'
				);

				if (in_array($op, $editorPages)) {
					define('HANDLER_CLASS', 'VGWortEditorHandler');
					define('VGWORT_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_OJS_EDITOR));
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'VGWortEditorHandler.inc.php';
				}
			}
		}
	}

	/*
	 * Metadata
	 */

	/**
	 * Insert VG Wort field cardNo into author submission step 3 and metadata edit form
	 */
	function metadataFieldEdit($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$output .= $smarty->fetch($this->getTemplatePath() . 'cardNoEdit.tpl');
		return false;
	}

	/**
	 * Add VG Wort field cardNo to the metadata view
	 */
	function metadataFieldView($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$output .= $smarty->fetch($this->getTemplatePath() . 'cardNoView.tpl');
		return false;
	}

	/**
	 * Init VG Wort field cardNo
	 */
	function metadataInitData($hookName, $params) {
		$form =& $params[0];
		$article =& $form->article;

		$formAuthors = $form->getData('authors');
		$articleAuthors =& $article->getAuthors();
		for ($i=0; $i<count($articleAuthors); $i++) {
			$formAuthors[$i]['cardNo'] = $articleAuthors[$i]->getData('cardNo');
		}
		$form->setData('authors', $formAuthors);
		return false;
	}

	/**
	 * Set authors' cardNo
	 */
	function metadataExecute($hookName, $params) {
		$author =& $params[0];
		$formAuthor =& $params[1];

		$author->setData('cardNo', $formAuthor['cardNo']);
		return false;
	}

	/**
	 * Add the validation check for the cardNo field (2-7 numbers and if one exists then all should exist)
	 */
	function addCheck($hookName, $params) {
		$form =& $params[0];
		if (get_class($form) == 'AuthorSubmitStep3Form' || get_class($form) == 'MetadataForm' ) {
			$form->addCheck(new FormValidatorArrayCustom($form, 'authors', 'required', 'plugins.generic.vgWort.cardNoValid', create_function('$cardNo, $regExp', 'return empty($cardNo) ? true : String::regexp_match($regExp, $cardNo);'), array('/^\d{2,7}$/'), false, array('cardNo')));
		}
		return false;
	}

	/**
	 * Add cardNo element to the article author
	 */
	function authorSubmitGetFieldNames($hookName, $params) {
		$fields =& $params[1];
		$fields[] = 'cardNo';
		return false;
	}

	/**
	 * Handle article and submission summary view template display.
	 */
	function handleTemplateDisplay($hookName, $params) {
		$smarty =& $params[0];
		$template =& $params[1];

		switch ($template) {
			case 'article/article.tpl':
			case 'rt/printerFriendly.tpl':
				$smarty->register_outputfilter(array($this, 'insertPixelTag'));
				break;
			case 'sectionEditor/submission.tpl':
				if ($this->authorize()) {
					HookRegistry::register ('TemplateManager::include', array($this, 'assignPixelTag'));
				}
				break;
		}
		return false;
	}

	/**
	 * Enable editors to assign a VG Wort pixel tag to an article in the submission summary view.
	 */
	function assignPixelTag($hookName, $args) {
		$smarty =& $args[0];
		$params =& $args[1];
		if (!isset($params['smarty_include_tpl_file'])) return false;
		switch ($params['smarty_include_tpl_file']) {
			case 'submission/metadata/metadata.tpl':
				$journal =& $smarty->get_template_vars('currentJournal');
				$submission =& $smarty->get_template_vars('submission');
				$pixelTagDao =& DAORegistry::getDAO('PixelTagDAO');
				$pixelTag =& $pixelTagDao->getPixelTagByArticleId($journal->getId(), $submission->getId());

				$vgWortTextType = !isset($pixelTag) ? 0 : $pixelTag->getTextType();
				$smarty->assign('pixelTag', $pixelTag);
				$smarty->assign('vgWortTextType', $vgWortTextType);
				$smarty->assign('typeOptions', PixelTag::getTextTypeOptions());
				$smarty->fetch($this->getTemplatePath() . 'assignPixelTag.tpl', null, null, true);
				break;
		}
		return false;
	}

	/**
	 * Insert the VG Wort pixel tag in the article page.
	 */
	function insertPixelTag($output, &$smarty) {
		$smarty->unregister_outputfilter('insertPixelTag');

		$journal =& $smarty->get_template_vars('currentJournal');
		$articleId =& $smarty->get_template_vars('articleId');
		$galley =& $smarty->get_template_vars('galley');

		if (isset($galley)) {
			if($galley->isHtmlGalley() || $galley->isPdfGalley()) {
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticle = & $publishedArticleDao->getPublishedArticleByBestArticleId($journal->getId(), $articleId);
				// the article and the issue have to be published
				if (isset($publishedArticle)) {
					$issueDao =& DAORegistry::getDAO('IssueDAO');
					$issue = & $issueDao->getIssueById($publishedArticle->getIssueId(), $journal->getId());
					if ($issue->getPublished()) {
						// get the assigned pixel tag
						$pixelTagDao =& DAORegistry::getDAO('PixelTagDAO');
						$pixelTag =& $pixelTagDao->getPixelTagByArticleId($journal->getId(), $publishedArticle->getId());
						if (isset($pixelTag) && !$pixelTag->getDateRemoved()) {
							// insert the pixel tag in the HTML version, just after the element <div id="content">
							$src = 'http://' . $pixelTag->getDomain() . '/na/' . $pixelTag->getPublicCode();
							$pixelTagImg = '<img src="' .$src .'" width="1" height="1" alt="" />';
							$output = str_replace ('<div id="content">', '<div id="content">'.$pixelTagImg, $output);
							// consider the pixel tag in the PDF download links, i.e. change the PDF download links
							if ($galley->isPdfGalley()) {
								$pdfUrl = Request::url(null, 'article', 'download', array($publishedArticle->getBestArticleId($journal), $galley->getBestGalleyId($journal)));
								$newPdfLink = 'http://' . $pixelTag->getDomain() . '/na/' . $pixelTag->getPublicCode() . '?l=' . $pdfUrl;
								$output = str_replace ('href="'.$pdfUrl, 'href="'.$newPdfLink, $output);
							}
						}
					}
				}
			}
		}
		return $output;
	}

	/**
	 * Set the breadcrumbs, given the plugin's tree of items to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->requirementsFulfilled()) {
			if ($this->getEnabled()) {
				$verbs[] = array('settings', Locale::translate('plugins.generic.vgWort.manager.settings'));
			}
			return parent::getManagementVerbs($verbs);
		}
		return $verbs;
	}

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
				$journal =& Request::getJournal();

				$this->import('classes.form.VGWortSettingsForm');
				$form = new VGWortSettingsForm($this, $journal->getId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$this->setBreadCrumbs(true);
						$form->display();
					}
				} else {
					$this->setBreadCrumbs(true);
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}

	/**
	 * Ensure that the user is an editor specified in the plugin settings.
 	 * @return boolean
	 */
	private function authorize() {
		$journal =& Request::getJournal();
		$editors = $this->getSetting($journal->getId(), 'vgWortEditors');
		if (empty($editors)) return false;
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
		if (!in_array($session->getUserId(), $editors)) return false;
		return true;
	}

}
?>
