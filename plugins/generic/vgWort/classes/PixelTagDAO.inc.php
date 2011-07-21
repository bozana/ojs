<?php

/**
 * @file plugins/generic/vgWort/classes/PixelTagDAO.inc.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: July 13, 2011  
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PixelTagDAO
 * @ingroup plugins_generic_vgWort
 * @see PixelTag
 *
 * @brief Operations for retrieving and modifying PixelTag objects.
 */

import('lib.pkp.classes.db.DAO');

/* These constants are used for user-selectable search fields. */
define('PT_FIELD_PRIVCODE', 	'private_code');
define('PT_FIELD_PUBCODE', 		'public_code');
define('PT_FIELD_NONE', 		null);

class PixelTagDAO extends DAO {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;
	
	/**
	 * Constructor.
	 */
	function PixelTagDAO($parentPluginName) {
		parent::DAO();
		$this->parentPluginName = $parentPluginName;
	}
	
	/**
	 * Retrieve a pixel tag by pixel tag ID.
	 * @param $pixelTagId int
	 * @param $journalId int optional
	 * @return PixelTag
	 */
	function &getPixelTag($pixelTagId, $journalId = null) {
		$params = array($pixelTagId);
		if ($journalId) $params[] = $journalId;
		
		$result =& $this->retrieve(
			'SELECT * FROM pixel_tags WHERE pixel_tag_id = ?' . ($journalId ? ' AND journal_id = ?' : ''),
			$params
		);
		
		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnPixelTagFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Internal function to return a PixelTag object from a row.
	 * @param $row array
	 * @return PixelTag
	 */
	function &_returnPixelTagFromRow(&$row) {
		$vgWortPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$vgWortPlugin->import('classes.PixelTag');

		$pixelTag = new PixelTag();
		$pixelTag->setId($row['pixel_tag_id']);
		$pixelTag->setJournalId($row['journal_id']);
		$pixelTag->setArticleId($row['article_id']);
		$pixelTag->setPrivateCode($row['private_code']);
		$pixelTag->setPublicCode($row['public_code']);
		$pixelTag->setDomain($row['domain']);
		$pixelTag->setDateOrdered($row['date_ordered']);
		$pixelTag->setDateAssigned($row['date_assigned']);
		$pixelTag->setDateRegistered($row['date_registered']);
		$pixelTag->setDateRemoved($row['date_removed']);
		$pixelTag->setStatus($row['status']);
		$pixelTag->setTextType($row['text_type']);

		HookRegistry::call('PixelTagDAO::_returnPixelTagFromRow', array(&$pixelTag, &$row));

		return $pixelTag;
	}

	/**
	 * Insert a new PixelTag.
	 * @param $pixelTag PixelTag
	 * @return int 
	 */
	function insertObject(&$pixelTag) {
		$ret = $this->update(
			sprintf('
				INSERT INTO pixel_tags
					(journal_id,
					article_id,
					private_code,
					public_code,
					domain,
					date_ordered,
					date_assigned,
					date_registered,
					date_removed,
					status,
					text_type)
				VALUES
					(?, ?, ?, ?, ?, %s, %s, %s, %s, ?, ?)',
				$this->datetimeToDB($pixelTag->getDateOrdered()),
				$this->datetimeToDB($pixelTag->getDateAssigned()),
				$this->datetimeToDB($pixelTag->getDateRegistered()),
				$this->datetimeToDB($pixelTag->getDateRemoved())
				),
			array(
				$pixelTag->getJournalId(),
				$pixelTag->getArticleId(),
				$pixelTag->getPrivateCode(),
				$pixelTag->getPublicCode(),
				$pixelTag->getDomain(),
				$pixelTag->getStatus(),
				$pixelTag->getTextType()
			)
		);
		$pixelTag->setId($this->getInsertPixelTagId());
		return $pixelTag->getId();
	}

	/**
	 * Update an existing pixel tag.
	 * @param $pixelTag PixelTag
	 * @return boolean
	 */
	function updateObject(&$pixelTag) {
		$this->update(
			sprintf('UPDATE pixel_tags
				SET
					journal_id = ?,
					article_id = ?,
					private_code = ?,
					public_code = ?,
					domain = ?,
					date_ordered = %s,
					date_assigned = %s,
					date_registered = %s,
					date_removed = %s,
					status = ?,
					text_type = ?
					WHERE pixel_tag_id = ?',
				$this->datetimeToDB($pixelTag->getDateOrdered()),
				$this->datetimeToDB($pixelTag->getDateAssigned()),
				$this->datetimeToDB($pixelTag->getDateRegistered()),
				$this->datetimeToDB($pixelTag->getDateRemoved())
				),
			array(
				$pixelTag->getJournalId(),
				$pixelTag->getArticleid(),
				$pixelTag->getPrivateCode(),
				$pixelTag->getPublicCode(),
				$pixelTag->getDomain(),
				$pixelTag->getStatus(),
				$pixelTag->getTextType(),
				$pixelTag->getId()
			)
		);
	}

	/**
	 * Delete a pixel tag.
	 * @param $pixelTag PixelTag
	 */
	function deleteObject($pixelTag) {
		$this->deletePixelTagById($pixelTag->getId());
	}

	/**
	 * Delete a pixel tag by pixel tag ID.
	 * @param $pixelTag int
	 */
	function deletePixelTagById($pixelTagId) {
		$this->update('DELETE FROM pixel_tags WHERE pixel_tag_id = ?', $pixelTagId);
	}

	/**
	 * Delete pixel tags by journal ID.
	 * @param $journalId int
	 */
	function deletePixelTagsByJournal($journalId) {
		$pixelTags = $this->getPixelTagsByJournalId($journalId);

		while (!$pixelTags->eof()) {
			$pixelTag =& $pixelTags->next();
			$this->deletePixelTagById($pixelTag->getId());
		}
	}

	/**
	 * Retrieve all pixel tags matching a particular journal ID.
	 * @param $journalId int
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $searchMatch string optional, type of the match ('is' vs. 'contains')
	 * @param $status int optional, status to match
	 * @param $rangeInfo object optional, DBRangeInfo object describing range of results to return
	 * @param $sortBy string optional, column name the results should be ordered by
	 * @param $sortDirection int optional, ascending (SORT_DIRECTION_ASC) or descending (SORT_DIRECTION_DESC)
	 * @return object DAOResultFactory containing matching PixelTag 
	 */
	function &getPixelTagsByJournalId($journalId, $searchType = null, $search = null, $searchMatch = null, $status = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$sql = 'SELECT DISTINCT * FROM pixel_tags ';
		$paramArray = array();

		switch ($searchType) {
			case PT_FIELD_PRIVCODE:
				$sql .= ' WHERE LOWER(private_code) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = $searchMatch == 'is' ? $search : "%$search%";
				break;
			case PT_FIELD_PUBCODE:
				$sql .= ' WHERE LOWER(public_code) ' . ($searchMatch=='is'?'=':'LIKE') . ' LOWER(?)';
				$paramArray[] = $searchMatch == 'is' ? $search : "%$search%";
				break;
			default:
				$searchType = null;
		}

		if (empty($searchType)) {
			$sql .= ' WHERE';
		} else {
			$sql .= ' AND';
		}

		if ($status != null) {
			$sql .= ' status = ? AND';
			$paramArray[] = (int) $status;
		}

		$sql .= ' journal_id = ?' . ($sortBy ? (' ORDER BY ' . $sortBy . ' ' . $this->getDirectionMapping($sortDirection)) : '');	
		$paramArray[] = (int) $journalId;

		$result =& $this->retrieveRange($sql, $paramArray, $rangeInfo);
		$returner = new DAOResultFactory($result, $this, '_returnPixelTagFromRow');
		return $returner;
	}

	/**
	 * Retrieve a pixel tag for a journal by article ID.
	 * @param $journalId int
	 * @param $articleId int
	 * @return PixelTag
	 */
	function &getPixelTagByArticleId($journalId, $articleId) {	
		$result =& $this->retrieve(
			'SELECT *
			FROM pixel_tags
			WHERE article_id = ?
			AND journal_id = ?',
			array(
				$articleId,
				$journalId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnPixelTagFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve the next available pixel tag for a journal.
	 * @param $journalId int
	 * @return PixelTag
	 */
	function &getAvailablePixelTag($journalId) {	
		$result =& $this->retrieveLimit(
			'SELECT * 
			FROM pixel_tags 
			WHERE journal_id = ? AND article_id IS NULL AND date_assigned IS NULL AND status = ?
			ORDER BY date_ordered', 
			array(
				(int)$journalId, 
				PT_STATUS_AVAILABLE
			),
			1
		);
		$returner = null;
		if (isset($result) && $result->RecordCount() != 0) {
			$returner =& $this->_returnPixelTagFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve pixel tags status counts for a journal.
	 * @param $journalId int
	 * @param $status int optional, pixel tag status to match
	 * @return int 
	 */
	function getPixelTagsStatusCount($journalId, $status = null) {
		$vgWortPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$vgWortPlugin->import('classes.PixelTag');

		$sql = 'SELECT COUNT(*)
				FROM pixel_tags
				WHERE journal_id = ?';
		$paramArray = array((int)$journalId);

		if ($status) {
			$sql .= ' AND status = ?';
			$paramArray[] = (int) $status;
		}

		$result =& $this->retrieve($sql, $paramArray);
		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Retrieve all pixel tags status counts for a journal.
	 * @param $journalId int
	 * @return array, status as index 
	 */
	function &getStatusCounts($journalId) {
		$vgWortPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$vgWortPlugin->import('classes.PixelTag');
		$counts = array();

		$counts[PT_STATUS_AVAILABLE] = $this->getPixelTagsStatusCount($journalId, PT_STATUS_AVAILABLE);
		$counts[PT_STATUS_UNREGISTERED] = $this->getPixelTagsStatusCount($journalId, PT_STATUS_UNREGISTERED);
		$counts[PT_STATUS_REGISTERED] = $this->getPixelTagsStatusCount($journalId, PT_STATUS_REGISTERED);
		
		return $counts;
	}

	/**
	 * Get the ID of the last inserted pixel tag.
	 * @return int
	 */
	function getInsertPixelTagId() {
		return $this->getInsertId('pixel_tags', 'pixel_tag_id');
	}
}

?>
