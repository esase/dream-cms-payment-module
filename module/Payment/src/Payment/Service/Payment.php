<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Payment\Service;

use Application\Service\ApplicationServiceLocator as ServiceLocator;
use Application\Service\ApplicationSetting as SettingService;
use Zend\Session\Container as SessionContainer;

class Payment
{
    /**
     * Primary currency
     *
     * @var array
     */
    protected static $primaryCurrency;

    /**
     * Exchange rates
     *
     * @var array
     */
    protected static $exchangeRates;

    /**
     * Model instance
     *
     * @var \Payment\Model\PaymentBase
     */
    protected static $model;

    /**
     * Shopping cart items
     *
     * @var array
     */
    protected static $activeShoppingCartItems = null;

    /**
     * Shopping cart items amount
     *
     * @var float
     */
    protected static $activeShoppingCartItemsAmount = null;

    /**
     * Discount coupon info
     *
     * @var array
     */
    protected static $discountCouponInfo = null;

    /**
     * Set a discount coupon ID
     *
     * @param integer $couponId
     * @return void
     */
    public static function setDiscountCouponId($couponId)
    {
        $paymentSession = new SessionContainer('payment');
        $paymentSession->discountCouponId = $couponId;
    }

    /**
     * Get discount coupon info
     *
     * @return array
     */
    public static function getDiscountCouponInfo()
    {
        if (self::$discountCouponInfo === null) {
            // get a session
            $paymentSession = new SessionContainer('payment');

            if (!empty($paymentSession->discountCouponId)) {
                // get a discount coupon info
                if (null != ($discountInfo =
                        self::getModel()->getActiveCouponInfo($paymentSession->discountCouponId, 'id'))) {

                    self::$discountCouponInfo = $discountInfo;

                    return $discountInfo;
                }

                // remove the discount from the session
                $paymentSession->discountCouponId = null;
            }

            self::$discountCouponInfo = [];
        }

        return self::$discountCouponInfo;
    }

    /**
     * Get active shopping cart items amount 
     *
     * @param boolean $discounted
     * @return float
     */
    public static function getActiveShoppingCartItemsAmount($discounted = false)
    {
        if (null === self::$activeShoppingCartItemsAmount) {
            self::initActiveShoppingCartItems();

            // process items amount price
            self::$activeShoppingCartItemsAmount
                    = self::getModel()->getItemsAmount(self::$activeShoppingCartItems);
        }

        return $discounted && self::getDiscountCouponInfo()
            ? self::getModel()->getDiscountedItemsAmount(self::$activeShoppingCartItemsAmount, self::getDiscountCouponInfo()['discount'])
            : self::$activeShoppingCartItemsAmount;
    }

    /**
     * Init active shopping cart items
     *
     * @return void
     */
    protected static function initActiveShoppingCartItems()
    {
        if (self::$activeShoppingCartItems === null) {
            self::$activeShoppingCartItems = self::getModel()->getAllShoppingCartItems();
        }
    }

    /**
     * Get active shopping cart items
     *
     * @return array
     */
    public static function getActiveShoppingCartItems()
    {
        self::initActiveShoppingCartItems();

        return self::$activeShoppingCartItems;
    }

    /**
     * Get model
     * 
     * @return \Payment\Model\PaymentBase
     */
    protected static function getModel()
    {
        if (!self::$model) {
            self::$model = ServiceLocator::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\PaymentBase');
        }

        return self::$model;
    }

    /**
     * Init exchange rates
     *
     * @return void
     */
    protected static function initExchangeRates()
    {
        // process all rates
        foreach (self::getModel()->getExchangeRates(false) as $currency => $currencyInfo) {
            // get primary currency
            if ($currencyInfo['primary_currency']) {
                self::$primaryCurrency = [
                    'id'   => $currencyInfo['id'],
                    'name' => $currencyInfo['name'],
                    'code' => $currencyInfo['code']
                ];

                continue;
            }

            if (!$currencyInfo['rate']) {
                continue;
            }

            self::$exchangeRates[$currency] = [
                'id'   => $currencyInfo['id'],
                'rate' => $currencyInfo['rate'],
                'name' => $currencyInfo['name'],
                'code' => $currencyInfo['code']
            ];
        }
    }

    /**
     * Get exchange rates
     *
     * @return array
     */
    public static function getExchangeRates()
    {
        if (!self::$primaryCurrency) {
            self::initExchangeRates();
        }

        return self::$exchangeRates;
    }

    /**
     * Get primary currency
     *
     * @return array
     */
    public static function getPrimaryCurrency()
    {
        if (!self::$primaryCurrency) {
            self::initExchangeRates();
        }

        return self::$primaryCurrency;
    }

    /**
     * Rounding a cost
     *
     * @param float|integer $cost
     * @return integer|float
     */
    public static function roundingCost($cost)
    {
        switch (SettingService::getSetting('payment_type_rounding')) {
            case 'type_round' :
                return round($cost);

            case 'type_ceil' :
                return ceil($cost);

            case 'type_floor' :
                return floor($cost);

            default :
                return $cost;
        }
    }

    /**
     * Get shopping cart currency
     *
     * @return string
     */
    public static function getShoppingCartCurrency()
    {
        if (!self::$primaryCurrency) {
            self::initExchangeRates();
        }

        $shoppingCartCurrency = self::getModel()->getShoppingCartCurrency();

        if (!$shoppingCartCurrency) {
            return self::$primaryCurrency['code'];
        }

        return self::$exchangeRates && array_key_exists($shoppingCartCurrency, self::$exchangeRates)
            ? self::$exchangeRates[$shoppingCartCurrency]['code']
            : self::$primaryCurrency['code'];         
    }
}