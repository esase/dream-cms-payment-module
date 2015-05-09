<?php
namespace Payment\View\Widget;

use Page\View\Widget\PageAbstractWidget;
use Page\Service\Page as PageService;

class PaymentShoppingCartInfoWidget extends PageAbstractWidget
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
        return self::SHOPPING_CART_PAGE == PageService::getCurrentPage()['slug'];
    }
}