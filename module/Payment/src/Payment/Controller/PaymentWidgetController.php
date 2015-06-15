<?php

namespace Payment\Controller;

use User\Service\UserIdentity as UserIdentityService;
use Payment\Event\PaymentEvent;
use Payment\Service\Payment as PaymentService;
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
     * View an item extra's options
     */
    public function ajaxViewItemExtraOptionsAction()
    {
        $shoppingCart = $this->params()->fromQuery('shopping_cart', false);
        $id = $this->params()->fromQuery('id', -1);
        $userId = UserIdentityService::getCurrentUserIdentity()['user_id'];

        $extraOptions = !$shoppingCart
            ? $this->getModel()->getTransactionItemExtraOptions($id, $userId)
            : $this->getModel()->getShoppingCartItemExtraOptions($id);

        if (!$extraOptions) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'extra_options' => $extraOptions
        ]);
    }

    /**
     * View transaction items
     */
    public function ajaxViewTransactionItemsAction()
    {
        $transasctionId = $this->params()->fromQuery('id', -1);
        $userId = UserIdentityService::getCurrentUserIdentity()['user_id'];

        // get transaction items list
        if (null == ($items = $this->
                getModel()->getAllTransactionItems($transasctionId, $userId, true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'items' => $items
        ]);
    }

    /**
     * Edit shopping cart's item
     */
    public function ajaxEditShoppingCartItemAction()
    {
        $itemId = $this->getRequest()->getQuery('id', -1);

        // get an item's info
        if (null == ($itemInfo = $this->
                getModel()->getShoppingCartItemInfo($itemId, true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the payment handler
        $paymentHandler = $this->getServiceLocator()
            ->get('Payment\Handler\PaymentHandlerManager')
            ->getInstance($itemInfo['handler']);

        // get the item's additional info
        $extraItemInfo = $paymentHandler->getItemInfo($itemInfo['object_id']);

        // extra checks
        if ($itemInfo['countable'] == PaymentBaseModel::MODULE_COUNTABLE
                || $itemInfo['multi_costs'] == PaymentBaseModel::MODULE_MULTI_COSTS
                || ($itemInfo['module_extra_options'] == PaymentBaseModel::MODULE_EXTRA_OPTIONS && !empty($extraItemInfo['extra_options']))
                || (float) $itemInfo['discount']
                || $paymentHandler->getDiscount($itemInfo['object_id'])) {

            $refreshPage = false;

            // get a form instance
            $shoppingCartForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('Payment\Form\PaymentShoppingCart')
                ->hideCountField($itemInfo['countable'] != PaymentBaseModel::MODULE_COUNTABLE)
                ->setDiscount(((float) $itemInfo['discount'] ? (float) $itemInfo['discount'] : (float) $extraItemInfo['discount']))
                ->setCountLimit((PaymentBaseModel::MODULE_COUNTABLE == $itemInfo['countable'] ? $extraItemInfo['count'] : 0));

            if (PaymentBaseModel::MODULE_MULTI_COSTS == $itemInfo['multi_costs']) {
                $shoppingCartForm->setTariffs($extraItemInfo['cost']);
            }

            // fill the form with default values
            $defaultFormValues = array_merge($itemInfo, [
                'discount' => (float) $itemInfo['discount'] ? 1 : 0
            ]);

            // add extra options in the form
            if ($itemInfo['module_extra_options'] ==
                        PaymentBaseModel::MODULE_EXTRA_OPTIONS && !empty($extraItemInfo['extra_options'])) {

                $shoppingCartForm->setExtraOptions($extraItemInfo['extra_options']);

                // fill a default value
                if ($itemInfo['extra_options']) {
                    $defaultFormValues = array_merge($defaultFormValues, unserialize($itemInfo['extra_options']));
                }
            }

            $shoppingCartForm->getForm()->setData($defaultFormValues);

            $request = $this->getRequest();
            $shoppingCartForm->getForm()->setData($request->getPost(), false);

            // validate the form
            if ($request->isPost()) {
                if ($shoppingCartForm->getForm()->isValid()) {
                    // get the form's data
                    $formData = $shoppingCartForm->getForm()->getData();

                    // get the item's extra options
                    $extraOptions = $shoppingCartForm->getExtraOptions($formData);

                    $newItemInfo = array(
                        'cost' => !empty($formData['cost']) ? $formData['cost'] : $itemInfo['cost'],
                        'count' => PaymentBaseModel::MODULE_COUNTABLE == $itemInfo['countable'] ? $formData['count'] : 1,
                        'discount'  => !empty($formData['discount'])
                            ? ((float) $itemInfo['discount'] ? (float) $itemInfo['discount'] : (float) $extraItemInfo['discount'])
                            : 0,
                        'extra_options' => $extraOptions ? serialize($extraOptions) : null
                    );

                    // update the item into the shopping cart
                    if (true === ($result = $this->
                            getModel()->updateShoppingCartItem($itemInfo['id'], $newItemInfo))) {

                        $refreshPage = true;

                        // return a discount back
                        if ((float) $itemInfo['discount'] && empty($formData['discount'])) {
                            // get the payment handler
                            $this->getServiceLocator()
                                ->get('Payment\Handler\PaymentHandlerManager')
                                ->getInstance($itemInfo['handler'])
                                ->returnBackDiscount($itemInfo['object_id'], (float) $itemInfo['discount']);
                        }

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Item has been edited'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Error occurred'));
                    }
                }
            }

            $view = new ViewModel([
                'refresh_page' => $refreshPage,
                'id' => $itemInfo['id'],
                'shopping_cart_form' => $shoppingCartForm->getForm(),
            ]);

            return $view;
        }
        else {
            return $this->createHttpNotFoundModel($this->getResponse());
        }
    }

    /**
     * Deactivate current discount coupon
     */
    public function ajaxDeactivateDiscountCouponAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null != ($discountCouponInfo = PaymentService::getDiscountCouponInfo())) {
                PaymentService::setDiscountCouponId(null);

                // fire the deactivate discount coupon event
                PaymentEvent::fireDeactivateDiscountCouponEvent($discountCouponInfo['slug']);

                $this->flashMessenger()
                    ->setNamespace('success')
                    ->addMessage($this->getTranslator()->translate('The coupon code has been deactivated'));
            }
        }

        return $this->getResponse();
    }

    /**
     * Activate a discount coupon
     */
    public function ajaxActivateDiscountCouponAction()
    {
        $refreshPage = false;

        $discountForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentDiscountForm')
            ->setModel($this->getModel());

        $request = $this->getRequest();

        if ($request->isPost()) {
            $discountForm->getForm()->setData($request->getPost(), false);

            if ($discountForm->getForm()->isValid()) {
                // activate a discount coupon
                $couponCode = $discountForm->getForm()->getData()['coupon'];

                // save the activated discount coupon's ID in sessions
                PaymentService::setDiscountCouponId($this->
                        getModel()->getCouponInfo($couponCode, 'slug')['id']);

                // fire the activate discount coupon event
                PaymentEvent::fireActivateDiscountCouponEvent($couponCode);

                $this->flashMessenger()
                    ->setNamespace('success')
                    ->addMessage($this->getTranslator()->translate('The coupon code has been activated'));

                $refreshPage = true;
            }
        }

        $view = new ViewModel([
            'discount_form' => $discountForm->getForm(),
            'refresh_page' => $refreshPage
        ]);

        return $view;
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
                            || (PaymentBaseModel::MODULE_EXTRA_OPTIONS == $moduleInfo['extra_options'] && !empty($objectInfo['extra_options']))
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

                        if (PaymentBaseModel::MODULE_EXTRA_OPTIONS ==
                                    $moduleInfo['extra_options'] && !empty($objectInfo['extra_options'])) {

                            $shoppingCartForm->setExtraOptions($objectInfo['extra_options']);
                        }

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

                                // get the item's extra options
                                $extraOptions = $shoppingCartForm->getExtraOptions($formData);

                                $itemInfo = [
                                    'object_id'     => $objectId,
                                    'module'        => $moduleInfo['id'],
                                    'title'         => $objectInfo['title'],
                                    'slug'          => $objectInfo['slug'],
                                    'cost'          => !empty($formData['cost']) ? $formData['cost'] : $objectInfo['cost'],
                                    'discount'      => !empty($formData['discount']) ? (float) $objectInfo['discount'] : 0,
                                    'count'         => PaymentBaseModel::MODULE_COUNTABLE == $moduleInfo['countable'] ? $count : 1,
                                    'extra_options' => $extraOptions ? serialize($extraOptions) : null,
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
                            'extra_options' => null
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