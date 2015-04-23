<?php
namespace Payment\Event;

use User\Service\UserIdentity as UserIdentityService;
use Application\Event\ApplicationAbstractEvent;

class PaymentEvent extends ApplicationAbstractEvent
{
    /**
     * Delete payment transaction event
     */
    const DELETE_PAYMENT_TRANSACTION = 'delete_payment_transaction';

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
}