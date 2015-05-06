<?php
namespace Payment\Model;

use Application\Utility\ApplicationCache as CacheUtility;
use Payment\Event\PaymentEvent;
use Payment\Service\PaymentService;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Application\Model\ApplicationAbstractBase;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Predicate\In as InPredicate;
use Zend\Db\ResultSet\ResultSet;
use Zend\Http\Header\SetCookie;
use Zend\Db\Sql\Predicate\Literal as LiteralPredicate;
use Exception;

class PaymentBase extends ApplicationAbstractBase
{
    /**
     * Module countable flag
     */
    const MODULE_COUNTABLE = 1;

    /**
     * Module multi costs flag
     */
    const MODULE_MULTI_COSTS = 1;

    /**
     * Transaction paid
     */
    const TRANSACTION_PAID = 1;

    /**
     * Transaction not paid
     */
    const TRANSACTION_NOT_PAID = 0;

    /**
     * Primary currency
     */
    const PRIMARY_CURRENCY = 1;

    /**
     * Not primary currency
     */
    const NOT_PRIMARY_CURRENCY = 0;

    /**
     * Item is active flag
     */ 
    const ITEM_ACTIVE = 1;

    /**
     * Item is available flag
     */ 
    const ITEM_AVAILABLE = 1;

    /**
     * Item is not active flag
     */ 
    const ITEM_NOT_ACTIVE = 0;

    /**
     * Item is not available flag
     */ 
    const ITEM_NOT_AVAILABLE = 0;

    /**
     * Payment exchange rates cache
     */
    const CACHE_EXCHANGE_RATES = 'Payment_Exchange_Rates';

    /**
     * Coupon used
     */
    const COUPON_USED = 1;

    /**
     * Coupon not used
     */
    const COUPON_NOT_USED = 0;

    /**
     * Coupon min slug length
     */
    const COUPON_MIN_SLUG_LENGTH = 15;

    /**
     * Allowed slug chars
     */
    const ALLOWED_SLUG_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Shopping cart cookie
     */ 
    const SHOPPING_CART_COOKIE = 'shopping_cart';

    /**
     * Shopping cart id length
     */
    const SHOPPING_CART_ID_LENGTH = 50;

    /**
     * Save shopping cart currency
     *
     * @param string $currency
     * @return void
     */
    public function setShoppingCartCurrency($currency)
    {
        $shoppingCartId = $this->getShoppingCartId();
        $value = $shoppingCartId . '|' . $currency;

        $this->_saveShoppingCartCookie($value);
    }

    /**
     * Delete the shopping cart's item
     *
     * @param integer $itemId
     * @param boolean $useShoppingCartId
     * @param boolean $isSystem
     * @return boolean|string
     */
    public function deleteFromShoppingCart($itemId, $useShoppingCartId = true, $isSystem = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('payment_shopping_cart')
                ->where([
                    'id' => $itemId
                ]);

            if ($useShoppingCartId) {
                $delete->where([
                   'shopping_cart_id' => $this->getShoppingCartId()
                ]);
            }

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        if ($result->count()) {
            // fire the delete item from shopping cart event
            PaymentEvent::fireDeleteItemFromShoppingCartEvent($itemId, $isSystem);
            return true;
        }

        return false;
    }

    /**
     * Get shopping cart id
     *
     * @return string
     */
    public function getShoppingCartId()
    {
        return current(explode( '|', $this->_getShoppingCartId()));
    }

