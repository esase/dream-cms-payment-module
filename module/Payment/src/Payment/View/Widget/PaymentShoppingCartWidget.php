<?php
namespace Payment\View\Widget;

use Page\View\Widget\PageAbstractWidget;

class PaymentShoppingCartWidget extends PageAbstractWidget
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

   /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {       
        // get pagination params
        $page = $this->getRouteParam('page', 1);
        $perPage = $this->getRouteParam('per_page');
        $orderBy = $this->getRouteParam('order_by', 'created');
        $orderType = $this->getRouteParam('order_type', 'desc');

        // get data
        $paginator = $this->getModel()->getShoppingCartItems($page, $perPage, $orderBy, $orderType);

        return $this->getView()->partial('payment/widget/shopping-cart', [
            'paginator' => $paginator,
            'order_by' => $orderBy,
            'order_type' => $orderType,
            'per_page' => $perPage,
            'paymentHandlerManager' => $this->
                    getServiceLocator()->get('Payment\Handler\PaymentHandlerManager')
        ]);
    }
}