<?php
namespace Payment\View\Widget;

use Page\View\Widget\PageAbstractWidget;

class PaymentShoppingCartInfoWidget extends PageAbstractWidget
{
   /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (false === ($pageUrl = $this->getView()->pageUrl('shopping-cart'))) {
            return false;
        }

        return $this->getView()->partial('payment/widget/shopping-cart-info');
    }
}