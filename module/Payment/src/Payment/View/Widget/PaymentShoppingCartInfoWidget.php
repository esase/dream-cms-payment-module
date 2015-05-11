<?php

namespace Payment\View\Widget;

use Page\Service\Page as PageService;

class PaymentShoppingCartInfoWidget extends PaymentAbstractWidget
{
    /**
     * Shopping cart page
     */
    const SHOPPING_CART_PAGE = 'shopping-cart';

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

        return $this->getView()->partial('payment/widget/shopping-cart-info', [
            'is_shopping_cart_page' => $this->isShoppingCartPage()
        ]);
    }

    /**
     * Is shoping cart page
     * 
     * @return boolean
     */
    protected function isShoppingCartPage()
    {
        return !empty(PageService::getCurrentPage()['slug'])
                && self::SHOPPING_CART_PAGE == PageService::getCurrentPage()['slug'];
    }
}