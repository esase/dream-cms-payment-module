<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Payment\Controller;

use Application\Controller\ApplicationAbstractBaseController;
use User\Service\UserIdentity as UserIdentityService;
use Localization\Service\Localization as LocalizationService;
use Application\Utility\ApplicationEmailNotification as EmailNotificationUtility;

class PaymentProcessController extends ApplicationAbstractBaseController
{
    /**
     * Model instance
     *
     * @var \Payment\Model\PaymentProcess
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Payment\Model\PaymentProcess
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\PaymentProcess');
        }

        return $this->model;
    }

    /**
     * Process action
     */
    public function processAction()
    {
        // get the payment's  type info
        if (null == ($payment =
                $this->getModel()->getPaymentTypeInfo($this->getSlug()))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the payment type instance
        $paymentInstance = $this->getServiceLocator()
            ->get('Payment\Type\PaymentTypeManager')
            ->getInstance($payment['handler']);

        // validate the payment
        if (false !== ($transactionInfo = $paymentInstance->validatePayment())) {
            if (true === ($result = $this->
                    getModel()->activateTransaction($transactionInfo, $payment['id'], true, true))) {

                // send an email notification about the paid transaction
                if ((int) $this->applicationSetting('payment_transaction_paid_users')) {
                    // get the user's info
                    $userInfo = !empty($transactionInfo['user_id'])
                        ? UserIdentityService::getUserInfo($transactionInfo['user_id'])
                        : [];

                    $notificationLanguage = !empty($userInfo['language'])
                        ? $userInfo['language'] // we should use the user's language
                        : LocalizationService::getDefaultLocalization()['language'];

                    EmailNotificationUtility::sendNotification($transactionInfo['email'],
                            $this->applicationSetting('payment_transaction_paid_users_title', $notificationLanguage),
                            $this->applicationSetting('payment_transaction_paid_users_message', $notificationLanguage), [
                                'find' => [
                                    'Id',
                                    'PaymentType'
                                ],
                                'replace' => [
                                    $transactionInfo['slug'],
                                    $this->getTranslator()->translate($payment['description'],
                                            'default', LocalizationService::getLocalizations()[$notificationLanguage]['locale'])
                                ]
                            ]);
                }
            }
        }
        else {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return $this->getResponse();
    }
}