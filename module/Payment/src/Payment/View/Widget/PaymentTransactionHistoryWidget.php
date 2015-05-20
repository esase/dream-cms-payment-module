<?php

namespace Payment\View\Widget;

use User\Service\UserIdentity as UserIdentityService;
use  Payment\Service\Payment as PaymentService;

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
        if ($this->getView()->
                applicationRoute()->getQueryParam('form_name') == $filterForm->getFormName()) {

            // check the filter form validation
            if ($filterForm->getForm()->isValid()) {
                $filters = $filterForm->getData();
            }
        }

        // get data
        $paginator = $this->getModel()->
                getUserTransactions($userId, $page, $perPage, $orderBy, $orderType, $filters, $fieldsPostfix);

        $dataGridWrapper = 'transactions-wrapper';

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