<?php

namespace Payment\Model;

use User\Model\UserBase as UserBaseModel;
use Payment\Service\Payment as PaymentService;
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
     * Add a new transaction
     *
     * @param integer $userId
     * @param array $transactionInfo
     *      integer payment_type - required
     *      string comments - optional
     *      string first_name - required
     *      string last_name - required
     *      string email - required
     *      string phone - required
     *      string address - optional
     * @param array $items
     *      integer object_id
     *      integer module
     *      string title
     *      string slug
     *      float cost
     *      float discount
     *      integer count
     * @param float $amount
     * @return integer|string
     */
    public function addTransaction($userId, array $transactionInfo, array $items, $amount)
    {
        $transactionId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $basicData = [
                'user_hidden' => self::TRANSACTION_USER_NOT_HIDDEN,
                'paid' => self::TRANSACTION_NOT_PAID,
                'language' => $this->getCurrentLanguage(),
                'date' => time(),
                'currency' => PaymentService::getPrimaryCurrency()['id'],
                'amount' => $amount
            ];

            // add the user id
            if (UserBaseModel::DEFAULT_GUEST_ID != $userId) {
                $basicData['user_id'] = $userId;
            }

            // add the discount id
            if (PaymentService::getDiscountCouponInfo()) {
                $basicData['discount_cupon'] = PaymentService::getDiscountCouponInfo()['id'];    
            }

            if (!$transactionInfo['comments']) {
                $transactionInfo['comments'] = null;
            }

            $insert = $this->insert()
                ->into('payment_transaction_list')
                ->values(array_merge($transactionInfo, $basicData));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $transactionId = $this->adapter->getDriver()->getLastGeneratedValue();

            // generate a random slug
            $update = $this->update()
                ->table('payment_transaction_list')
                ->set([
                    'slug' => strtoupper($this->generateSlug($transactionId, $this->
                            generateRandString(self::TRANSACTION_MIN_SLUG_LENGTH, self::ALLOWED_SLUG_CHARS), 'payment_transaction_list', 'id'))
                ])
                ->where([
                    'id' => $transactionId
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // update the discount coupon info
            if (PaymentService::getDiscountCouponInfo()) {
                $update = $this->update()
                    ->table('payment_discount_cupon')
                    ->set([
                        'used' => self::COUPON_USED
                    ])
                    ->where([
                        'id' => PaymentService::getDiscountCouponInfo()['id']
                    ]);

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();
            }

            // add  transaction's items
            foreach ($items as $item) {
                $insert = $this->insert()
                    ->into('payment_transaction_item')
                    ->values([
                        'transaction_id' => $transactionId,
                        'object_id' => $item['object_id'],
                        'module' => $item['module'],
                        'title' => $item['title'],
                        'slug' => $item['slug'],
                        'cost' => $item['cost'],
                        'discount' => $item['discount'],
                        'count' => $item['count']
                    ]);

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }

            // clear the shopping cart items
            if (null != ($items = $this->getAllShoppingCartItems(false))) {
                // delete all items
                foreach ($items as $itemInfo) {
                    $this->deleteFromShoppingCart($itemInfo['id']);
                }
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the add payment transaction event
        PaymentEvent::fireAddPaymentTransactionEvent($transactionId, $transactionInfo);
        return $transactionId;
    }

    /**
     * Get payments types
     *
     * @param boolean $keyId
     * @param boolean $fullArray
     * @return array
     */
    public function getPaymentsTypes($keyId = true, $fullArray = false)
    {
        $paymentsTypes = [];

        $select = $this->select();
        $select->from('payment_type')
            ->columns([
                'id',
                'name',
                'description',
                'enable_option',
                'handler'
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        // process available payments
        foreach ($result as $payment) {
            if (!(int) SettingService::getSetting($payment['enable_option'])) {
                continue;
            }

            $paymentsTypes[($keyId ? $payment['id'] : $payment['name'])] = $fullArray
                ? $payment
                : $payment['description'];
        }

        return $paymentsTypes;
    }

    /**
     * Update the shopping cart's item
     *
     * @param integer $id
     * @param array $itemInfo
     * @return boolean|string
     */
    public function updateShoppingCartItem($id, array $itemInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('payment_shopping_cart')
                ->set($itemInfo)
                ->where([
                    'id' => $id,
                    'shopping_cart_id' => $this->getShoppingCartId(),
                    'language' => $this->getCurrentLanguage()
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit item into shopping cart event
        PaymentEvent::fireEditItemIntoShoppingCartEvent($id);
        return true;
    }

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
                'object_id',
                'title',
                'cost',
                'discount',
                'count',
                'total' => new Expression('cost * count - discount'),
                'slug'
            ])
            ->join(
                ['b' => 'payment_module'],
                'a.module = b.module',
                [
                    'countable',
                    'multi_costs',
                    'handler',
                    'page_name'
                ]
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