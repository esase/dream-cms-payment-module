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

            if (!$currencyInfo['primary_currency']) {
                $currencyInfo['primary_currency'] = self::NOT_PRIMARY_CURRENCY;
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
}