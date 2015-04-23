<?php
namespace Payment\Model;

use Payment\Event\PaymentEvent;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Application\Model\ApplicationAbstractBase;
use Zend\Db\Sql\Expression;
use Exception;

class PaymentBase extends ApplicationAbstractBase
{
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
     * Item deleted flag
     */ 
    const ITEM_DELETED = 1;

    /**
     * Item not deleted flag
     */ 
    const ITEM_NOT_DELETED = 0;

    /**
     * Item is not active flag
     */ 
    const ITEM_NOT_ACTIVE = 0;

    /**
     * Item is not available flag
     */ 
    const ITEM_NOT_AVAILABLE = 0;

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
                'deleted',
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
}