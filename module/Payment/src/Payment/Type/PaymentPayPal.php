<?php

namespace Payment\Type;

use Application\Service\ApplicationSetting as SettingService;

class PaymentPayPal extends PaymentAbstractType
{
    /**
     * Payment url
     * @var string
     */
    protected $paymentUrl = 'https://www.paypal.com/cgi-bin/webscr';

    /**
     * Success status
     */
    const PAYMENT_STATUS_SUCCESS = 'VERIFIED';

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
     *      integer discount_cupon
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