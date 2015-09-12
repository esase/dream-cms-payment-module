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
namespace Payment\Type;

use Application\Service\ApplicationSetting as SettingService;
use Localization\Service\Localization as LocalizationService;

class PaymentRBKMoney extends PaymentAbstractType
{
    /**
     * Success status
     */
    const PAYMENT_STATUS_SUCCESS = 5;

    /**
     * Payment url
     *
     * @var string
     */
    protected $paymentUrl = 'https://rbkmoney.ru/acceptpurchase.aspx';

    /**
     * Get payment url
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }

    /**
     * Get payment options
     *
     * @param float $itemsAmount
     * @param array $transactionInfo
     *      integer id
     *      string slug
     *      integer user_id
     *      string first_name
     *      string last_name
     *      string phone
     *      string address
     *      string email
     *      integer currency
     *      integer payment_type
     *      integer discount_coupon
     *      string currency_code
     *      string payment_name 
     * @return array
     */
    public function getPaymentOptions($itemsAmount, array $transactionInfo)
    {
        return [
            'eshopId' => SettingService::getSetting('payment_rbk_eshop_id'),
            'orderId' => $transactionInfo['slug'],
            'successUrl' => $this->getSuccessUrl(),
            'failUrl' => $this->getErrorUrl(),
            'serviceName' => SettingService::getSetting('payment_rbk_money_title'), 
            'language' => LocalizationService::getCurrentLocalization()['language'],
            'recipientAmount' => number_format($itemsAmount, 2),
            'recipientCurrency' => $transactionInfo['currency_code'],
            'user_email' => $transactionInfo['email']
        ];
    }

    /**
     * Validate payment
     *
     * @return boolean|array
     */
    public function validatePayment()
    {
        // validate the hash
        if ($this->request->isPost()
                && null !== ($hash = $this->request->getPost('hash', null))) {

            $postParams = [
                'orderId',
                'serviceName',
                'recipientAmount',
                'recipientCurrency',
                'paymentStatus',
                'userName',
                'userEmail',
                'paymentData'
            ];

            $controlHash = SettingService::getSetting('payment_rbk_eshop_id');
            $controlHash .= '::';

            // collect hash's parts
            foreach ($postParams as $paramName) {
                $controlHash .= $this->request->getPost($paramName);
                $controlHash .= '::';

                if ($paramName == 'serviceName') {
                    $controlHash .= SettingService::getSetting('payment_rbk_account');
                    $controlHash .= '::';
                }
            }

            $controlHash .= SettingService::getSetting('payment_rbk_secret');

            // compare the hashes
            if ($hash == md5($controlHash)
                        && self::PAYMENT_STATUS_SUCCESS == $this->request->getPost('paymentStatus')) {

                // get transaction info
                if (null != ($transactionInfo = $this->model->
                        getTransactionInfo($this->request->getPost('orderId'), true, 'slug', true, 0, false))) {

                    // check the currency code and amount
                    if ($transactionInfo['currency_code'] == $this->request->getPost('recipientCurrency')
                                && (float) $this->request->getPost('recipientAmount') >= $transactionInfo['amount']) {

                        echo 'ok';

                        return $transactionInfo;  
                    }
                }
            }
        }

        return false;
    }
}