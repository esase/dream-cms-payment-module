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

use User\Service\UserIdentity as UserIdentityService;
use Payment\Service\Payment as PaymentService;
use Application\Utility\ApplicationCsrf as ApplicationCsrfUtility;

class PaymentTransactionHistoryWidget extends PaymentAbstractWidget
{
   /**
     * Get widget content
     *
     * @return string|boolean
     */
   public function getContent()
   {
        $userId = UserIdentityService::getCurrentUserIdentity()['user_id'];

        // process post actions
        if ($this->getRequest()->isPost()
                && ApplicationCsrfUtility::isTokenValid($this->getRequest()->getPost('csrf'))
                && $this->getRequest()->getPost('form_name') == 'transactions') {

            $transactions = $this->getRequest()->getPost('transactions');

            if ($transactions && is_array($transactions)) {
                switch($this->getRequest()->getQuery('action')) {
                    // delete selected transactions
                    case 'delete' :
                        return $this->deleteTransactions($transactions, $userId);

                    default :
                }
            }
        }

        // get pagination options
        list($pageParamName, $perPageParamName,
                $orderByParamName, $orderTypeParamName) = $this->getPaginationParams();

        $page = $this->getView()->applicationRoute()->getQueryParam($pageParamName, 1);
        $perPage = $this->getView()->applicationRoute()->getQueryParam($perPageParamName);
        $orderBy = $this->getView()->applicationRoute()->getQueryParam($orderByParamName);
        $orderType = $this->getView()->applicationRoute()->getQueryParam($orderTypeParamName);

        $filters = [];
        $fieldsPostfix = '_' . $this->widgetConnectionId;

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Payment\Form\PaymentUserTransactionFilter')
            ->setFieldsPostfix($fieldsPostfix);

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // validate the filter form
        if ($this->getRequest()->isXmlHttpRequest()
                || $this->getView()->applicationRoute()->getQueryParam('form_name') == $filterForm->getFormName()) {

            // check the filter form validation
            if ($filterForm->getForm()->isValid()) {
                $filters = $filterForm->getData();
            }
        }

        // get data
        $paginator = $this->getModel()->
                getUserTransactions($userId, $page, $perPage, $orderBy, $orderType, $filters, $fieldsPostfix);

        $dataGridWrapper = 'transactions-page-wrapper';

        // get data grid
        $dataGrid = $this->getView()->partial('payment/widget/transaction-history', [
            'current_currency' => PaymentService::getPrimaryCurrency(),
            'payment_types' =>  $this->getModel()->getPaymentsTypes(false, true),
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $orderBy,
            'order_type' => $orderType,
            'per_page' => $perPage,
            'page_param_name' => $pageParamName,
            'per_page_param_name' => $perPageParamName,
            'order_by_param_name' => $orderByParamName,
            'order_type_param_name' => $orderTypeParamName,
            'widget_connection' =>  $this->widgetConnectionId,
            'widget_position' => $this->widgetPosition,
            'data_grid_wrapper' => $dataGridWrapper
        ]);

        if ($this->getRequest()->isXmlHttpRequest()) {
            return $dataGrid;
        }

        return $this->getView()->partial('payment/widget/transaction-history-wrapper', [
            'data_grid_wrapper' => $dataGridWrapper,
            'data_grid' => $dataGrid
        ]);
   }

    /**
     * Get pagination params
     *
     * @return array
     */
    protected function getPaginationParams()
    {
        return [
            'page_' . $this->widgetConnectionId,
            'per_page_' . $this->widgetConnectionId,
            'order_by_' . $this->widgetConnectionId,
            'order_type_' . $this->widgetConnectionId
        ];
    }

    /**
     * Delete transactions
     * 
     * @param array $transactionsIds
     * @param integer $userId
     * @return void
     */
    protected function deleteTransactions(array $transactionsIds, $userId)
    {
        $hideResult = false;
        $hiddenCount = 0;

        foreach ($transactionsIds as $transactionId) {       
            // hide the transaction
            if (true !== ($hideResult = $this->
                    getModel()->hideUserTransaction($transactionId, $userId))) {

                $this->getFlashMessenger()
                    ->setNamespace('success')
                    ->addMessage($this->translate('Error occurred'));
 
                break;
            }

            $hiddenCount++;
        }

        if (true === $hideResult) {
            $message = $hiddenCount > 1
                ? 'Selected transactions have been deleted'
                : 'The selected transaction has been deleted';

            $this->getFlashMessenger()
                ->setNamespace('success')
                ->addMessage($this->translate($message));
        }

        $this->redirectTo([], true);
    }
}