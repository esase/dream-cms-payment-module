<?php

namespace Payment\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\Predicate as Predicate;
use Application\Service\ApplicationSetting as SettingService;

class PaymentConsole extends PaymentBase
{
    /**
     * Get all expired shopping cart items
     *
     * @param integer $limit
     * @return array
     */
    public function getExpiredShoppingCartItems($limit)
    {
        $predicate = new Predicate();
        $select = $this->select();
        $select->from('payment_shopping_cart')
            ->columns([
                'id'
            ])
            ->where([
                $predicate->
                    lessThanOrEqualTo('date', time() - (int) SettingService::getSetting('payment_clearing_time'))
            ])
            ->limit($limit);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }

    /**
     * Get all expired not paid transactions
     *
     * @param integer $limit
     * @return array
     */
    public function getExpiredTransactions($limit)
    {
        $predicate = new Predicate();
        $select = $this->select();
        $select->from('payment_transaction_list')
            ->columns([
                'id',
                'slug'
            ])
            ->where([
                'paid' => self::TRANSACTION_NOT_PAID,
                $predicate->lessThanOrEqualTo('date', time() - (int) SettingService::getSetting('payment_clearing_time'))
            ])
            ->limit($limit);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }
}