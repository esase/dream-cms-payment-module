<?php
namespace Payment\Model;

use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;

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
}