<?php
namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Model\PaymentBase as PaymentBaseModel;

class PaymentItemStatus extends AbstractHelper
{
    /**
     * Payment item status
     *
     * @param array $info
     *      integer id
     *      string title
     *      float cost
     *      float discount
     *      integer count
     *      integer active
     *      integer available
     *      string slug
     *      string view_controller
     *      string view_action
     *      integer countable
     *      integer must_login
     *      string handler
     *      integer object_id
     *      srting module_state
     * @return string
     */
    public function __invoke($info)
    {
        // check the item's status
        if ($info['active'] == PaymentBaseModel::ITEM_NOT_ACTIVE) {
            return  $this->getView()->translate('Item is not active');
        }

        if ($info['available'] == PaymentBaseModel::ITEM_NOT_AVAILABLE) {
            return  $this->getView()->translate('Item is not available');
        }

        if ($info['module_state'] != PaymentBaseModel::MODULE_STATUS_ACTIVE) {
            return  $this->getView()->translate('Module is not active');
        }

        return $this->getView()->translate('Active');
    }
}