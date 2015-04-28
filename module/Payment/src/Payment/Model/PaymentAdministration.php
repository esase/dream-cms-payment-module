<?php
namespace Payment\Model;

use Payment\Event\PaymentEvent;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Expression;
use Exception;

class PaymentAdministration extends PaymentBase
{
    /**
     * Get transactions list
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string slug
     *      integer paid
     *      string email
     *      string date
     * @return Zend\Paginator\Paginator
     */
    public function getTransactionsList($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'slug',
            'paid',
            'cost',
            'email',
            'date',
            'currency'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'payment_transaction_list'])
            ->columns([
                'id',
                'slug',
                'paid',
                'email',
                'cost' => 'amount',
                'date'
            ])
            ->join(
                ['b' => 'payment_currency'],
                'a.currency = b.id',
                [
                    'currency' => 'code'
                ]
            )
            ->order($orderBy . ' ' . $orderType);

        // filter by a slug
        if (!empty($filters['slug'])) {
            $select->where([
                'a.slug' => $filters['slug']
            ]);
        }

        // filter by a paid status
        if (isset($filters['paid']) && $filters['paid'] != null) {
            $select->where([
                'a.paid' => ((int) $filters['paid'] == self::TRANSACTION_PAID ? $filters['paid'] : self::TRANSACTION_NOT_PAID)
            ]);
        }

        // filter by a email
        if (!empty($filters['email'])) {
            $select->where([
                'a.email' => $filters['email']
            ]);
        }

        // filter by a date
        if (!empty($filters['date'])) {
            $select->where([
                'a.date' => $filters['date']
            ]);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Get currencies
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @return Paginator
     */
    public function getCurrencies($page = 1, $perPage = 0, $orderBy = null, $orderType = null)
    {
        $orderFields = [
            'id',
            'code',
            'primary'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from('payment_currency')
            ->columns([
                'id',
                'code',
                'name',
                'primary' => 'primary_currency'
            ])
            ->order($orderBy . ' ' . $orderType);

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Add new currency
     *
     * @param array $currencyInfo
     *      string code
     *      sting name
     *      integer primary_currency
     * @return integer|string
     */
    public function addCurrency(array $currencyInfo)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            if (!$currencyInfo['primary_currency']) {
                $currencyInfo['primary_currency'] = self::NOT_PRIMARY_CURRENCY;
            }

            $insert = $this->insert()
                ->into('payment_currency')
                ->values($currencyInfo);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            // skip the previously activated primary currency
            if ((int) $currencyInfo['primary_currency'] == self::PRIMARY_CURRENCY) {
                $this->skippActivatedPrimaryCurrency($insertId);
                $this->clearExchangeRates();
                $this->cleanShoppingCart();
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the add payment currency event
        PaymentEvent::fireAddPaymentCurrencyEvent($insertId);
        return $insertId;
    }

    /**
     * Skip the previously activated primary currency
     *
     * @param integer $currencyId
     * @return boolean
     */
    protected function skippActivatedPrimaryCurrency($currencyId)
    {
        $update = $this->update()
            ->table('payment_currency')
            ->set([
                'primary_currency' => self::NOT_PRIMARY_CURRENCY
            ])
            ->where([
               new NotInPredicate('id', [$currencyId])
            ]);

        $statement = $this->prepareStatementForSqlObject($update);
        $result = $statement->execute();

        return $result->count() ? true : false;
    }

    /**
     * Clear exchange rates
     *
     * @return integer
     */
    protected function clearExchangeRates()
    {
        $delete = $this->delete()->from('payment_exchange_rate');

        $statement = $this->prepareStatementForSqlObject($delete);
        $result = $statement->execute();
        $this->removeExchangeRatesCache();

        return $result->count() ? true : false;
    }

    /**
     * Clean shopping cart
     *
     * @return boolean
     */
    protected function cleanShoppingCart()
    {
        $delete = $this->delete()->from('payment_shopping_cart');
        $statement = $this->prepareStatementForSqlObject($delete);
        $result = $statement->execute();

        return $result->count() ? true : false;
    }

    /**
     * Delete currency
     *
     * @param integer $currencyId
     * @return boolean|string
     */
    public function deleteCurrency($currencyId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('payment_currency')
                ->where([
                    'id' => $currencyId
                ])
                ->where([
                    new NotInPredicate('primary_currency', [self::PRIMARY_CURRENCY])
                ]);

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
            // fire the delete payment currency event
            PaymentEvent::fireDeletePaymentCurrencyEvent($currencyId);
            return true;
        }

        return false;
    }

    /**
     * Get currencies count
     *
     * @return integer
     */
    public function getCurrenciesCount()
    {
        $select = $this->select();
        $select->from('payment_currency')
            ->columns([
               'count' => new Expression('count(*)')
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current()['count'];
    }

    /**
     * Edit currency
     *
     * @param array $oldCurrencyInfo
     *      string code
     *      sting name
     *      integer primary_currency
     * @param array $currencyInfo
     *      string code
     *      sting name
     *      integer primary_currency
     * @return boolean|string
     */
    public function editCurrency(array $oldCurrencyInfo, array $currencyInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            if (empty($currencyInfo['primary_currency'])) {
                $currencyInfo['primary_currency'] = $oldCurrencyInfo['primary_currency'];
            }

            $update = $this->update()
                ->table('payment_currency')
                ->set($currencyInfo)
                ->where([
                    'id' => $oldCurrencyInfo['id']
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // skip the previously activated primary currency
            if ((int) $currencyInfo['primary_currency'] == self::PRIMARY_CURRENCY &&
                        $oldCurrencyInfo['primary_currency'] == self::NOT_PRIMARY_CURRENCY) {

                $this->skippActivatedPrimaryCurrency($oldCurrencyInfo['id']);
                $this->clearExchangeRates();
                $this->cleanShoppingCart();
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit payment currency event
        PaymentEvent::fireEditPaymentCurrencyEvent($oldCurrencyInfo['id']);
        return true;
    }

    /**
     * Get coupons
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string slug
     *      integer discount
     *      integer used
     *      integer start
     *      integer end
     * @return Paginator
     */
    public function getCoupons($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'slug',
            'discount',
            'used',
            'start',
            'end'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from('payment_discount_cupon')
            ->columns([
                'id',
                'slug',
                'discount',
                'used',
                'start' => 'date_start',
                'end' => 'date_end'
            ])
            ->order($orderBy . ' ' . $orderType);

        // filter by a slug
        if (!empty($filters['slug'])) {
            $select->where([
                'slug' => $filters['slug']
            ]);
        }

        // filter by a discount
        if (!empty($filters['discount'])) {
            $select->where([
                'discount' => $filters['discount']
            ]);
        }

        // filter by a status
        if (isset($filters['used']) && $filters['used'] != null) {
            $select->where([
                'used' => ((int) $filters['used'] == self::COUPON_USED ? $filters['used'] : self::COUPON_NOT_USED)
            ]);
        }

        // filter by an activation date
        if (!empty($filters['start'])) {
            $select->where([
                'date_start' => $filters['start']
            ]);
        }

        // filter by a deactivation date
        if (!empty($filters['end'])) {
            $select->where([
                'date_end' => $filters['end']
            ]);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Delete coupon
     *
     * @param integer $couponId
     * @return boolean|string
     */
    public function deleteCoupon($couponId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('payment_discount_cupon')
                ->where([
                    'id' => $couponId
                ]);

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
            // fire the  delete discount coupon event
            PaymentEvent::fireDeleteDiscountCouponEvent($couponId);
            return true;
        }

        return false;
    }

    /**
     * Add new coupon
     *
     * @param array $couponInfo
     *      integer discount
     *      integer date_start
     *      integer date_end
     * @return integer|string
     */
    public function addCoupon(array $couponInfo)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            if (!$couponInfo['date_start']) {
                $couponInfo['date_start'] = null;
            }

            if (!$couponInfo['date_end']) {
                $couponInfo['date_end'] = null;
            }

            $insert = $this->insert()
                ->into('payment_discount_cupon')
                ->values(array_merge($couponInfo, [
                    'used' => self::COUPON_NOT_USED
                ]));

            $statement = $this->prepareStatementForSqlObject($insert);
            $result = $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            // generate a random slug
            $update = $this->update()
                ->table('payment_discount_cupon')
                ->set([
                    'slug' => strtoupper($this->generateSlug($insertId, $this->
                            generateRandString(self::COUPON_MIN_SLUG_LENGTH, self::ALLOWED_SLUG_CHARS), 'payment_discount_cupon', 'id'))
                ])
                ->where([
                    'id' => $insertId
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the add discount coupon event
        PaymentEvent::fireAddDiscountCouponEvent($insertId);
        return $insertId;
    }

    /**
     * Edit the coupon
     *
     * @param integer $id
     * @param array $couponInfo
     *      integer discount
     *      integer date_start
     *      integer date_end
     * @return boolean|string
     */
    public function editCoupon($id, array $couponInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            if (!$couponInfo['date_start']) {
                $couponInfo['date_start'] = null;
            }

            if (!$couponInfo['date_end']) {
                $couponInfo['date_end'] = null;
            }

            $update = $this->update()
                ->table('payment_discount_cupon')
                ->set($couponInfo)
                ->where([
                    'id' => $id
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

        // fire the edit discount coupon event
        PaymentEvent::fireEditDiscountCouponEvent($id);
        return true;
    }

    /**
     * Edit exchange rates
     *
     * @param array $exchangeRatesInfo
     *      integer id
     *      string code
     *      sting name
     *      float rate
     * @param array $exchangeRates
     *      float rate
     * @param integer $currencyId
     * @return boolean|string
     */
    public function editExchangeRates(array $exchangeRatesInfo, array $exchangeRates, $currencyId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // delete old rates
            $this->clearExchangeRates();

            // insert new rates
            foreach ($exchangeRates as $code => $rate) {
                // skip empty values
                if (!(float) $rate) {
                    continue;
                }

                $insert = $this->insert()
                    ->into('payment_exchange_rate')
                    ->values([
                        'rate' => $rate,
                        'currency' => $exchangeRatesInfo[$code]['id']
                    ]);

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit exchange rates event
        PaymentEvent::fireEditExchangeRatesEvent($currencyId);
        return true;
    }
}