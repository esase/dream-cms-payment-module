<?php

namespace Payment\Handler;

use Zend\ServiceManager\ServiceLocatorInterface;

abstract class PaymentAbstractHandler implements PaymentInterfaceHandler
{
    /**
     * Service locator
     * @var object
     */
    protected $serviceLocator;

    /**
     * Class constructor
     *
     * @param object $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}