<?php

namespace Payment\View\Widget;

class PaymentSuccessWidget extends PaymentAbstractWidget
{
   /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        return $this->getSetting('payment_transaction_successful_message');
    }
}