<?php

namespace Payment\Model;

use Application\Service\ApplicationSetting as SettingService;

class PaymentProcess extends PaymentBase
{
    /**
     * Get the payment type info
     *
     * @param string $name
     * @return array
     */
    public function getPaymentTypeInfo($name)
    {
        $select = $this->select();
        $select->from('payment_type')
            ->columns([
                'id',
                'name',
                'description',
                'enable_option',
                'handler'
            ])
            ->where([
                'name' => $name
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result  = $statement->execute();
        $payment = $result->current();

        return (int) SettingService::getSetting($payment['enable_option']) 
            ? $payment
            : [];
    }
}