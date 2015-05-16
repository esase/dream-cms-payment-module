<?php

namespace Payment\PagePrivacy;

use Page\PagePrivacy\PageAbstractPagePrivacy;
use Application\Utility\ApplicationRouteParam as RouteParamUtility;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;

class PaymentBuyItemsPrivacy extends PageAbstractPagePrivacy
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
            $this->model = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\PaymentWidget');
        }

        return $this->model;
    }

    /**
     * Is allowed view page
     * 
     * @param array $privacyOptions
     * @param boolean $trusted
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [], $trustedData = false)
    {
        // get a news id from the route or params
        if (!$trustedData) {
            $transactionId = $this->objectId 
                ? $this->objectId
                : RouteParamUtility::getParam('slug', -1);

            // check an existing transaction
            if (null == ($transactionInfo = 
                    $this->getModel()->getTransactionInfo($transactionId, true, 'slug'))) {

                return false;
            }

            if ($transactionInfo['amount'] <= 0) {
                return false;
            }

            if (null == ($paymentsTypes =
                    $this->getModel()->getPaymentsTypes(false, true))) {

                return false;
            }

            // check count of transaction's items
            if (!count($this->getModel()->getAllTransactionItems($transactionInfo['id']))) {
                return false;
            }
        }

        return true;
    }
}