<?php

/**
 * @file classes/subscription/InstitutionalSubscriptionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscriptionDAO
 * @ingroup subscription
 *
 * @see InstitutionalSubscription
 *
 * @brief Operations for retrieving and modifying InstitutionalSubscription objects.
 */

namespace APP\subscription;

use APP\core\Application;
use APP\i18n\AppLocale;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;
use PKP\plugins\HookRegistry;

class InstitutionalSubscriptionDAO extends SubscriptionDAO
{
    public const SUBSCRIPTION_INSTITUTION_NAME = 0x20;
    public const SUBSCRIPTION_DOMAIN = 0x21;
    public const SUBSCRIPTION_IP_RANGE = 0x22;

    /**
     * Retrieve an institutional subscription by subscription ID.
     *
     * @param $subscriptionId int Subscription ID
     * @param $journalId int Journal ID
     *
     * @return InstitutionalSubscription
     */
    public function getById($subscriptionId, $journalId = null)
    {
        $params = [(int) $subscriptionId];
        if ($journalId) {
            $params[] = (int) $journalId;
        }
        $result = $this->retrieve(
            'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE	st.institutional = 1
				AND s.subscription_id = ?
				' . ($journalId ? ' AND s.journal_id = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Retrieve institutional subscriptions by user ID.
     *
     * @param $userId int
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing matching InstitutionalSubscriptions
     */
    public function getByUserId($userId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (st.type_id = s.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE	st.institutional = 1
				AND s.user_id = ?',
            [(int) $userId],
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve institutional subscriptions by user ID and journal ID.
     *
     * @param $userId int
     * @param $journalId int
     * @param $rangeInfo RangeInfo
     *
     * @return object DAOResultFactory containing matching InstitutionalSubscriptions
     */
    public function getByUserIdForJournal($userId, $journalId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT	s.*, iss.*
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
			WHERE	st.institutional = 1
				AND s.user_id = ?
				AND s.journal_id = ?',
            [(int) $userId, (int) $journalId],
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Return number of institutional subscriptions with given status for journal.
     *
     * @param status int
     * @param null|mixed $status
     *
     * @return int
     */
    public function getStatusCount($journalId, $status = null)
    {
        $params = [(int) $journalId];
        if ($status !== null) {
            $params[] = (int) $status;
        }
        $result = $this->retrieve(
            'SELECT	COUNT(*) AS row_count
			FROM	subscriptions s,
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 1 AND
				s.journal_id = ?
				' . ($status !== null ? ' AND s.status = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $row->row_count : 0;
    }

    /**
     * Get the number of institutional subscriptions for a particular journal.
     *
     * @param $journalId int
     *
     * @return int
     */
    public function getSubscribedUserCount($journalId)
    {
        return $this->getStatusCount($journalId);
    }

    /**
     * Check if an institutional subscription exists for a given subscriptionId.
     *
     * @param $subscriptionId int Subscription ID
     * @param $journalId int Optional journal ID
     *
     * @return boolean
     */
    public function subscriptionExists($subscriptionId, $journalId = null)
    {
        $params = [(int) $subscriptionId];
        if ($journalId) {
            $params[] = (int) $journalId;
        }
        $result = $this->retrieve(
            'SELECT	COUNT(*) AS row_count
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 1
				AND s.subscription_id = ?
				' . ($journalId ? ' AND s.journal_id = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an institutional subscription exists for a given user.
     *
     * @param $subscriptionId int Subscription ID
     * @param $userId int User ID
     *
     * @return boolean
     */
    public function subscriptionExistsByUser($subscriptionId, $userId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 1
				AND s.subscription_id = ?
				AND s.user_id = ?',
            [(int) $subscriptionId, (int) $userId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Check if an institutional subscription exists for a given user and journal.
     *
     * @param $userId int
     * @param $journalId int
     *
     * @return boolean
     */
    public function subscriptionExistsByUserForJournal($userId, $journalId)
    {
        $result = $this->retrieve(
            'SELECT	COUNT(*) AS row_count
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
			WHERE	st.institutional = 1
				AND s.user_id = ?
				AND s.journal_id = ?',
            [(int) $userId, (int) $journalId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Insert a new institutional subscription.
     *
     * @param $institutionalSubscription InstitutionalSubscription
     *
     * @return int
     */
    public function insertObject($institutionalSubscription)
    {
        $subscriptionId = null;
        if ($this->_insertObject($institutionalSubscription)) {
            $subscriptionId = $institutionalSubscription->getId();

            $this->update(
                'INSERT INTO institutional_subscriptions
				(subscription_id, institution_id, mailing_address, domain)
				VALUES
				(?, ?, ?, ?)',
                [
                    (int) $subscriptionId,
                    (int) $institutionalSubscription->getInstitutionId(),
                    $institutionalSubscription->getInstitutionMailingAddress(),
                    $institutionalSubscription->getDomain()
                ]
            );
        }

        return $subscriptionId;
    }

    /**
     * Update an existing institutional subscription.
     *
     * @param $institutionalSubscription InstitutionalSubscription
     *
     * @return boolean
     */
    public function updateObject($institutionalSubscription)
    {
        $this->_updateObject($institutionalSubscription);

        $this->update(
            'UPDATE	institutional_subscriptions
			SET	institution_id = ?,
				mailing_address = ?,
				domain = ?
			WHERE	subscription_id = ?',
            [
                (int) $institutionalSubscription->getInstitutionId(),
                $institutionalSubscription->getInstitutionMailingAddress(),
                $institutionalSubscription->getDomain(),
                (int) $institutionalSubscription->getId()
            ]
        );
    }

    /**
     * Delete an institutional subscription by subscription ID.
     *
     * @param $subscriptionId int
     * @param null|mixed $journalId
     */
    public function deleteById($subscriptionId, $journalId = null)
    {
        if (!$this->subscriptionExists($subscriptionId, $journalId)) {
            return;
        }

        $this->update('DELETE FROM subscriptions WHERE subscription_id = ?', [(int) $subscriptionId]);
        $this->update('DELETE FROM institutional_subscriptions WHERE subscription_id = ?', [(int) $subscriptionId]);
    }

    /**
     * Delete institutional subscriptions by journal ID.
     *
     * @param $journalId int
     */
    public function deleteByJournalId($journalId)
    {
        $result = $this->retrieve('SELECT s.subscription_id AS subscription_id FROM subscriptions s WHERE s.journal_id = ?', [(int) $journalId]);
        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
    }

    /**
     * Delete institutional subscriptions by user ID.
     *
     * @param $userId int
     */
    public function deleteByUserId($userId)
    {
        $result = $this->retrieve('SELECT s.subscription_id AS subscription_id FROM subscriptions s WHERE s.user_id = ?', [(int) $userId]);
        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
    }

    /**
     * Delete institutional subscriptions by user ID and journal ID.
     *
     * @param $userId int User ID
     * @param $journalId int Journal ID
     */
    public function deleteByUserIdForJournal($userId, $journalId)
    {
        $result = $this->retrieve('SELECT s.subscription_id AS subscription_id FROM subscriptions s WHERE s.user_id = ? AND s.journal_id = ?', [(int) $userId, (int) $journalId]);
        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
    }

    /**
     * Delete all institutional subscriptions by subscription type ID.
     *
     * @param $subscriptionTypeId int Subscription type ID
     */
    public function deleteByTypeId($subscriptionTypeId)
    {
        $result = $this->retrieve('SELECT s.subscription_id AS subscription_id FROM subscriptions s WHERE s.type_id = ?', [(int) $subscriptionTypeId]);
        foreach ($result as $row) {
            $this->deleteById($row->subscription_id);
        }
    }

    /**
     * Retrieve all institutional subscriptions.
     *
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing InstitutionalSubscriptions
     */
    public function getAll($rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT	s.*, iss.*
                ' . $this->getInstitutionNameFetchColumns() . '
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
                ' . $this->getInstitutionNameFetchJoins() . '
			WHERE	st.institutional = 1
            ORDER BY institution_name ASC, s.subscription_id',
            $this->getInstitutionNameFetchParameters(),
            $rangeInfo
        );
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve institutional subscriptions matching a particular journal ID.
     *
     * @param $journalId int
     * @param null|mixed $rangeInfo
     * @param null|mixed $status
     * @param null|mixed $searchField
     * @param null|mixed $searchMatch
     * @param null|mixed $search
     * @param null|mixed $dateField
     * @param null|mixed $dateFrom
     * @param null|mixed $dateTo
     *
     * @return object DAOResultFactory containing matching Subscriptions
     */

    public function getByJournalId($journalId, $status = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $rangeInfo = null)
    {
        $userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
        $params = array_merge($userDao->getFetchParameters(), $this->getInstitutionNameFetchParameters(), [(int) $journalId]);
        $institutionFetch = $ipRangeFetch = '';
        $searchSql = $this->_generateSearchSQL($status, $searchField, $searchMatch, $search, $dateField, $dateFrom, $dateTo, $params);

        if (!empty($search)) {
            switch ($searchField) {
            case self::SUBSCRIPTION_INSTITUTION_NAME:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(insl.setting_value) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(insl.setting_value) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(insl) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $institutionFetch = 'JOIN institution_settings insl ON (insl.institution_id = iss.institution_id AND insl.setting_name = \'name\')';
                $params[] = $search;
                break;
            case self::SUBSCRIPTION_DOMAIN:
                if ($searchMatch === 'is') {
                    $searchSql = ' AND LOWER(iss.domain) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(iss.domain) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(iss.domain) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $params[] = $search;
                break;
            case self::SUBSCRIPTION_IP_RANGE:
                if ($searchMatch === 'inip') {
                    $searchSql = ' AND LOWER(inip.ip_string) = LOWER(?)';
                } elseif ($searchMatch === 'contains') {
                    $searchSql = ' AND LOWER(inip.ip_string) LIKE LOWER(?)';
                    $search = '%' . $search . '%';
                } else { // $searchMatch === 'startsWith'
                    $searchSql = ' AND LOWER(inip.ip_string) LIKE LOWER(?)';
                    $search = $search . '%';
                }
                $ipRangeFetch = ' JOIN institution_ip inip ON (inip.institution_id = iss.institution_id)';
                $params[] = $search;
                break;
            }
        }

        $result = $this->retrieveRange(
            $sql = 'SELECT DISTINCT s.*, iss.institution_id, iss.mailing_address, iss.domain,
                    ' . $this->getInstitutionNameFetchColumns() . ',
                    ' . $userDao->getFetchColumns() . '
                FROM	subscriptions s
                    JOIN subscription_types st ON (s.type_id = st.type_id)
                    JOIN users u ON (s.user_id = u.user_id)
                    JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
                    ' . $institutionFetch . '
                    ' . $userDao->getFetchJoins() . '
                    ' . $ipRangeFetch . '
                    ' . $this->getInstitutionNameFetchJoins() . '
                WHERE	st.institutional = 1 AND s.journal_id = ?
                ' . $searchSql . ' ORDER BY institution_name ASC, s.subscription_id',
            $params,
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow', [], $sql, $params, $rangeInfo); // Counted in subscription grid paging
    }


    /**
     * Check whether there is a valid institutional subscription for a given journal.
     *
     * @param $journalId int
     * @param null|mixed $checkDate
     *
     * @return int|false Found subscription ID, or false for none.
     */
    public function isValidInstitutionalSubscription($domain, $IP, $journalId, $check = Subscription::SUBSCRIPTION_DATE_BOTH, $checkDate = null)
    {
        if (empty($journalId) || (empty($domain) && empty($IP))) {
            return false;
        }

        $today = $this->dateToDB(Core::getCurrentDate());

        if ($checkDate == null) {
            $checkDate = $today;
        } else {
            $checkDate = $this->dateToDB($checkDate);
        }

        switch ($check) {
            case Subscription::SUBSCRIPTION_DATE_START:
                $dateSql = sprintf('%s >= s.date_start AND %s >= s.date_start', $checkDate, $today);
                break;
            case Subscription::SUBSCRIPTION_DATE_END:
                $dateSql = sprintf('%s <= s.date_end AND %s >= s.date_start', $checkDate, $today);
                break;
            default:
                $dateSql = sprintf('%s >= s.date_start AND %s <= s.date_end', $checkDate, $checkDate);
        }

        // Check if domain match
        if (!empty($domain)) {
            $result = $this->retrieve(
                '
                SELECT	iss.subscription_id
                FROM	institutional_subscriptions iss
                    JOIN subscriptions s ON (iss.subscription_id = s.subscription_id)
                    JOIN subscription_types st ON (s.type_id = st.type_id)
                WHERE	POSITION(UPPER(LPAD(iss.domain, LENGTH(iss.domain)+1, \'.\')) IN UPPER(LPAD(?, LENGTH(?)+1, \'.\'))) != 0
                    AND iss.domain != \'\'
                    AND s.journal_id = ?
                    AND s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
                    AND st.institutional = 1
                    AND ((st.duration IS NULL) OR (st.duration IS NOT NULL AND (' . $dateSql . ')))
                    AND (st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
                    OR st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')',
                [$domain, $domain, (int) $journalId]
            );
            $row = $result->current();
            if ($row) {
                return $row->subscription_id;
            }
        }

        // Check for IP match
        if (!empty($IP)) {
            $IP = sprintf('%u', ip2long($IP));
            $result = $this->retrieve(
                'SELECT	iss.subscription_id
                FROM	institutional_subscriptions iss
                    JOIN institution_ip iip ON (iip.institution_id = iss.institution_id)
                    JOIN subscriptions s ON (iss.subscription_id = s.subscription_id)
                    JOIN subscription_types st ON (s.type_id = st.type_id)
                WHERE	((iip.ip_end IS NOT NULL
                    AND ? >= iip.ip_start AND ? <= iip.ip_end
                    AND s.journal_id = ?
                    AND s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
                    AND st.institutional = 1
                    AND ((st.duration IS NULL) OR (st.duration IS NOT NULL AND (' . $dateSql . ')))
                    AND (st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
                        OR st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . '))
                    OR  (iip.ip_end IS NULL
                    AND ? = iip.ip_start
                    AND s.journal_id = ?
                    AND s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
                    AND st.institutional = 1
                    AND ((st.duration IS NULL) OR (st.duration IS NOT NULL AND (' . $dateSql . ')))
                    AND (st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_ONLINE . '
                    OR st.format = ' . SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE . ')))',
                [$IP, $IP, (int) $journalId, $IP, (int) $journalId]
            );
            $row = $result->current();
            if ($row) {
                return $row->subscription_id;
            }
        }

        return false;
    }


    /**
     * Retrieve active institutional subscriptions matching a particular end date and journal ID.
     *
     * @param $dateEnd date (YYYY-MM-DD)
     * @param $journalId int
     * @param null|mixed $rangeInfo
     *
     * @return object DAOResultFactory containing matching InstitutionalSubscriptions
     */
    public function getByDateEnd($dateEnd, $journalId, $rangeInfo = null)
    {
        $dateEnd = explode('-', $dateEnd);

        $params = array_merge([$dateEnd[0], $dateEnd[1], dateEnd[2], (int) $journalId], $this->getInstitutionNameFetchParameters());

        $result = $this->retrieveRange(
            'SELECT	s.*, iss.*
                ' . $this->getInstitutionNameFetchColumns() . ',
			FROM	subscriptions s
				JOIN subscription_types st ON (s.type_id = st.type_id)
				JOIN institutional_subscriptions iss ON (s.subscription_id = iss.subscription_id)
                ' . $this->getInstitutionNameFetchJoins() . '
			WHERE	s.status = ' . Subscription::SUBSCRIPTION_STATUS_ACTIVE . '
				AND st.institutional = 1
				AND EXTRACT(YEAR FROM s.date_end) = ?
				AND EXTRACT(MONTH FROM s.date_end) = ?
				AND EXTRACT(DAY FROM s.date_end) = ?
				AND s.journal_id = ?
            ORDER BY institution_name ASC, s.subscription_id',
            $params,
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Renew an institutional subscription by dateEnd + duration of subscription type
     * if the institutional subscription is expired, renew to current date + duration
     *
     * @param $institutionalSubscription InstitutionalSubscription
     *
     * @return boolean
     */
    public function renewSubscription($institutionalSubscription)
    {
        return $this->_renewSubscription($institutionalSubscription);
    }

    /**
     * Generator function to create object.
     *
     * @return InstitutionalSubscription
     */
    public function newDataObject()
    {
        return new InstitutionalSubscription();
    }

    /**
     * Internal function to return an InstitutionalSubscription object from a row.
     *
     * @param $row array
     *
     * @return InstitutionalSubscription
     */
    public function _fromRow($row)
    {
        $institutionalSubscription = parent::_fromRow($row);

        $institutionalSubscription->setInstitutionId($row['institution_id']);
        $institutionalSubscription->setInstitutionMailingAddress($row['mailing_address']);
        $institutionalSubscription->setDomain($row['domain']);

        HookRegistry::call('InstitutionalSubscriptionDAO::_fromRow', [&$institutionalSubscription, &$row]);

        return $institutionalSubscription;
    }


    /**
     * Return a list of extra parameters to bind to the institution fetch queries.
     *
     * @return array
     */
    public function getInstitutionNameFetchParameters()
    {
        $locale = AppLocale::getLocale();
        $journal = Application::get()->getRequest()->getContext();
        $primaryLocale = $journal->getPrimaryLocale();
        return [
            'name', $locale,
            'name', $primaryLocale,
        ];
    }

    /**
     * Return a SQL snippet of extra columns to fetch during institution fetch queries.
     *
     * @return string
     */
    public function getInstitutionNameFetchColumns()
    {
        return 'COALESCE(isal.setting_value, isapl.setting_value) AS institution_name';
    }

    /**
     * Return a SQL snippet of extra joins to include during institution fetch queries.
     *
     * @return string
     */
    public function getInstitutionNameFetchJoins()
    {
        return 'LEFT JOIN institution_settings isal ON (isal.institution_id = iss.institution_id AND isal.setting_name = ? AND isal.locale = ?)
        LEFT JOIN institution_settings isapl ON (isapl.institution_id = iss.institution_id AND isapl.setting_name = ? AND isapl.locale = ?)';
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\InstitutionalSubscriptionDAO', '\InstitutionalSubscriptionDAO');
    foreach ([
        'SUBSCRIPTION_INSTITUTION_NAME',
        'SUBSCRIPTION_DOMAIN',
        'SUBSCRIPTION_IP_RANGE',
    ] as $constantName) {
        define($constantName, constant('\InstitutionalSubscriptionDAO::' . $constantName));
    }
}
