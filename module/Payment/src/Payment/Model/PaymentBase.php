<?php
namespace Payment\Model;

use Application\Model\ApplicationAbstractBase;

class PaymentBase extends ApplicationAbstractBase
{
    /**
     * Transaction paid
     */
    const TRANSACTION_PAID = 1;

    /**
     * Transaction not paid
     */
    const TRANSACTION_NOT_PAID = 0;
}