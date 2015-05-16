<?php

namespace Payment\Controller;

use Application\Controller\ApplicationAbstractBaseController;
use User\Service\UserIdentity as UserIdentityService;
use Localization\Service\Localization as LocalizationService;
use Application\Utility\ApplicationEmailNotification as EmailNotificationUtility;

class PaymentProcessController extends ApplicationAbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
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