<?php
namespace Payment\Handler;

use Zend\ServiceManager\ServiceLocatorInterface;
use Payment\Handler\PaymentInterfaceHandler;
use Payment\Exception\PaymentException;

class PaymentHandlerManager
{
    /**
     * List of instances
     * @var array
     */
    private $instances = [];

    /**
     * Service locator
     * @var object
     */
    private $serviceLocator;

    /**
     * Class constructor
     * 
     * @param object $translator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get an object instance
     *
     * @papam string $name
     * @throws Payment\Exception\PaymentException
     * @return Payment\Handler\PaymentInterfaceHandler|boolean
     */
    public function getInstance($name)
    {
        if (!class_exists($name)) {
            return false;
        }

        if (array_key_exists($name, $this->instances)) {
            return $this->instances[$name];
        }

        $handler = new $name($this->serviceLocator);

        if (!$handler instanceof PaymentInterfaceHandler) {
            throw new PaymentException(sprintf('The file "%s" must be an object implementing Payment\Handler\PaymentInterfaceHandler', $name));
        }

        $this->instances[$name] = $handler;
        return $this->instances[$name];
    }
}