<?php

namespace Payment\View\Widget;

class PaymentErrorWidget extends PaymentAbstractWidget
{
   /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        return $this->getSetting('payment_transaction_unsuccessful_message');
    }
}