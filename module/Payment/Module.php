<?php

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
     * @var object
     */
    public $serviceManager;

    /**
     * Init
     *
     * @param Zend\ModuleManager\ModuleManagerInterface $moduleManager
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
     * @param Zend\EventManager\EventInterface $e 
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
                $eventManager->attach($module->update_event, function ($e) 
                        use ($model, $paymentHandler, $module) {

                    $model->updateItemGlobally($e->getParam('object_id'), $paymentHandler, $module);
                });

                // delete items
                $eventManager->attach($module->delete_event, function ($e) 
                        use ($model, $module) {

                    $model->deleteItemGlobally($e->getParam('object_id'), $module->module);
                });
            }
        }
    }

    /**
     * Return autoloader config array
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
                },
            ]
        ];
    }

    /**
     * Init view helpers
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
     * @return boolean
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get console usage info
     *
     * @param Zend\Console\Adapter\AdapterInterface $console
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