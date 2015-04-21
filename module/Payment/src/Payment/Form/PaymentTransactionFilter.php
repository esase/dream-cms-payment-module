<?php
namespace Payment\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Payment\Model\PaymentBase as PaymentModelBase;

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
     * Model
     * @var object
     */
    protected $model;

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
                PaymentModelBase::TRANSACTION_PAID  => 'Yes',
                PaymentModelBase::TRANSACTION_NOT_PAID => 'No'
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