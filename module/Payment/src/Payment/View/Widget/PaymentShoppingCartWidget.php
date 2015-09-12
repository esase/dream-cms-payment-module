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

            if ($items && is_array($items)) {
                switch($this->getRequest()->getQuery('action')) {
                    // delete shopping cart items
                    case 'delete' :
                        return $this->deleteItems($items);

                    default :
                }
            }
        }

        // get pagination params
        $page = $this->getRouteParam('page', 1);
        $perPage = $this->getRouteParam('per_page');
        $orderBy = $this->getRouteParam('order_by', 'id');
        $orderType = $this->getRouteParam('order_type', 'desc');

        // get data
        $paginator = $this->getModel()->getShoppingCartItems($page, $perPage, $orderBy, $orderType);
        $dataGridWrapper = 'shopping-cart-page-wrapper';

        // get data grid
        $dataGrid = $this->getView()->partial('payment/widget/shopping-cart', [
            'paginator' => $paginator,
            'order_by' => $orderBy,
            'order_type' => $orderType,
            'per_page' => $perPage,
            'widget_connection' =>  $this->widgetConnectionId,
            'widget_position' => $this->widgetPosition,
            'data_grid_wrapper' => $dataGridWrapper,
            'paymentHandlerManager' => $this->
                    getServiceLocator()->get('Payment\Handler\PaymentHandlerManager')
        ]);

        if ($this->getRequest()->isXmlHttpRequest()) {
            return $dataGrid;
        }

        return $this->getView()->partial('payment/widget/shopping-cart-wrapper', [
            'data_grid_wrapper' => $dataGridWrapper,
            'data_grid' => $dataGrid
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