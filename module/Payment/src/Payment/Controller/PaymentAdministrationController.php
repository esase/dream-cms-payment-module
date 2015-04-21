<?php
namespace Payment\Controller;

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
}