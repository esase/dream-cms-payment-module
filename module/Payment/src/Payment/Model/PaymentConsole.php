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
namespace Payment\Model;

use Application\Service\ApplicationSetting as SettingService;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\Predicate as Predicate;

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

    /**
     * Get all empty transactions
     *
     * @param integer $limit
     * @return array
     */
    public function getEmptyTransactions($limit)
    {
        $predicate = new Predicate();
        $select = $this->select();
        $select->from(['a' => 'payment_transaction_list'])
            ->columns([
                'id',
                'slug'
            ])
           ->join(
                ['b' => 'payment_transaction_item'],
                'a.id = b.transaction_id',
                [],
                'left'
            )
            ->where([
                $predicate->isNull('b.transaction_id')
            ])
            ->group('a.id')
            ->limit($limit);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }
}