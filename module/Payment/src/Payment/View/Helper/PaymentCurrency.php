<?php
namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Service\Payment as PaymentService;

class PaymentCurrency extends AbstractHelper
{
   /**
    * Currency
    *
    * @return PaymentCurrency - fluent interface
   */
   public function __invoke()
   {
      return $this;
   }

   /**
    * Get exchange rates 
    *
    * @return array
    */
   public function getExchangeRates($mergePrimaryCurrency = true)
   {
      $rates = [];

      if (PaymentService::getExchangeRates()) {
         $rates = $mergePrimaryCurrency
            ? array_merge(PaymentService::getExchangeRates(), array(PaymentService::getPrimaryCurrency()['code'] => PaymentService::getPrimaryCurrency()))
            : PaymentService::getExchangeRates();

         krsort($rates);
      }

      return $rates;
   }

   /**
    * Get an active shopping cart currency
    *
    * @return string
    */
   public function getActiveShoppingCartCurrency()
   {
      return PaymentService::getShoppingCartCurrency();
   }

   /**
    * Get primary currency
    *
    * @return array
    */
   public function getPrimaryCurrency()
   {
      return PaymentService::getPrimaryCurrency();
   }
}