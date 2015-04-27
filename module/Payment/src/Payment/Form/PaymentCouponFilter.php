<?php
namespace Payment\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Payment\Model\PaymentBase as PaymentModelBase;

class PaymentCouponFilter extends ApplicationAbstractCustomForm 
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
        'discount' => [
            'name' => 'discount',
            'type' => ApplicationCustomFormBuilder::FIELD_FLOAT,
            'label' => 'Discount'
        ],
        'used' => [
            'name' => 'used',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Used',
            'values' => [
                PaymentModelBase::COUPON_USED  => 'Yes',
                PaymentModelBase::COUPON_NOT_USED => 'No'
            ]
        ],
        'start' => [
            'name' => 'start',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Activation date'
        ],
        'end' => [
            'name' => 'end',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Deactivation date'
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];
}