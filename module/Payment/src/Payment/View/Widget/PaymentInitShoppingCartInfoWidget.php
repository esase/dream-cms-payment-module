<?php
namespace Payment\View\Widget;

use Page\View\Widget\PageAbstractWidget;

class PaymentInitShoppingCartInfoWidget extends PageAbstractWidget
{
    /**
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {
        $this->getView()->layoutHeadLink()->
                appendStylesheet($this->getView()->layoutAsset('main.css', 'css', 'payment'));

        if (!$this->getView()->localization()->isCurrentLanguageLtr()) {
            $this->getView()->layoutHeadLink()->
                    appendStylesheet($this->getView()->layoutAsset('main.rtl.css', 'css', 'payment'));
        }

        $this->getView()->layoutHeadScript()->
                appendFile($this->getView()->layoutAsset('payment.js', 'js', 'payment'));
    }

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

        return $this->getView()->partial('payment/widget/init-shopping-cart-info');
    }
}