<?php

namespace Payment\Model;

use Payment\Event\PaymentEvent;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Exception;

class PaymentWidget extends PaymentBase
{
    /**
     * Get the shopping cart's item info
     *
     * @param integer $itemId
     * @param boolean $checkModuleState
     * @return array
     */
    public function getShoppingCartItemInfo($itemId, $checkModuleState = true)
    {
        $select = $this->select();
        $select->from(['a' => 'payment_shopping_cart'])
            ->columns([
                'id',
                'object_id',
                'cost',
                'discount',
                'count'
            ])
            ->join(
                ['b' => 'payment_module'],
                'a.module = b.module',
                [
                    'module',
                    'countable',
                    'multi_costs',
                    'must_login',
                    'handler'
                ]
            );

        if ($checkModuleState) {
            $select->join(
                ['c' => 'application_module'],
                new Expression('b.module = c.id and c.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            );
        }

        $select->where([
            'a.id' => $itemId,
            'a.shopping_cart_id' => $this->getShoppingCartId()
        ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Get shopping cart items
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @return Zend\Paginator\Paginator
     */
    public function getShoppingCartItems($page = 1, $perPage = 0, $orderBy = null, $orderType = null)
    {
        $orderFields = [
            'id',
            'title',
            'cost',
            'discount',
            'count',
            'total'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'payment_shopping_cart'])
            ->columns([
                'id',
                'title',
                'cost',
                'discount',
                'count',
                'total' => new Expression('cost * count - discount')
            ])
            ->join(
                ['b' => 'payment_module'],
                'a.module = b.module',
                []
            )
            ->join(
                ['c' => 'application_module'],
                new Expression('b.module = c.id and c.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            )
            ->order($orderBy . ' ' . $orderType)
            ->where(array(
                'shopping_cart_id' => $this->getShoppingCartId(),
                'language' => $this->getCurrentLanguage()
            ));

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));
        
        return $paginator;
    }

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
                    'date' => time(),
                    'language' => $this->getCurrentLanguage()
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
     * @return integer
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
                'shopping_cart_id' => $this->getShoppingCartId(),
                'language' => $this->getCurrentLanguage()
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? $resultSet->current()->id : null;
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