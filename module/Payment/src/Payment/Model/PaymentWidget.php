<?php

namespace Payment\Model;

use Payment\Event\PaymentEvent;
use Application\Utility\ApplicationErrorLogger;
use Zend\Db\ResultSet\ResultSet;
use Exception;

class PaymentWidget extends PaymentBase
{
    /**
     * Add to shopping cart
     *
     * @param array $itemInfo
     *      integer object_id - required
     *      integer module - required
     *      string title - required
     *      string|integer slug - optional
     *      float cost - required
     *      integer|float discount - optional
     *      integer count - required
     * @return integer|string
     */
    public function addToShoppingCart(array $itemInfo)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('payment_shopping_cart')
                ->values(array_merge($itemInfo, [
                    'shopping_cart_id' => $this->getShoppingCartId(),
                    'date' => time()
                ]));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the add item to shopping cart event
        PaymentEvent::fireAddItemToShoppingCartEvent($insertId);
        return $insertId;
    }

    /**
     * Check an item in shopping cart
     *
     * @param integer $objectId
     * @param integer $module
     * @return boolean
     */
    public function inShoppingCart($objectId, $module)
    {
        $select = $this->select();
        $select->from('payment_shopping_cart')
            ->columns([
                'id'
            ])
            ->where([
                'object_id' => $objectId,
                'module' => $module,
                'shopping_cart_id' => $this->getShoppingCartId()            
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? true : false;
    }

    /**
     * Get the payment module info
     *
     * @param string $moduleName
     * @return array
     */
    public function getPaymentModuleInfo($moduleName)
    {
        $select = $this->select();
        $select->from(['a' => 'application_module'])
            ->columns([
                'id',
                'name'
            ])
            ->join(
                ['b' => 'payment_module'],
                'a.id = b.module',
                [
                    'countable',
                    'multi_costs',
                    'must_login',
                    'handler'
                ]
            )
            ->where([
                'name' => $moduleName,
                'status' => self::MODULE_STATUS_ACTIVE
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }
}