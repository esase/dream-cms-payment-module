<?php
namespace Payment\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Payment\Model\PaymentBase as PaymentBaseModel;

class PaymentTransactionFilter extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'filter';

    /**
     * Form method
     * @var string
     */
    protected $method = 'get';

    /**
     * List of not validated elements
     * @var array
     */
    protected $notValidatedElements = ['submit'];

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'slug' => [
            'name' => 'slug',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Code'
        ],
        'paid' => [
            'name' => 'paid',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Paid',
            'values' => [
                PaymentBaseModel::TRANSACTION_PAID  => 'Yes',
                PaymentBaseModel::TRANSACTION_NOT_PAID => 'No'
            ]
        ],
        'email' => [
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Email'
        ],
        'date' => [
            'name' => 'date',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Date'
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search',
        ]
    ];
}