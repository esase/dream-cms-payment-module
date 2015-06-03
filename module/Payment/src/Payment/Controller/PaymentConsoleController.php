<?php

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
     * @var Payment\Model\PaymentConsole
     */
    protected $model;

    /**
     * Get model
     * 
     * @return Payment\Model\PaymentConsole
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