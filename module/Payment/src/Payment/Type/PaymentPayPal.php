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

class PaymentPayPal extends PaymentAbstractType
{
    /**
     * Success status
     */
    const PAYMENT_STATUS_SUCCESS = 'VERIFIED';

    /**
     * Payment url
     *
     * @var string
     */
    protected $paymentUrl = 'https://www.paypal.com/cgi-bin/webscr';

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
            'amount' => number_format($itemsAmount, 2),
            'cmd' => '_xclick',
            'business' => SettingService::getSetting('payment_paypal_email'),
            'item_name' => SettingService::getSetting('paypal_title'),
            'item_number' => $transactionInfo['slug'],
            'currency_code' => $transactionInfo['currency_code'],
            'no_note' => 1,
            'no_shipping' => 1,
            'return' => $this->getSuccessUrl(),
            'notify_url' => $this->getNotifyUrl('paypal'),
            'cancel_return' => $this->getErrorUrl(),
            'charset' => 'utf-8'
        ];
    }

    /**
     * Validate payment
     *
     * @return boolean|array
     */
    public function validatePayment()
    {
        if ($this->request->isPost()) {
            $nvpStr = null;

            // collect post params
            foreach ($this->request->getPost() as $key => $value) {
                $value = urlencode(stripslashes($value));
                $nvpStr .= "$key=$value&";
            }

            $nvpStr .= 'cmd=_notify-validate';
            $url = parse_url($this->paymentUrl);

            $str = file_get_contents('https://' . $url['host'] . '/cgi-bin/webscr?' . $nvpStr);

            // validate the payment
            if (mb_strstr($str, self::PAYMENT_STATUS_SUCCESS) !== false) {
                // get transaction info
                if (null != ($transactionInfo = $this->model->
                        getTransactionInfo($this->request->getPost('item_number'), true, 'slug', true, 0, false))) {

                    // check the currency code and amount
                    if ($transactionInfo['currency_code'] == $this->request->getPost('mc_currency')
                                && (float) $this->request->getPost('mc_gross') >= $transactionInfo['amount']) {

                        return $transactionInfo;  
                    }
                }
            }
        }

        return false;
    }
}