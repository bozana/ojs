<?php

/**
 * @file DefaultENTranslationPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.defaultENTranslation
 * @class DefaultENTranslationPlugin
 *
 * Display English translation if the current UI language translation doesn't exist
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class DefaultENTranslationPlugin extends GenericPlugin {

	/**
	 * @copydoc PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.defaultENTranslation.displayName');
	}

	/**
	 * @copydoc PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.defaultENTranslation.description');
	}

	/**
	 * @copydoc LazyLoadPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			HookRegistry::register('PKPLocale::translate', array($this, 'translate'));
			HookRegistry::register('PKPLocale::registerLocaleFile::isValidLocaleFile', array(&$this, 'isValidLocaleFile'));
		}
		return $success;
	}

	/**
	 * @copydoc PKPPlugin::getSeq()
	 */
	function getSeq() {
		return -1;
	}

	/**
	 * Hook callback: Handle requests.
	 * Show Englisch translation if the current UI language translation doesn't exist.
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function translate($hookName, $args) {
		$key = $args[1];
		$params = $args[2];
		$locale = $args[3];
		$localeFiles = $args[4];
		$value = &$args[5];

		foreach ($localeFiles as $localeFile) {
			$fileName = $localeFile->getFilename();
			$newFileName = str_replace($locale, 'en_US', $fileName);
			$newFile = new LocaleFile('en_US', $newFileName);
			$value = $newFile->translate($key, $params);
			if ($value !== null) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Hook callback: Handle requests.
	 * Consider/register also the not existing locale files.
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function isValidLocaleFile($hookName, $args) {
		return true;
	}

}
?>
