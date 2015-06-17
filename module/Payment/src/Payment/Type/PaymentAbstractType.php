<?php

namespace Payment\Type;

use Application\Service\ApplicationServiceLocator;
use Payment\Model\PaymentBase as PaymentBaseModel;

abstract class PaymentAbstractType implements PaymentTypeInterface
{
    /**
     * Model
     * @var Payment\Model\PaymentBase
     */
    protected $model;

    /**
     * Request
     * @var Zend\Stdlib\RequestInterface
     */
    protected $request;

    /**
     * Class constructor
     *
     * @param Payment\Model\PaymentBase $model
     */
    public function __construct(PaymentBaseModel $model)
    {
        $this->model = $model;
        $this->request = $this->getServiceLocator()->get('Request');
    }

    /**
     * Get service locator
     *
     * @return object
     */
    protected function getServiceLocator()
    {
        return ApplicationServiceLocator::getServiceLocator();
    }

    /**
     * Get success url
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        $pageName = $this->getServiceLocator()->
                get('viewHelperManager')->get('pageUrl')->__invoke('successful-payment');

        return $this->getServiceLocator()->get('viewHelperManager')->
                get('url')-> __invoke('page', ['page_name' => $pageName], ['force_canonical' => true]);
    }

    /**
     * Get error url
     *
     * @return string
     */
    public function getErrorUrl()
    {
        $pageName = $this->getServiceLocator()->
                get('viewHelperManager')->get('pageUrl')->__invoke('failed-payment');

        return $this->getServiceLocator()->get('viewHelperManager')->
                get('url')-> __invoke('page', ['page_name' => $pageName], ['force_canonical' => true]);
    }

    /**
     * Get notify url
     *
     * @param string $paymentName
     * @return string
     */
    public function getNotifyUrl($paymentName)
    {
        return $this->getServiceLocator()->get('viewHelperManager')->get('url')->
                __invoke('application/page', ['controller' => 'payments', 'action' => 'process', 'slug' => $paymentName], ['force_canonical' => true]);
    }
}