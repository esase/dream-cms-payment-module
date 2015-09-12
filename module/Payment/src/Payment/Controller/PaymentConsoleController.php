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

use Application\Controller\ApplicationAbstractBaseConsoleController;

class PaymentConsoleController extends ApplicationAbstractBaseConsoleController
{
    /**
     * Limit items
     */
    const LIMIT_ITEMS = 500;

    /**
     * Model instance
     *
     * @var \Payment\Model\PaymentConsole
     */
    protected $model;

    /**
     * Get model
     * 
     * @return \Payment\Model\PaymentConsole
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\PaymentConsole');
        }

        return $this->model;
    }

    /**
     * Clean shopping cart and transactions items
     */
    public function cleanExpiredItemsAction()
    {
        $request = $this->getRequest();

        // get list of expired shopping cart items
        $deletedShoppingCartItems = 0;
        if (null != ($items = $this->getModel()->getExpiredShoppingCartItems(self::LIMIT_ITEMS))) {
            foreach ($items as $item) {
                // delete the item
                if (true === ($deleteResult = 
                        $this->getModel()->deleteFromShoppingCart($item['id'], false, true))) {

                    $deletedShoppingCartItems++;
                }
            }
        }

        // get list of expired not paid transactions
        $deletedTransactions = 0;
        if (null != ($transactions = $this->getModel()->getExpiredTransactions(self::LIMIT_ITEMS))) {
            // process list of transactions
            foreach ($transactions as $transaction) {
                // delete the transaction
                if (true === ($deleteResult = 
                        $this->getModel()->deleteTransaction($transaction['id'], 0, 'system'))) {

                    $deletedTransactions++;
                }
            }
        }

        // get list of empty transactions
        $deletedEmptyTransactions = 0;
        if (null != ($transactions = $this->getModel()->getEmptyTransactions(self::LIMIT_ITEMS))) {
            // process list of transactions
            foreach ($transactions as $transaction) {
                // delete the transaction
                if (true === ($deleteResult = 
                        $this->getModel()->deleteTransaction($transaction['id'], 0, 'system'))) {

                    $deletedEmptyTransactions++;
                }
            }
        }

        $verbose = $request->getParam('verbose');

        if (!$verbose) {
            return 'All expired shopping cart items and expired not paid transactions have been deleted.' . "\n";
        }

        $longDescription  = $deletedShoppingCartItems . ' items have been deleted from the shopping cart.'. "\n";
        $longDescription .= $deletedTransactions . ' not paid transactions have been deleted.'. "\n";
        $longDescription .= $deletedEmptyTransactions . ' empty transactions have been deleted.'. "\n";

        return $longDescription;
    }
}