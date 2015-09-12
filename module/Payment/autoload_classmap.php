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
return [
    'Payment\Type\PaymentCash'                              => __DIR__ . '/src/Payment/Type/PaymentCash.php',
    'Payment\Type\PaymentPayPal'                            => __DIR__ . '/src/Payment/Type/PaymentPayPal.php',
    'Payment\Type\PaymentTypeInterface'                     => __DIR__ . '/src/Payment/Type/PaymentTypeInterface.php',
    'Payment\Type\PaymentRBKMoney'                          => __DIR__ . '/src/Payment/Type/PaymentRBKMoney.php',
    'Payment\Type\PaymentTypeManager'                       => __DIR__ . '/src/Payment/Type/PaymentTypeManager.php',
    'Payment\Type\PaymentAbstractType'                      => __DIR__ . '/src/Payment/Type/PaymentAbstractType.php',
    'Payment\PagePrivacy\PaymentBuyItemsPrivacy'            => __DIR__ . '/src/Payment/PagePrivacy/PaymentBuyItemsPrivacy.php',
    'Payment\Form\PaymentDiscountForm'                      => __DIR__ . '/src/Payment/Form/PaymentDiscountForm.php',
    'Payment\Form\PaymentShoppingCart'                      => __DIR__ . '/src/Payment/Form/PaymentShoppingCart.php',
    'Payment\Form\PaymentExchangeRate'                      => __DIR__ . '/src/Payment/Form/PaymentExchangeRate.php',
    'Payment\Form\PaymentTransactionFilter'                 => __DIR__ . '/src/Payment/Form/PaymentTransactionFilter.php',
    'Payment\Form\PaymentUserTransactionFilter'             => __DIR__ . '/src/Payment/Form/PaymentUserTransactionFilter.php',
    'Payment\Form\PaymentCurrency'                          => __DIR__ . '/src/Payment/Form/PaymentCurrency.php',
    'Payment\Form\PaymentCheckout'                          => __DIR__ . '/src/Payment/Form/PaymentCheckout.php',
    'Payment\Form\PaymentCouponFilter'                      => __DIR__ . '/src/Payment/Form/PaymentCouponFilter.php',
    'Payment\Form\PaymentCoupon'                            => __DIR__ . '/src/Payment/Form/PaymentCoupon.php',
    'Payment\Event\PaymentEvent'                            => __DIR__ . '/src/Payment/Event/PaymentEvent.php',
    'Payment\Service\Payment'                               => __DIR__ . '/src/Payment/Service/Payment.php',
    'Payment\Model\PaymentAdministration'                   => __DIR__ . '/src/Payment/Model/PaymentAdministration.php',
    'Payment\Model\PaymentConsole'                          => __DIR__ . '/src/Payment/Model/PaymentConsole.php',
    'Payment\Model\PaymentBase'                             => __DIR__ . '/src/Payment/Model/PaymentBase.php',
    'Payment\Model\PaymentProcess'                          => __DIR__ . '/src/Payment/Model/PaymentProcess.php',
    'Payment\Model\PaymentWidget'                           => __DIR__ . '/src/Payment/Model/PaymentWidget.php',
    'Payment\Controller\PaymentWidgetController'            => __DIR__ . '/src/Payment/Controller/PaymentWidgetController.php',
    'Payment\Controller\PaymentProcessController'           => __DIR__ . '/src/Payment/Controller/PaymentProcessController.php',
    'Payment\Controller\PaymentAdministrationController'    => __DIR__ . '/src/Payment/Controller/PaymentAdministrationController.php',
    'Payment\Controller\PaymentConsoleController'           => __DIR__ . '/src/Payment/Controller/PaymentConsoleController.php',
    'Payment\Exception\PaymentException'                    => __DIR__ . '/src/Payment/Exception/PaymentException.php',
    'Payment\Handler\PaymentAbstractHandler'                => __DIR__ . '/src/Payment/Handler/PaymentAbstractHandler.php',
    'Payment\Handler\PaymentHandlerManager'                 => __DIR__ . '/src/Payment/Handler/PaymentHandlerManager.php',
    'Payment\Handler\PaymentInterfaceHandler'               => __DIR__ . '/src/Payment/Handler/PaymentInterfaceHandler.php',
    'Payment\View\Widget\PaymentSuccessWidget'              => __DIR__ . '/src/Payment/View/Widget/PaymentSuccessWidget.php',
    'Payment\View\Widget\PaymentTransactionHistoryWidget'   => __DIR__ . '/src/Payment/View/Widget/PaymentTransactionHistoryWidget.php',
    'Payment\View\Widget\PaymentBuyItemsWidget'             => __DIR__ . '/src/Payment/View/Widget/PaymentBuyItemsWidget.php',
    'Payment\View\Widget\PaymentInitShoppingCartInfoWidget' => __DIR__ . '/src/Payment/View/Widget/PaymentInitShoppingCartInfoWidget.php',
    'Payment\View\Widget\PaymentAbstractWidget'             => __DIR__ . '/src/Payment/View/Widget/PaymentAbstractWidget.php',
    'Payment\View\Widget\PaymentCheckoutWidget'             => __DIR__ . '/src/Payment/View/Widget/PaymentCheckoutWidget.php',
    'Payment\View\Widget\PaymentShoppingCartWidget'         => __DIR__ . '/src/Payment/View/Widget/PaymentShoppingCartWidget.php',
    'Payment\View\Widget\PaymentErrorWidget'                => __DIR__ . '/src/Payment/View/Widget/PaymentErrorWidget.php',
    'Payment\View\Widget\PaymentShoppingCartInfoWidget'     => __DIR__ . '/src/Payment/View/Widget/PaymentShoppingCartInfoWidget.php',
    'Payment\View\Helper\PaymentShoppingCart'               => __DIR__ . '/src/Payment/View/Helper/PaymentShoppingCart.php',
    'Payment\View\Helper\PaymentCostFormat'                 => __DIR__ . '/src/Payment/View/Helper/PaymentCostFormat.php',
    'Payment\View\Helper\PaymentCurrency'                   => __DIR__ . '/src/Payment/View/Helper/PaymentCurrency.php',
    'Payment\View\Helper\PaymentProcessCost'                => __DIR__ . '/src/Payment/View/Helper/PaymentProcessCost.php',
    'Payment\View\Helper\PaymentItemLink'                   => __DIR__ . '/src/Payment/View/Helper/PaymentItemLink.php',
    'Payment\Module'                                        => __DIR__ . '/Module.php',
    'Payment\Test\PaymentBootstrap'                         => __DIR__ . '/test/Bootstrap.php'
];
