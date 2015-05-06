<?php

namespace Payment\Controller;

use Payment\Handler\PaymentInterfaceHandler;
use Application\Controller\ApplicationAbstractBaseController;
use Payment\Model\PaymentBase as PaymentBaseModel;
use Zend\View\Model\ViewModel;

class PaymentWidgetController extends ApplicationAbstractBaseController
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
     * Add to shopping cart
     *
     * @param array $itemInfo
     *      integer object_id - required
     *      integer module - required
     *      string title - required
     *      string|integer slug - optional
     *      float cost - required
     *      float discount - optional
     *      integer count - required
     * @param Payment\Handler\PaymentInterfaceHandler $paymentHandler
     * @return boolean
     */
    protected function addToShoppingCart($itemInfo, PaymentInterfaceHandler $paymentHandler)
    {
        // check an item existing in shopping cart
        if (null != ($itemId = $this->
                getModel()->inShoppingCart($itemInfo['object_id'], $itemInfo['module']))) {

            // delete old item
            $this->getModel()->deleteFromShoppingCart($itemId);
        }

        $result = $this->getModel()->addToShoppingCart($itemInfo);

        if (is_numeric($result)) {
            // clear the item's discount
            if ((float) $itemInfo['discount']) {
                $paymentHandler->clearDiscount($itemInfo['object_id'], (float)$itemInfo['discount']);
            }

            return true;
        }

        return false;
    }

    /**
     * Clean shopping cart
     *
     * @param boolean $returnDiscount
     * @return boolean
     */
    protected function cleanShoppingCart($returnDiscount = true)
    {
        // get all shopping cart items
        if (null != ($items = $this->getModel()->getAllShoppingCartItems(false))) {
            // delete all items
            foreach ($items as $itemInfo) {
                if (true !== ($deleteResult =
                        $this->getModel()->deleteFromShoppingCart($itemInfo['id']))) {

                    return false;
                }

                // return a discount back
                if ($returnDiscount && (float) $itemInfo['discount']) {
                    // get the payment handler
                    $this->getServiceLocator()
                        ->get('Payment\Handler\PaymentHandlerManager')
                        ->getInstance($itemInfo['handler'])
                        ->returnBackDiscount($itemInfo['id'], (float) $itemInfo['discount']);
                }
            }
        }

        return true;
    }

    /**
     * Change currency
     */
    public function ajaxChangeCurrencyAction()
    {
        $this->getModel()->setShoppingCartCurrency($this->params()->fromPost('currency'));
        return $this->getResponse();
    }

    /**
     * Clean shopping cart
     */
    public function ajaxCleanShoppingCartAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->cleanShoppingCart();
        }

        return new ViewModel([]);
    }

    /**
     * Update shopping cart
     */
    public function ajaxUpdateShoppingCartAction()
    {
        return new ViewModel([]);
    }

    /**
     * Add to shopping cart
     */
    public function ajaxAddToShoppingCartAction()
    {
        $objectId = $this->params()->fromPost('object_id', -1);
        $module   = $this->params()->fromPost('module');
        $count    = (int) $this->params()->fromPost('count', 0);

        $shoppingCartForm  = $message = null;
        $updateShopingCart = false;

        // get a payment module info 
        if (null == ($moduleInfo = $this->getModel()->getPaymentModuleInfo($module))) {
            $message = sprintf($this->
                getTranslator()->translate('Received module not found'), $module);
        }
        else {
            // get the payment handler
            $paymentHandler = $this->getServiceLocator()
                ->get('Payment\Handler\PaymentHandlerManager')
                ->getInstance($moduleInfo['handler']);

            // get the item info
            if (null == $objectInfo = $paymentHandler->getItemInfo($objectId)) {
                $message = $this->getTranslator()->
                        translate('Sorry but the item not found or not activated');    
            }
            else {
                // count is not available
                if (PaymentBaseModel::MODULE_COUNTABLE ==
                            $moduleInfo['countable'] && $objectInfo['count'] <= 0) {

                    $message = $this->getTranslator()->translate('Item is not available');
                }
                else {
                    // show an additional shopping cart form
                    if ((float) $objectInfo['discount']
                            || PaymentBaseModel::MODULE_MULTI_COSTS == $moduleInfo['multi_costs']
                            || (PaymentBaseModel::MODULE_COUNTABLE == $moduleInfo['countable'] &&
                                    ($count <= 0 || $count > $objectInfo['count'])))
                    {
                        // get the form instance
                        $shoppingCartForm = $this->getServiceLocator()
                            ->get('Application\Form\FormManager')
                            ->getInstance('Payment\Form\PaymentShoppingCart')
                            ->hideCountField($moduleInfo['countable'] != PaymentBaseModel::MODULE_COUNTABLE)
                            ->setDiscount((float) $objectInfo['discount'])
                            ->setCountLimit((PaymentBaseModel::MODULE_COUNTABLE == $moduleInfo['countable'] ? $objectInfo['count'] : 0));

                        if (PaymentBaseModel::MODULE_MULTI_COSTS == $moduleInfo['multi_costs']) {
                            $shoppingCartForm->setTariffs($objectInfo['cost']);
                        }

                        // process the post request
                        $request = $this->getRequest();
                        $shoppingCartForm->getForm()->setData($request->getPost(), false);

                        // validate the form
                        if ($request->isPost() && null !== $this->params()->fromPost('validate', null)) {
                            if ($shoppingCartForm->getForm()->isValid()) {
                                $formData = $shoppingCartForm->getForm()->getData();

                                $itemInfo = [
                                    'object_id'     => $objectId,
                                    'module'        => $moduleInfo['id'],
                                    'title'         => $objectInfo['title'],
                                    'slug'          => $objectInfo['slug'],
                                    'cost'          => !empty($formData['cost']) ? $formData['cost'] : $objectInfo['cost'],
                                    'discount'      => !empty($formData['discount']) ? (float) $objectInfo['discount'] : 0,
                                    'count'         => PaymentBaseModel::MODULE_COUNTABLE == $moduleInfo['countable'] ? $count : 1
                                ];

                                // add the item into the shopping cart
                                $shoppingCartForm = null;
                                if (true === ($result = $this->addToShoppingCart($itemInfo, $paymentHandler))) {
                                    $updateShopingCart = true;
                                    $message = $this->getTranslator()->translate('Item has been added to your shopping cart');
                                }
                                else {
                                    $message = $this->getTranslator()->translate('Error occurred');
                                }
                            }
                        }
                    }
                    else {
                        $itemInfo = [
                            'object_id'     => $objectId,
                            'module'        => $moduleInfo['id'],
                            'title'         => $objectInfo['title'],
                            'slug'          => $objectInfo['slug'],
                            'cost'          => $objectInfo['cost'],
                            'discount'      => 0,
                            'count'         => PaymentBaseModel::MODULE_COUNTABLE == $moduleInfo['countable'] ? $count : 1,
                        ];

                        if (true === ($result = $this->addToShoppingCart($itemInfo, $paymentHandler))) {
                            $updateShopingCart = true;
                            $message = $this->getTranslator()->translate('Item has been added to your shopping cart');
                        }
                        else {
                            $message = $this->getTranslator()->translate('Error occurred');
                        }   
                    }                        
                }
            }
        }

        $view = new ViewModel([
            'update_shoping_cart' => $updateShopingCart,
            'shoppingcart_form' => $shoppingCartForm ? $shoppingCartForm->getForm() : null,
            'message' => $message
        ]);

        return $view;
    }
}