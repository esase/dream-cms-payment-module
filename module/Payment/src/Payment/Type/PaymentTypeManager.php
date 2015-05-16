<?php

namespace Payment\Type;

use Payment\Model\PaymentBase as PaymentBaseModel;
use Payment\Type\PaymentTypeInterface;
use Payment\Exception\PaymentException;

class PaymentTypeManager
{
    /**
     * List of instances
     * @var array
     */
    private $instances = [];

    /**
     * Model
     * @var Payment\Model\PaymentBase
     */
    private $model;

    /**
     * Class constructor
     * 
     * @param Payment\Model\PaymentBase $model
     */
    public function __construct(PaymentBaseModel $model)
    {
        $this->model = $model;
    }

    /**
     * Get an object instance
     *
     * @papam string $name
     * @throws Payment\Exception\PaymentException
     * @return Payment\Type\PaymentTypeInterface
     */
    public function getInstance($name)
    {
        if (!class_exists($name)) {
            throw new PaymentException(sprintf('The class name "%s" cannot be found', $name));
        }

        if (array_key_exists($name, $this->instances)) {
            return $this->instances[$name];
        }

        $paymentType = new $name($this->model);

        if (!$paymentType instanceof PaymentTypeInterface) {
            throw new PaymentException(sprintf('The file "%s" must be an object implementing Payment\Type\PaymentTypeInterface', $name));
        }

        $this->instances[$name] = $paymentType;
        return $this->instances[$name];
    }
}
