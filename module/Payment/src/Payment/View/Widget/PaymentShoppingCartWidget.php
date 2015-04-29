<?php
namespace Payment\View\Widget;

use Page\View\Widget\PageAbstractWidget;

class PaymentShoppingCartWidget extends PageAbstractWidget
{
   /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        return $this->getView()->partial('payment/widget/shopping-cart');
    }
}