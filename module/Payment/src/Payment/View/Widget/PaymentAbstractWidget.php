<?php

namespace Payment\View\Widget;

use Page\View\Widget\PageAbstractWidget;

abstract class PaymentAbstractWidget extends PageAbstractWidget
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\PaymentWidget');
        }

        return $this->model;
    }
}