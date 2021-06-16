<?php

/**
 * @file classes/subscription/form/UserInstitutionalSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserInstitutionalSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for user purchase of institutional subscription.
 */

use APP\facades\Repo;

use APP\payment\ojs\OJSPaymentManager;
use APP\subscription\Subscription;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;

use PKP\form\Form;

class UserInstitutionalSubscriptionForm extends Form
{
    /** @var PKPRequest */
    public $request;

    /** @var userId int the user associated with the subscription */
    public $userId;

    /** @var subscription the subscription being purchased */
    public $subscription;

    /** @var subscriptionTypes Array subscription types */
    public $subscriptionTypes;

    /** @var array of the journal institutions [institutionId => name] */
    public $institutions;

    /**
     * Constructor
     *
     * @param $request PKPRequest
     * @param $userId int
     * @param $subscriptionId int
     */
    public function __construct($request, $userId = null, $subscriptionId = null)
    {
        parent::__construct('frontend/pages/purchaseInstitutionalSubscription.tpl');

        $this->userId = isset($userId) ? (int) $userId : null;
        $this->subscription = null;
        $this->request = $request;

        $subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;

        if (isset($subscriptionId)) {
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $subscriptionDao InstitutionalSubscriptionDAO */
            if ($subscriptionDao->subscriptionExists($subscriptionId)) {
                $this->subscription = $subscriptionDao->getById($subscriptionId);
            }
        }

        $journal = $this->request->getJournal();
        $journalId = $journal->getId();

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
        $subscriptionTypes = $subscriptionTypeDao->getByInstitutional($journalId, true, false);
        $this->subscriptionTypes = $subscriptionTypes->toArray();

        $collector = Repo::institution()->getCollector()->filterByContextIds([$journalId]);
        $institutions = Repo::institution()->getMany($collector);
        $this->institutions = [];
        foreach ($institutions as $institution) {
            $this->institutions[$institution->getId()] = $institution->getLocalizedName();
        }

        // Ensure subscription type is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'typeId', 'required', 'user.subscriptions.form.typeIdValid', function ($typeId) use ($journalId) {
            $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
            return $subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) && !$subscriptionTypeDao->getSubscriptionTypeDisablePublicDisplay($typeId);
        }));

        // Ensure institution ID exists
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'institutionId', 'required', 'manager.subscriptions.form.institutionIdValid', function ($institutionId) use ($journalId) {
            return Repo::institution()->existsByContextId($institutionId, $journalId);
        }));

        // If provided, domain is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'domain', 'optional', 'user.subscriptions.form.domainValid', '/^' .
                '[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
                '\.' .
                '[A-Z]{2,4}' .
            '$/i'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Initialize form data from current subscription.
     */
    public function initData()
    {
        if (isset($this->subscription)) {
            $subscription = $this->subscription;
            $this->_data = [
                'institutionId' => $this->subscription->getInstitutionId(),
                'institutionMailingAddress' => $subscription->getInstitutionMailingAddress(),
                'domain' => $subscription->getDomain()
            ];
        }
    }

    /**
     * @copydoc Form::display
     *
     * @param null|mixed $request
     * @param null|mixed $template
     */
    public function display($request = null, $template = null)
    {
        if (is_null($request)) {
            $request = $this->request;
        }
        $templateMgr = TemplateManager::getManager($this->request);
        $templateMgr->assign([
            'subscriptionId' => $this->subscription ? $this->subscription->getId() : null,
            'subscriptionTypes' => $this->subscriptionTypes,
            'institutionId' => $this->subscription ? $this->subscription->getInstitutionId() : null,
            'institutions' => $this->institutions,
        ]);
        parent::display($request, $template);
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['typeId', 'membership', 'institutionId', 'institutionMailingAddress', 'domain']);

        // If subscription type requires it, membership is provided
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
        $needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

        if ($needMembership) {
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'membership', 'required', 'user.subscriptions.form.membershipRequired'));
        }

        $institution = Repo::institution()->get($this->getData('institutionId'));
        $ipRanges = $institution->getIPRanges();

        // Domain or at least one IP range has been provided
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'domain', 'required', 'user.subscriptions.form.domainIPRangeRequired', function ($domain) use ($ipRanges) {
            return ($domain != '' || !empty($ipRanges)) ? true : false;
        }));
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $journal = $this->request->getJournal();
        $journalId = $journal->getId();
        $typeId = $this->getData('typeId');
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
        $institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var $institutionalSubscriptionDao InstitutionalSubscriptionDAO */
        $subscriptionType = $subscriptionTypeDao->getById($typeId);
        $nonExpiring = $subscriptionType->getNonExpiring();
        $today = date('Y-m-d');

        if (!isset($this->subscription)) {
            $subscription = $institutionalSubscriptionDao->newDataObject();
            $subscription->setJournalId($journalId);
            $subscription->setUserId($this->userId);
            $subscription->setReferenceNumber(null);
            $subscription->setNotes(null);
        } else {
            $subscription = $this->subscription;
        }

        $paymentManager = Application::getPaymentManager($journal);
        $paymentPlugin = $paymentManager->getPaymentPlugin();

        if ($paymentPlugin->getName() == 'ManualPayment') {
            $subscription->setStatus(Subscription::SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT);
        } else {
            $subscription->setStatus(Subscription::SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT);
        }

        $subscription->setTypeId($typeId);
        $subscription->setMembership($this->getData('membership') ? $this->getData('membership') : null);
        $subscription->setDateStart($nonExpiring ? null : $today);
        $subscription->setDateEnd($nonExpiring ? null : $today);
        $subscription->setInstitutionId($this->getData('institutionId'));
        $subscription->setInstitutionMailingAddress($this->getData('institutionMailingAddress'));
        $subscription->setDomain($this->getData('domain'));

        if ($subscription->getId()) {
            $institutionalSubscriptionDao->updateObject($subscription);
        } else {
            $institutionalSubscriptionDao->insertObject($subscription);
        }

        $queuedPayment = $paymentManager->createQueuedPayment($this->request, OJSPaymentManager::PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $this->userId, $subscription->getId(), $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
        $paymentManager->queuePayment($queuedPayment);

        $paymentForm = $paymentManager->getPaymentForm($queuedPayment);
        $paymentForm->display($this->request);
        parent::execute(...$functionArgs);
    }
}
