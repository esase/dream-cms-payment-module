<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Payment\View\Widget;

use Payment\Service\Payment as PaymentService;
use User\Service\UserIdentity as UserIdentityService;
use Payment\Model\PaymentBase as PaymentBaseModel;

class PaymentCheckoutWidget extends PaymentAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        // get list of shopping cart's items
        $shoppingCartItems = PaymentService::getActiveShoppingCartItems();

        if (!count($shoppingCartItems)) {
            return $this->getView()->partial('payment/widget/checkout-message', [
                'message' => $this->translate('Shopping cart is empty')
            ]);
        }

        // check additional params
        if (UserIdentityService::isGuest()) {
            foreach ($shoppingCartItems as $item) {
                if ($item['must_login'] == PaymentBaseModel::MODULE_MUST_LOGIN) {
                    $this->getFlashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->
                                translate('Some of the items in your shopping cart requires you to be logged in'));

                    // get the login page url
                    $loginPageUrl = $this->getView()->pageUrl('login');

                    if (false !== $loginPageUrl) {
                        return $this->redirectTo(['page_name' => $loginPageUrl], false, [
                            'back_url' => $this->getView()->
                                    url('page', ['page_name' => $this->getView()->pageUrl('checkout')], ['force_canonical' => true])
                        ]);
                    }

                    // redirect to home page
                    return $this->redirectTo(['page_name' => $this->getView()->pageUrl('home')]);
                }
            }
        }

        // get shopping cart items amount
        $amount = (float) paymentService::
                roundingCost(paymentService::getActiveShoppingCartItemsAmount(true));

        $transactionPayable = $amount > 0;

        // get payments types
        if (null == ($paymentsTypes =
                $this->getModel()->getPaymentsTypes()) && $transactionPayable) {

            return $this->getView()->partial('payment/widget/checkout-message', [
                'message' => $this->translate('No available payment types. Please try again later')
            ]);
        }

        // get a form instance
        $checkoutForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentCheckout')
            ->setPaymentsTypes($paymentsTypes)
            ->hidePaymentType(!$transactionPayable);
    
        // set default values
        if (!UserIdentityService::isGuest()) {
            $checkoutForm->getForm()->setData([
                'first_name'    => UserIdentityService::getCurrentUserIdentity()['first_name'],
                'last_name'     => UserIdentityService::getCurrentUserIdentity()['last_name'],
                'email'         => UserIdentityService::getCurrentUserIdentity()['email'],
                'phone'         => UserIdentityService::getCurrentUserIdentity()['phone'],
                'address'       => UserIdentityService::getCurrentUserIdentity()['address'],
            ], false);
        }

        // validate the form
        if ($this->getRequest()->isPost() &&
                $this->getRequest()->getPost('form_name') == $checkoutForm->getFormName()) {

            // fill form with received values
            $checkoutForm->getForm()->setData($this->getRequest()->getPost());

            if ($checkoutForm->getForm()->isValid()) {
                $formData = $checkoutForm->getForm()->getData();
                $userId   = UserIdentityService::getCurrentUserIdentity()['user_id'];

                // add a new transaction
                $result = $this->getModel()->addTransaction($userId, $formData, $shoppingCartItems);

                if (is_numeric($result)) {
                    // clear the shopping cart items
                    if (null != ($items = $this->getModel()->getAllShoppingCartItems(false))) {
                        // delete all items
                        foreach ($items as $itemInfo) {
                            $this->getModel()->deleteFromShoppingCart($itemInfo['id']);
                        }
                    }

                    // get created transaction info
                    $transactionInfo = $this->getModel()->getTransactionInfo($result);

                    // redirect to the buying page
                    if ($transactionPayable) {
                        $buyItemsPageUrl = $this->getView()->pageUrl('buy-items', [], null, true);

                        if (false !== $buyItemsPageUrl) {
                            return $this->redirectTo([
                                'page_name' => $buyItemsPageUrl, 
                                'slug' => $transactionInfo['slug']
                            ], false, ['payment_name' => $transactionInfo['payment_name']]);
                        }

                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->translate('Sorry you cannot see the buy items page'));
                    }
                    else {
                        // activate the transaction and redirect to the success page
                        if (true === ($result = 
                                $this->getModel()->activateTransaction($transactionInfo))) {

                            $successPageUrl = $this->getView()->pageUrl('successful-payment');

                            if (false !== $successPageUrl) {
                                return $this->redirectTo(['page_name' => $successPageUrl]);
                            }

                            $this->getFlashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->translate('Sorry you cannot see the payment success page'));
                        }
                        else {
                            $this->getFlashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->translate('Transaction activation error'));
                        }
                    }
                }
                else {
                    $this->getFlashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->translate('Error occurred'));
                }

                return $this->reloadPage();
            }
        }

        return $this->getView()->partial('payment/widget/checkout', [
            'checkout_form' => $checkoutForm->getForm()
        ]);
    }
}