    /**
     * Get an active coupon info
     *
     * @param string|integer $id
     * @param string $field
     * @return array
     */
    public function getActiveCouponInfo($id, $field = 'slug')
    {
        $time = time();
        $select = $this->select();
        $select->from('payment_discount_cupon')
            ->columns([
                'id',
                'slug',
                'discount',
                'used',
                'date_start',
                'date_end'
            ])
            ->where([
                ($field == 'id' ? $field : 'slug') => $id,
                'used' => self::COUPON_NOT_USED
            ])
            ->where([
                new LiteralPredicate('(date_start = 0 or 
                        (' . $time . ' >= date_start)) and (date_end = 0 or (' . $time . ' <= date_end))')
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? $resultSet->current() : array();
    }

    /**
     * Get discounted items amount
     *
     * @param float $itemsAmount
     * @param float $discount
     * @return float|integer
     */
    public function getDiscountedItemsAmount($itemsAmount, $discount)
    {
        return $itemsAmount - ($itemsAmount * $discount / 100);
    }

    /**
     * Get items amount
     *
     * @param array $itemsList
     *      float cost
     *      integer count
     *      float discount
     * @param float $discount
     * @param boolean $rounding
     * @return float|integer
     */
    public function getItemsAmount(array $itemsList, $discount = 0, $rounding = false)
    {
        $itemsAmount = 0;
        foreach($itemsList as $itemInfo) {
            $itemsAmount += (float) $itemInfo['cost'] * (int) $itemInfo['count'] - (float) $itemInfo['discount'];
        }

        // calculate the discount
        if ($discount) {
            $itemsAmount = $this->getDiscountedItemsAmount($itemsAmount, $discount);
        }

        return $rounding
            ? PaymentService::roundingCost($itemsAmount)
            : $itemsAmount;
    }

    /**
     * Get all active shopping cart items
     *
     * @param boolean $onlyActive
     * @return array
     */
    public function getAllShoppingCartItems($onlyActive = true)
    {
        $select = $this->select();
        $select->from(['a' => 'payment_shopping_cart'])
            ->columns([
                'id',
                'object_id',
                'cost',
                'module',
                'title',
                'slug',
                'discount',
                'count'
            ])
            ->join(
                ['b' => 'payment_module'],
                'a.module = b.module',
                [
                    'countable',
                    'must_login',
                    'handler'
                ]
            )
            ->join(
                ['c' => 'application_module'],
                new Expression('b.module = c.id and c.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            );

        if ($onlyActive) {
            $select->where([
                'a.active' => self::ITEM_ACTIVE,
                'a.available' => self::ITEM_AVAILABLE               
            ]);
        }

        $select->where([
            'a.shopping_cart_id' => $this->getShoppingCartId(),
            'a.language' => $this->getCurrentLanguage()
        ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }

    /**
     * Save a shopping cart cookie
     *
     * @param string $value
     * @return void
     */
    private function _saveShoppingCartCookie($value)
    {
        $header = new SetCookie();
        $header->setName(self::SHOPPING_CART_COOKIE)
            ->setValue($value)
            ->setPath('/')
            ->setHttpOnly(true)
            ->setExpires(time() + (int) SettingService::getSetting('payment_shopping_cart_session_time'));

        $this->serviceLocator->get('Response')->getHeaders()->addHeader($header);
    }

    /**
     * Get shopping cart uid
     *
     * @return string
     */
    private function _getShoppingCartId()
    {
        $request  = $this->serviceLocator->get('Request');
        $shoppingCartId = !empty($request->getCookie()->{self::SHOPPING_CART_COOKIE})
            ? $request->getCookie()->{self::SHOPPING_CART_COOKIE}
            : null;

        // generate a new shopping cart id
        if (!$shoppingCartId) {
            // generate a new hash
            $shoppingCartId =  md5(time() . '_' . $this->generateRandString(self::SHOPPING_CART_ID_LENGTH));
            $this->_saveShoppingCartCookie($shoppingCartId);
        }

        return $shoppingCartId;
    }

    /**
     * Get shopping cart currency
     *
     * @return string
     */
    public function getShoppingCartCurrency()
    {
        $currencyId = explode( '|', $this->_getShoppingCartId());
        return count($currencyId) == 2
            ? end($currencyId)
            : null;
    }

    /**
     * Activate transaction
     *
     * @param array $transactionInfo
     *      integer id
     *      string slug
     *      integer user_id
     *      string first_name
     *      string last_name
     *      string phone
     *      string address
     *      string email
     *      integer currency
     *      integer payment_type
     *      integer discount_cupon
     *      string currency_code
     *      string payment_name
     * @param integer $paymentTypeId
     * @param boolean $isSystem
     * @param boolean $sendMessage
     * @return boolean
     */
    public function activateTransaction(array $transactionInfo, $paymentTypeId = 0, $isSystem = false, $sendMessage = false)
    {
        if (true === ($result = $this->activateTransactionItem($transactionInfo['id'], 'id', $paymentTypeId))) {
            // mark as paid all transaction's items
            if (null != ($activeTransactionItems = $this->getAllTransactionItems($transactionInfo['id']))) {
                // process transactions
                foreach ($activeTransactionItems as $itemInfo) {
                    // get the payment handler
                    $handler = $this->serviceLocator
                        ->get('Payment\Handler\HandlerManager')
                        ->getInstance($itemInfo['handler']);

                    // set an item as paid
                    $handler->setPaid($itemInfo['object_id'], $transactionInfo);

                    // decrease the item's count
                    if ($itemInfo['countable'] == self::MODULE_COUNTABLE) {
                        $handler->decreaseCount($itemInfo['object_id'], $itemInfo['count']);
                    }
                }
            }

            // fire the activate payment transaction event
            PaymentEvent::fireActivatePaymentTransactionEvent($transactionInfo['id'], $isSystem, ($sendMessage ? $transactionInfo : []));
            return true;
        }

        return false;
    }

    /**
     * Get all transaction items
     *
     * @param integer $transactionId
     * @param boolean $onlyActive
     * @return array
     */
    public function getAllTransactionItems($transactionId, $onlyActive = true)
    {
        $select = $this->select();
        $select->from(['a' => 'payment_transaction_item'])
            ->columns([
                'object_id',
                'cost',
                'discount',
                'count'
            ])
            ->join(
                ['b' => 'payment_module'],
                'a.module = b.module',
                [
                    'countable',
                    'handler'
                ]
            )
            ->join(
                ['c' => 'application_module'],
                new Expression('b.module = c.id and c.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            )
            ->where([
                'transaction_id' => $transactionId
            ]);

        if ($onlyActive) {
            $select->where([
                'a.active' => self::ITEM_ACTIVE,
                'a.available' => self::ITEM_AVAILABLE             
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }

    /**
     * Activate transaction
     *
     * @param integer $transactionId
     * @param string $field
     * @param integer $paymentTypeId
     * @return boolean|string
     */
    protected function activateTransactionItem($transactionId, $field = 'id', $paymentTypeId = 0)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $baseFields = [
                'paid'  => self::TRANSACTION_PAID
            ];

            if ($paymentTypeId) {
                $baseFields = array_merge($baseFields, [
                    'payment_type' => $paymentTypeId
                ]);
            }

            $update = $this->update()
                ->table('payment_transaction_list')
                ->set($baseFields)
                ->where([
                    ($field == 'id' ? $field : 'slug') => $transactionId
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

        return true;
    }

    /**
     * Delete transaction
     *
     * @param integer $transactionId
     * @param integer $userId
     * @param string $type
     * @return boolean|string
     */
    public function deleteTransaction($transactionId, $userId = 0, $type = null)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('payment_transaction_list')
                ->where(array(
                    'id' => $transactionId
                ));

            if ($userId) {
                $delete->where(array(
                    'user_id' => $userId
                ));
            }

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        if ($result->count()) {
            // fire the delete payment transaction event
            PaymentEvent::fireDeletePaymentTransactionEvent($transactionId, $type);
            return true;
        }

        return false;
    }

    /**
     * Get the transaction's items
     *
     * @param integer $transactionId
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @return object
     */
    public function getTransactionItems($transactionId, $page = 1, $perPage = 0, $orderBy = null, $orderType = null)
    {
        $orderFields = [
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
            : 'title';

        $select = $this->select();
        $select->from(['a' => 'payment_transaction_item'])
            ->columns([
                'object_id',
                'title',
                'cost',
                'discount',
                'count',
                'total' => new Expression('cost * count - discount'),
                'active',
                'available',
                'slug'
            ])
            ->join(
                ['b' => 'payment_module'],
                'a.module = b.module',
                [
                    'view_controller',
                    'view_action',
                    'view_check',
                    'countable',
                    'module_extra_options' => 'extra_options',
                    'handler'
                ]
            )
            ->join(
                ['c' => 'application_module'],
                'b.module = c.id',
                [
                    'module_state' => 'status'
                ]
            )
            ->where([
                'transaction_id' => $transactionId
            ])
            ->order($orderBy . ' ' . $orderType);

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Get the transaction info
     *
     * @param integer $id
     * @param boolean $onlyNotPaid
     * @param string $field
     * @param boolean $onlyPrimaryCurrency
     * @param integer $userId
     * @return array
     */
    public function getTransactionInfo($id, $onlyNotPaid = true, $field = 'id', $onlyPrimaryCurrency = true, $userId = 0)
    {
        $currencyCondition = $onlyPrimaryCurrency
            ? new Expression('a.currency = b.id and b.primary_currency = ?', [self::PRIMARY_CURRENCY])
            : new Expression('a.currency = b.id');

        $select = $this->select();
        $select->from(['a' => 'payment_transaction_list'])
            ->columns([
                'id',
                'slug',
                'user_id',
                'first_name',
                'last_name',
                'phone',
                'address',
                'email',
                'currency',
                'payment_type',
                'amount',
                'comments',
                'date',
                'paid'
            ])
            ->join(
                ['b' => 'payment_currency'],
                $currencyCondition,
                [
                    'currency_code' => 'code',
                    'currency_name' => 'name'
                ]
            )
            ->join(
                ['c' => 'payment_type'],
                'a.payment_type = c.id',
                [
                    'payment_name' => 'name',
                    'payment_description' => 'description'
                ],
                'left'
            )
            ->join(
                ['d' => 'payment_discount_cupon'],
                'a.discount_cupon = d.id',
                [
                    'discount_cupon' => 'discount'
                ],
                'left'
            )
            ->where([
                ($field == 'id' ? 'a.id' : 'a.slug') => $id
            ]);

        if ($onlyNotPaid) {
            $select->where([
                'paid' => self::TRANSACTION_NOT_PAID
            ]);
        }

        if ($userId) {
            $select->where([
                'user_id' => $userId
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Is the currency code free
     *
     * @param string $code
     * @param integer $currencyCodeId
     * @return boolean
     */
    public function isCurrencyCodeFree($code, $currencyCodeId = 0)
    {
        $select = $this->select();
        $select->from('payment_currency')
            ->columns([
                'id'
            ])
            ->where(['code' => $code]);

        if ($currencyCodeId) {
            $select->where([
                new NotInPredicate('id', [$currencyCodeId])
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? false : true;
    }

    /**
     * Remove the exchange rates cache
     *
     * @return void
     */
    protected function removeExchangeRatesCache()
    {
        $cacheName = CacheUtility::getCacheName(self::CACHE_EXCHANGE_RATES, [true]);
        $this->staticCacheInstance->removeItem($cacheName);

        $cacheName = CacheUtility::getCacheName(self::CACHE_EXCHANGE_RATES, [false]);
        $this->staticCacheInstance->removeItem($cacheName);
    }

    /**
     * Get the currency info
     *
     * @param integer $id
     * @param boolean $primary
     * @return array
     */
    public function getCurrencyInfo($id, $primary = false)
    {
        $select = $this->select();
        $select->from('payment_currency')
            ->columns([
                'id',
                'code',
                'name',
                'primary_currency'
            ])
            ->where([
                'id' => $id
            ]);

        if ($primary) {
            $select->where([
                new InPredicate('primary_currency ', [self::PRIMARY_CURRENCY])
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Get the coupon info
     *
     * @param integer|sting $id
     * @param string $field
     * @return array
     */
    public function getCouponInfo($id, $field = 'id')
    {
        $select = $this->select();
        $select->from('payment_discount_cupon')
            ->columns([
                'id',
                'slug',
                'discount',
                'used',
                'date_start',
                'date_end'
            ])
            ->where([
                ($field == 'id' ? $field : 'slug') => $id
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Get exchange rates
     *
     * @param boolean $excludePrimary
     * @return array
     */
    public function getExchangeRates($excludePrimary = true)
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_EXCHANGE_RATES, [$excludePrimary]);

        // check data in cache
        if (null === ($rates = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(['a' => 'payment_currency'])
                ->columns([
                    'id',
                    'code',
                    'name',
                    'primary_currency'
                ])
                ->join(
                    ['b' => 'payment_exchange_rate'],
                    new Expression('a.id = b.currency'),
                    [
                        'rate'
                    ],
                    'left'
                );

            if ($excludePrimary) {
                $select->where([
                    new NotInPredicate('primary_currency', [self::PRIMARY_CURRENCY])
                ]);
            }

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            foreach ($result as $rate) {
                $rates[$rate['code']] = [
                    'id' => $rate['id'],
                    'code' => $rate['code'],
                    'name' => $rate['name'],
                    'rate' => $rate['rate'],
                    'primary_currency' => $rate['primary_currency']
                ];    
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $rates);
        }

        return $rates;        
    }
}