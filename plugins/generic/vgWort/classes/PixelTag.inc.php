<?php

/**
 * @file plugins/generic/vgWort/classes/PixelTag.inc.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PixelTag
 * @ingroup plugins_generic_vgWort
 * @see PixelTagDAO
 *
 * @brief Pixel tag metadata class.
 */

define('PT_STATUS_AVAILABLE', 0x01);
define('PT_STATUS_UNREGISTERED', 0x02);
define('PT_STATUS_REGISTERED', 0x03);

define('TYPE_DEFAULT', 0x00);
define('TYPE_TEXT', 0x01);
define('TYPE_LYRIC', 0x02);

class PixelTag extends DataObject {

	/**
	 * Constructor.
	 */
	function PixelTag() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the journal ID of the pixel tag.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal ID of the pixel tag.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the article ID of the article the pixel tag is assigned to.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}

	/**
	 * Set the article ID of the article the pixel tag is assigned to.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}

	/**
	 * Get the article the pixel tag is assigned to.
	 * @return Article object
	 */
	function &getArticle() {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article = & $articleDao->getById($this->getData('articleId'));
		return $article;
	}

	/**
	 * Get private code of the pixel tag -- 20, 30 or 32 long hexadecimal number treated here as string.
	 * @return string
	 */
	function getPrivateCode() {
		return $this->getData('privateCode');
	}

	/**
	 * Set private code of the pixel tag -- 20, 30 or 32 long hexadecimal number treated as string here.
	 * @param $privateCode string
	 */
	function setPrivateCode($privateCode) {
		return $this->setData('privateCode', $privateCode);
	}

	/**
	 * Get public code of the pixel tag -- 20, 30 or 32 long hexadecimal number treated as string here.
	 * @return string
	 */
	function getPublicCode() {
		return $this->getData('publicCode');
	}

	/**
	 * Set public code of the pixel tag -- 20, 30 or 32 long hexadecimal number treated as string here.
	 * @param $publicCode string
	 */
	function setPublicCode($publicCode) {
		return $this->setData('publicCode', $publicCode);
	}

	/**
	 * Get VG Wort domain for the pixel tag.
	 * @return string
	 */
	function getDomain() {
		return $this->getData('domain');
	}

	/**
	 * Set VG Wort domain for the pixel tag.
	 * @param $domain string
	 */
	function setDomain($domain)
	{
		return $this->setData('domain', $domain);
	}

	/**
	 * Get date ordered.
	 * @return date
	 */

	function getDateOrdered() {
		return $this->getData('dateOrdered');
	}

	/**
	 * Set date ordered.
	 * @param $dateOrdered date
	 */

	function setDateOrdered($dateOrdered) {
		return $this->setData('dateOrdered', $dateOrdered);
	}

	/**
	 * Get date assigned.
	 * @return date
	 */

	function getDateAssigned() {
		return $this->getData('dateAssigned');
	}

	/**
	 * Set date assigned.
	 * @param $dateAssigned date
	 */

	function setDateAssigned($dateAssigned) {
		return $this->setData('dateAssigned', $dateAssigned);
	}

	/**
	 * Get date registered.
	 * @return date
	 */

	function getDateRegistered() {
		return $this->getData('dateRegistered');
	}

	/**
	 * Set date registered.
	 * @param $dateRegistered date
	 */

	function setDateRegistered($dateRegistered) {
		return $this->setData('dateRegistered', $dateRegistered);
	}

	/**
	 * Get date removed.
	 * @return date
	 */

	function getDateRemoved() {
		return $this->getData('dateRemoved');
	}

	/**
	 * Set date removed.
	 * @param $dateRemoved date
	 */

	function setDateRemoved($dateRemoved) {
		return $this->setData('dateRemoved', $dateRemoved);
	}

	/**
	 * Get status.
	 * @return int
	 */

	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set status.
	 * @param $status int
	 */

	function setStatus($status) {
		return $this->setData('status', $status);
	}

	/**
	 * Get pixel tag status locale string.
	 * @return string
	 */
	function getStatusString() {
		switch ($this->getData('status')) {
			case PT_STATUS_AVAILABLE:
				return __('plugins.generic.vgWort.pixelTag.available');
			case PT_STATUS_UNREGISTERED:
				return __('plugins.generic.vgWort.pixelTag.unregistered');
			case PT_STATUS_REGISTERED:
				return __('plugins.generic.vgWort.pixelTag.registered');
			default:
				return __('plugins.generic.vgWort.pixelTag.status');
		}
	}

	/**
	 * Get text type.
	 * @return int
	 */

	function getTextType() {
		return $this->getData('textType');
	}

	/**
	 * Set text type.
	 * @param $textType int
	 */

	function setTextType($textType) {
		return $this->setData('textType', $textType);
	}

	/**
	 * Get an associative array matching text type codes with locale strings.
	 * @return array text type => localeString
	 */
	function &getTextTypeOptions() {
		static $textTypeOptions = array(
			TYPE_DEFAULT => 'plugins.generic.vgWort.default',
			TYPE_TEXT => 'plugins.generic.vgWort.text',
			TYPE_LYRIC => 'plugins.generic.vgWort.lyric'
		);
		return $textTypeOptions;
	}

	/**
	 * Get pixel tag text type locale string.
	 * @return string
	 */
	function getTextTypeString() {
		switch ($this->getData('textType')) {
			case TYPE_DEFAULT:
				return __('plugins.generic.vgWort.default');
			case TYPE_TEXT:
				return __('plugins.generic.vgWort.text');
			case TYPE_LYRIC:
				return __('plugins.generic.vgWort.lyric');
			default:
				return __('article.comments.sectionDefault');
		}
	}

}

?>
