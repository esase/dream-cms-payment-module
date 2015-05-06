<?php
namespace Payment;

use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

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
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceManager = $moduleManager->getEvent()->getParam('ServiceManager');
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
                'paymentShoppingCartWidget' => 'Payment\View\Widget\PaymentShoppingCartWidget',
                'paymentInitShoppingCartInfoWidget' => 'Payment\View\Widget\PaymentInitShoppingCartInfoWidget',
                'paymentShoppingCartInfoWidget' => 'Payment\View\Widget\PaymentShoppingCartInfoWidget',
                'paymentCostFormat' => 'Payment\View\Helper\PaymentCostFormat',
                'paymentItemStatus' => 'Payment\View\Helper\PaymentItemStatus',
                'paymentCurrency' => 'Payment\View\Helper\PaymentCurrency',
                'paymentShoppingCart' => 'Payment\View\Helper\PaymentShoppingCart',
                'paymentProcessCost' => 'Payment\View\Helper\PaymentProcessCost'
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
     * @param object $console
     * @return array
     */
    public function getConsoleUsage(Console $console)
    {}
}