<?php
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
                $result = $this->getModel()->addTransaction($userId, $formData, $shoppingCartItems, $amount);

                if (!is_numeric($result)) {
                    // get created transaction info
                    $transactionInfo = $this->getModel()->getTransactionInfo($result);

                    // redirect to the buying page
                    if ($transactionPayable) {
                        // TODO: Pay page ???
                        return $this->redirectTo('payments', 'buy', array(
                            'slug'  => $transactionInfo['slug'],
                            'extra' => $transactionInfo['payment_name']
                        ));
                    }
                    else {
                        // activate the transaction and redirect to success page
                        if (true == ($result = $this->activateTransaction($transactionInfo))) {
                            // SUCCESS PAGE???
                            return $this->redirectTo('payments', 'success');
                        }

                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Transaction activation error'));

                        return $this->redirectTo('payments', 'shopping-cart');
                    }
                }
                else {
                    $this->getFlashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->translate('Error occurred'));

                    $this->reloadPage();    
                }
            }
        }

        return $this->getView()->partial('payment/widget/checkout', [
            'checkout_form' => $checkoutForm->getForm()
        ]);
    }
}