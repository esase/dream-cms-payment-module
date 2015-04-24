<?php
namespace Payment\Controller;

use Payment\Model\PaymentBase as PaymentBaseModel;
use Zend\View\Model\ViewModel;
use Application\Controller\ApplicationAbstractAdministrationController;

class PaymentAdministrationController extends ApplicationAbstractAdministrationController
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
                getSlug(), false, 'id', false))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get data
        $paginator = $this->getModel()->getTransactionItems($transactionInfo['id'],
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return new ViewModel([
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
                getSlug(), false, 'id', false))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'transaction' => $transactionInfo
        ]);
    }

    /**
     * Delete selected transactions
     */
    public function deleteTransactionsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
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

        if ($request->isPost()) {
            if (null !== ($transactionsIds = $request->getPost('transactions', null))) {
                // process transactions
                $activationResult = true;
                $activationCount  = 0;

                foreach ($transactionsIds as $transactionId) {
                    // get the transaction info
                    if (null == ($transactionInfo = $this->getModel()->getTransactionInfo($transactionId, false, 'id', false))
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
     * News settings
     */
    public function settingsAction()
    {
        return new ViewModel([
            'settings_form' => parent::settingsForm('payment', 'payments-administration', 'settings')
        ]);
    }
}