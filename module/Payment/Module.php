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
namespace Payment;

use Payment\Event\PaymentEvent;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\EventManager\EventInterface;

class Module implements ConsoleUsageProviderInterface
{
    /**
     * Service manager
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    public $serviceManager;

    /**
     * Init
     *
     * @param \Zend\ModuleManager\ModuleManagerInterface $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceManager = $moduleManager->getEvent()->getParam('ServiceManager');

        $moduleManager->getEventManager()->
                attach(ModuleEvent::EVENT_LOAD_MODULES_POST, array($this, 'initPaymentListeners'));
    }

    /**
     * Init payment listeners
     *
     * @param \Zend\EventManager\EventInterface $e
     */
    public function initPaymentListeners(EventInterface $e)
    {
        $model = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Payment\Model\PaymentBase');

        // update a user transactions info
        $eventManager = PaymentEvent::getEventManager();

        if ($model->getModuleInfo('Payment')) {
            // init edit and update events for payment modules
            foreach ($model->getPaymentModules() as $module) {
                // get the payment handler
                $paymentHandler = $this->serviceManager
                    ->get('Payment\Handler\PaymentHandlerManager')
                    ->getInstance($module->handler);

                // update items
                $eventManager->attach($module->update_event,
                        function ($e) use ($model, $paymentHandler, $module) {

                    $model->updateItemGlobally($e->getParam('object_id'), $paymentHandler, $module);
                });

                // delete items
                $eventManager->attach($module->delete_event,
                        function ($e) use ($model, $module) {

                    $model->deleteItemGlobally($e->getParam('object_id'), $module->module);
                });
            }
        }
    }

    /**
     * Return auto loader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php'
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
                ]
            ]
        ];
    }

    /**
     * Return service config array
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'Payment\Type\PaymentTypeManager' => function($serviceManager)
                {
                    $basePaymentModel = $serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Payment\Model\PaymentBase');

                    return new Type\PaymentTypeManager($basePaymentModel);
                },
                'Payment\Handler\PaymentHandlerManager' => function($serviceManager)
                {
                    return new Handler\PaymentHandlerManager($serviceManager);
                }
            ]
        ];
    }

    /**
     * Init view helpers
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return [
            'invokables' => [
                'paymentTransactionHistoryWidget' => 'Payment\View\Widget\PaymentTransactionHistoryWidget',
                'paymentBuyItemsWidget' => 'Payment\View\Widget\PaymentBuyItemsWidget',
                'paymentErrorWidget' => 'Payment\View\Widget\PaymentErrorWidget',
                'paymentSuccessWidget' => 'Payment\View\Widget\PaymentSuccessWidget',
                'paymentCheckoutWidget' => 'Payment\View\Widget\PaymentCheckoutWidget',
                'paymentShoppingCartWidget' => 'Payment\View\Widget\PaymentShoppingCartWidget',
                'paymentInitShoppingCartInfoWidget' => 'Payment\View\Widget\PaymentInitShoppingCartInfoWidget',
                'paymentShoppingCartInfoWidget' => 'Payment\View\Widget\PaymentShoppingCartInfoWidget',
                'paymentCostFormat' => 'Payment\View\Helper\PaymentCostFormat',
                'paymentItemStatus' => 'Payment\View\Helper\PaymentItemStatus',
                'paymentCurrency' => 'Payment\View\Helper\PaymentCurrency',
                'paymentShoppingCart' => 'Payment\View\Helper\PaymentShoppingCart',
                'paymentProcessCost' => 'Payment\View\Helper\PaymentProcessCost',
                'paymentItemLink' => 'Payment\View\Helper\PaymentItemLink'
            ],
            'factories' => [
            ]
        ];
    }

    /**
     * Return path to config file
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get console usage info
     *
     * @param \Zend\Console\Adapter\AdapterInterface $console
     * @return array
     */
    public function getConsoleUsage(Console $console)
    {
        return [
            // describe available commands
            'payment clean expired items [--verbose|-v]' => 'Clean expired shopping cart and items and expired not paid transactions',
            // describe expected parameters
            ['--verbose|-v', '(optional) turn on verbose mode']
        ];
    }
}