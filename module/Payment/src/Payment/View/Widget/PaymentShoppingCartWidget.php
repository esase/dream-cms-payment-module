<?php

namespace Payment\View\Widget;

class PaymentShoppingCartWidget extends PaymentAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        // process post actions
        if ($this->getRequest()->isPost() 
                && $this->getRequest()->getPost('form_name') == 'shopping-cart') {

            $items = $this->getRequest()->getPost('items');

            switch($this->getRequest()->getQuery('action')) {
                // delete shopping cart items
                case 'delete' :
                    if ($items && is_array($items)) {
                        return $this->deleteItems($items);
                    }

                default :
            }
        }

        // get pagination params
        $page = $this->getRouteParam('page', 1);
        $perPage = $this->getRouteParam('per_page');
        $orderBy = $this->getRouteParam('order_by', 'id');
        $orderType = $this->getRouteParam('order_type', 'desc');

        // get data
        $paginator = $this->getModel()->getShoppingCartItems($page, $perPage, $orderBy, $orderType);

        return $this->getView()->partial('payment/widget/shopping-cart', [
            'paginator' => $paginator,
            'order_by' => $orderBy,
            'order_type' => $orderType,
            'per_page' => $perPage,
            'paymentHandlerManager' => $this->
                    getServiceLocator()->get('Payment\Handler\PaymentHandlerManager')
        ]);
    }

    /**
     * Delete items
     * 
     * @param array $itemsIds
     * @return void
     */
    protected function deleteItems(array $itemsIds)
    {
        $deleteResult = false;
        $deletedCount = 0;

        foreach ($itemsIds as $itemId) {
            // get an item info
            if (null == ($itemInfo = 
                    $this->getModel()->getShoppingCartItemInfo($itemId, false))) {

                continue;
            }

            // delete the item
            if (true !== ($deleteResult = $this->getModel()->deleteFromShoppingCart($itemId))) {
                $this->getFlashMessenger()
                    ->setNamespace('error')
                    ->addMessage($this->translate('Error occurred'));

                break;
            }

            // return a discount back
            if ((float) $itemInfo['discount']) {
                // get the payment handler
                $this->getServiceLocator()
                    ->get('Payment\Handler\PaymentHandlerManager')
                    ->getInstance($itemInfo['handler'])
                    ->returnBackDiscount($itemId, (float) $itemInfo['discount']);
            }

            $deletedCount++;
        }

        if (true === $deleteResult) {
            $message = $deletedCount > 1
                ? 'Selected items have been deleted'
                : 'The selected item has been deleted';

            $this->getFlashMessenger()
                ->setNamespace('success')
                ->addMessage($this->translate($message));
        }

        $this->redirectTo([], true);
    }
}