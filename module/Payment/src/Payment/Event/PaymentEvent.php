<?php
namespace Payment\Event;

use Application\Service\ApplicationSetting as ApplicationSettingService;
use User\Service\UserIdentity as UserIdentityService;
use Application\Event\ApplicationAbstractEvent;
use Application\Utility\ApplicationEmailNotification as ApplicationEmailNotificationUtility;
use Localization\Service\Localization as LocalizationService;

class PaymentEvent extends ApplicationAbstractEvent
{
    /**
     * Delete payment transaction event
     */
    const DELETE_PAYMENT_TRANSACTION = 'delete_payment_transaction';

    /**
     * Activate payment transaction event
     */
    const ACTIVATE_PAYMENT_TRANSACTION = 'activate_payment_transaction';

    /**
     * Add payment currency event
     */
    const ADD_PAYMENT_CURRENCY = 'add_payment_currency';

    /**
     * Delete payment currency event
     */
    const DELETE_PAYMENT_CURRENCY = 'delete_payment_currency';

    /**
     * Edit payment currency event
     */
    const EDIT_PAYMENT_CURRENCY = 'edit_payment_currency';

    /**
     * Fire activate payment transaction event
     *
     * @param integer $transactionId
     * @param boolean $isSystemEvent
     * @param array $transactionInfo
     *      string first_name
     *      string last_name
     *      string email
     *      string id
     * @return void
     */
    public static function fireActivatePaymentTransactionEvent($transactionId, $isSystemEvent = false, $transactionInfo = [])
    {
        // event's description
        $eventDesc = $isSystemEvent
            ? 'Event - Payment transaction activated by the system'
            : (UserIdentityService::isGuest() ? 'Event - Payment transaction activated by guest'
                    : 'Event - Payment transaction activated by user');

        $eventDescParams = $isSystemEvent
            ? [$transactionId]
            : (UserIdentityService::isGuest() ? [$transactionId]
                    : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $transactionId]);

        self::fireEvent(self::ACTIVATE_PAYMENT_TRANSACTION, 
                $transactionId, self::getUserId($isSystemEvent), $eventDesc, $eventDescParams);

        // send an email notification about the paid transaction
        if ($transactionInfo && (int) ApplicationSettingService::getSetting('payment_transaction_paid')) {
            ApplicationEmailNotificationUtility::sendNotification(ApplicationSettingService::getSetting('application_site_email'),
                ApplicationSettingService::getSetting('payment_transaction_paid_title', LocalizationService::getDefaultLocalization()['language']),
                ApplicationSettingService::getSetting('payment_transaction_paid_message', LocalizationService::getDefaultLocalization()['language']), [
                    'find' => [
                        'FirstName',
                        'LastName',
                        'Email',
                        'Id'
                    ],
                    'replace' => [
                        $transactionInfo['first_name'],
                        $transactionInfo['last_name'],
                        $transactionInfo['email'],
                        $transactionInfo['id']
                    ]
                ]);
        }
    }

    /**
     * Fire delete payment transaction event
     *
     * @param integer $transactionId
     * @param string $type (available types are: system, user)
     * @return void
     */
    public static function fireDeletePaymentTransactionEvent($transactionId, $type = null)
    {
        switch($type) {
            case 'system' :
                $eventDesc = 'Event - Payment transaction deleted by the system';
                $eventDescParams = [$transactionId];
                break;

            case 'user' :
                $eventDesc = 'Event - Payment transaction deleted by user';
                $eventDescParams = [UserIdentityService::getCurrentUserIdentity()['nick_name'], $transactionId];
                break;

            default :
                // event's description
                $eventDesc = UserIdentityService::isGuest()
                    ? 'Event - Payment transaction deleted by guest'
                    : 'Event - Payment transaction deleted by user';

                $eventDescParams = UserIdentityService::isGuest()
                    ? [$transactionId]
                    : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $transactionId];
        }

        self::fireEvent(self::DELETE_PAYMENT_TRANSACTION, 
                $transactionId, self::getUserId(($type == 'system')), $eventDesc, $eventDescParams);
    }

    /**
     * Fire add payment currency event
     *
     * @param integer $currencyId
     * @return void
     */
    public static function fireAddPaymentCurrencyEvent($currencyId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Payment currency added by guest'
            : 'Event - Payment currency added by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$currencyId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $currencyId];

        self::fireEvent(self::ADD_PAYMENT_CURRENCY, 
                $currencyId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete payment currency event
     *
     * @param integer $currencyId
     * @return void
     */
    public static function fireDeletePaymentCurrencyEvent($currencyId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Payment currency deleted by guest'
            : 'Event - Payment currency deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$currencyId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $currencyId];

        self::fireEvent(self::DELETE_PAYMENT_CURRENCY, 
                $currencyId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire edit payment currency event
     *
     * @param integer $currencyId
     * @return void
     */
    public static function fireEditPaymentCurrencyEvent($currencyId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Payment currency edited by guest'
            : 'Event - Payment currency edited by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$currencyId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $currencyId];

        self::fireEvent(self::EDIT_PAYMENT_CURRENCY, 
                $currencyId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}