<?php

/**
 * @defgroup subscription Subscription
 * Implement subscriptions, subscription management, and subscription checking.
 */

/**
 * @file classes/subscription/InstitutionalSubscription.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscription
 * @ingroup subscription
 *
 * @see InstitutionalSubscriptionDAO
 *
 * @brief Basic class describing an institutional subscription.
 */

namespace APP\subscription;

use PKP\db\DAORegistry;

class InstitutionalSubscription extends Subscription
{
    //
    // Get/set methods
    //

    /**
     * Get the institution ID of the subscription.
     *
     * @return int
     */
    public function getInstitutionId()
    {
        return $this->getData('institutionId');
    }

    /**
     * Set the institution ID of the subscription.
     *
     * @param $institutionId int
     */
    public function setInstitutionId($institutionId)
    {
        $this->setData('institutionId', $institutionId);
    }

    /**
     * Get the mailing address of the institutionalSubscription.
     *
     * @return string
     */
    public function getInstitutionMailingAddress()
    {
        return $this->getData('mailingAddress');
    }

    /**
     * Set the mailing address of the institutionalSubscription.
     *
     * @param $mailingAddress string
     */
    public function setInstitutionMailingAddress($mailingAddress)
    {
        return $this->setData('mailingAddress', $mailingAddress);
    }

    /**
     * Get institutionalSubscription domain string.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->getData('domain');
    }

    /**
     * Set institutionalSubscription domain string.
     *
     * @param $domain string
     */
    public function setDomain($domain)
    {
        return $this->setData('domain', $domain);
    }

    /**
     * Check whether subscription is valid
     *
     * @param $domain string
     * @param $IP string
     * @param $check int SUBSCRIPTION_DATE_... Test using either start date, end date, or both (default)
     * @param $checkDate date (YYYY-MM-DD) Use this date instead of current date
     *
     * @return int|false Found subscription ID, or false for none.
     */
    public function isValid($domain, $IP, $check = self::SUBSCRIPTION_DATE_BOTH, $checkDate = null)
    {
        $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
        return $subscriptionDao->isValidInstitutionalSubscription($domain, $IP, $this->getData('journalId'), $check, $checkDate);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\InstitutionalSubscription', '\InstitutionalSubscription');
}
