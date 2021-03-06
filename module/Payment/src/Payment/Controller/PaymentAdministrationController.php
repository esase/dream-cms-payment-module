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
namespace Payment\Controller;

use Application\Controller\ApplicationAbstractAdministrationController;
use Payment\Model\PaymentBase as PaymentBaseModel;
use Zend\View\Model\ViewModel;

class PaymentAdministrationController extends ApplicationAbstractAdministrationController
{
    /**
     * Model instance
     *
     * @var \Payment\Model\PaymentAdministration
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Payment\Model\PaymentAdministration
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\PaymentAdministration');
        }

        return $this->model;
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to the list action
        return $this->redirectTo('payments-administration', 'list');
    }

    /**
     * View transaction's items
     */
    public function viewTransactionItemsAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get the transaction info
        if (null == ($transactionInfo = $this->getModel()->getTransactionInfo($this->
                getSlug(), false, 'id', false, 0, false))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get data
        $paginator = $this->getModel()->getTransactionItems($transactionInfo['id'],
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'transaction' => $transactionInfo,
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Transactions list 
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentTransactionFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getTransactionsList($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * View transaction's details
     */
    public function viewTransactionDetailsAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get the transaction info
        if (null == ($transactionInfo = $this->getModel()->getTransactionInfo($this->
                getSlug(), false, 'id', false, 0, false))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'transaction' => $transactionInfo
        ]);
    }

    /**
     * Delete selected transactions
     */
    public function deleteTransactionsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($transactionsIds = $request->getPost('transactions', null))) {
                // delete selected transactions
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($transactionsIds as $transactionId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the transaction
                    if (true !== ($deleteResult = $this->getModel()->deleteTransaction($transactionId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    $deletedCount++;
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected transactions have been deleted'
                        : 'The selected transaction has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('payments-administration', 'list', [], true);
    }

    /**
     * Activate selected transactions
     */
    public function activateTransactionsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($transactionsIds = $request->getPost('transactions', null))) {
                // process transactions
                $activationResult = true;
                $activationCount  = 0;

                foreach ($transactionsIds as $transactionId) {
                    // get the transaction info
                    if (null == ($transactionInfo = $this->getModel()->getTransactionInfo($transactionId, false, 'id', false, 0, false))
                                || PaymentBaseModel::TRANSACTION_PAID == $transactionInfo['paid']) {

                        $activationCount++;
                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // activate the transaction
                    if (true !== ($activationResult = $this->getModel()->activateTransaction($transactionInfo))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Transaction activation error'));

                        break;
                    }

                    $activationCount++;
                }

                if (true === $activationResult) {
                    $message = $activationCount > 1
                        ? 'Selected transactions have been activated'
                        : 'The selected transaction has been activated';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('payments-administration', 'list', [], true);
    }

    /**
     * Payments settings
     */
    public function settingsAction()
    {
        return new ViewModel([
            'settings_form' => parent::settingsForm('payment', 'payments-administration', 'settings')
        ]);
    }

    /**
     * Currencies list 
     */
    public function currenciesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get data
        $paginator = $this->getModel()->getCurrencies($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return new ViewModel([
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Add currency
     */
    public function addCurrencyAction()
    {
        $currencyForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentCurrency')
            ->setModel($this->getModel());

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $currencyForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($currencyForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // add a new currency
                $result = $this->getModel()->addCurrency($currencyForm->getForm()->getData());

                if (is_numeric($result)) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Currency has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('payments-administration', 'add-currency');
            }
        }

        return new ViewModel([
            'currency_form' => $currencyForm->getForm()
        ]);
    }

    /**
     * Delete selected currencies
     */
    public function deleteCurrenciesAction()
    {
        $request = $this->getRequest();
        
        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($currenciesIds = $request->getPost('currencies', null))) {
                // delete selected currencies
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($currenciesIds as $currencyId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the currency
                    if (true !== ($deleteResult = $this->getModel()->deleteCurrency($currencyId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    $deletedCount++;
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected currencies have been deleted'
                        : 'The selected currency has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('payments-administration', 'currencies', [], true);
    }

    /**
     * Edit a currency action
     */
    public function editCurrencyAction()
    {
        // get the currency info
        if (null == ($currency = $this->
                getModel()->getCurrencyInfo($this->getSlug()))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $currencyForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentCurrency')
            ->setModel($this->getModel())
            ->setCurrencyCodeId($currency['id'])
            ->enabledPrimaryCurrency($this->getModel()->
                    getCurrenciesCount() > 1 && $currency['primary_currency'] != PaymentBaseModel::PRIMARY_CURRENCY);

        $currencyForm->getForm()->setData($currency);
        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $currencyForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($currencyForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit the currency
                if (true == ($result = $this->
                        getModel()->editCurrency($currency, $currencyForm->getForm()->getData()))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Currency has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('payments-administration', 'edit-currency', [
                    'slug' => $currency['id']
                ]);
            }
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'currency' => $currency,
            'currency_form' => $currencyForm->getForm()
        ]);
    }

    /**
     * Coupons list 
     */
    public function couponsAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentCouponFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getCoupons($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Delete selected coupons
     */
    public function deleteCouponsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($couponsIds = $request->getPost('coupons', null))) {
                // delete selected coupons
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($couponsIds as $couponId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the coupon
                    if (true !== ($deleteResult = $this->getModel()->deleteCoupon($couponId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    $deletedCount++;
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected coupons have been deleted'
                        : 'The selected coupon has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('payments-administration', 'coupons', [], true);
    }

    /**
     * Add a coupon
     */
    public function addCouponAction()
    {
        // get a form instance
        $couponForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentCoupon');

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $couponForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($couponForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // add a new coupon
                $result = $this->getModel()->addCoupon($couponForm->getForm()->getData());

                if (is_numeric($result)) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Coupon has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('payments-administration', 'add-coupon');
            }
        }

        return new ViewModel([
            'coupon_form' => $couponForm->getForm()
        ]);
    }

    /**
     * Edit a coupon action
     */
    public function editCouponAction()
    {
        // get the coupon info
        if (null == ($coupon = $this->
                getModel()->getCouponInfo($this->getSlug()))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get a form instance
        $couponForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentCoupon')
            ->setDiscount($coupon['discount'])
            ->setDateStart($coupon['date_start'])
            ->setDateEnd($coupon['date_end']);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $couponForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($couponForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit the coupon
                if (true == ($result = $this->
                        getModel()->editCoupon($coupon['id'], $couponForm->getForm()->getData()))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Coupon has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('payments-administration', 'edit-coupon', [
                    'slug' => $coupon['id']
                ]);
            }
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'coupon' => $coupon,
            'coupon_form' => $couponForm->getForm()
        ]);
    }

    /**
     * Edit exchange rates action
     */
    public function editExchangeRatesAction()
    {
        // get the currency info
        if (null == ($currency = $this->getModel()->getCurrencyInfo($this->
                getSlug(), true)) || null == ($exchangeRates = $this->getModel()->getExchangeRates())) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $exchangeRatesForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentExchangeRate')
            ->setExchangeRates($exchangeRates);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $exchangeRatesForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($exchangeRatesForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit the exchange rates
                if (true == ($result = $this->getModel()->
                        editExchangeRates($exchangeRates, $exchangeRatesForm->getForm()->getData(), $currency['id']))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Exchange rates have been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('payments-administration', 'edit-exchange-rates', [
                    'slug' => $currency['id']
                ]);
            }
        }

        return new ViewModel([
            'currency' => $currency,
            'exchange_form' => $exchangeRatesForm->getForm()
        ]);
    }
}