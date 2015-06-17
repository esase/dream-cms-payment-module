<?php

namespace Payment\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Payment\Model\PaymentWidget as PaymentWidgetModel;

class PaymentDiscountForm extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'discount';

    /**
     * Model instance
     * @var Payment\Model\PaymentWidget  
     */
    protected $model;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'coupon' => [
            'name' => 'coupon',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Code',
            'required' => true
        ]
    ];

    /**
     * Get form instance
     *
     * @return Application\Form\ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get the form builder
        if (!$this->form) {
            // add extra validators
            $this->formElements['coupon']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateCoupon'],
                        'message' => 'The discount code not found or not activated'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set the model
     *
     * @param Payment\Model\PaymentWidget $model
     * @return Payment\Form\PaymentDiscountForm fluent interface
     */
    public function setModel(PaymentWidgetModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Validate the coupon
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateCoupon($value, array $context = [])
    {
        return $this->model->getActiveCouponInfo($value) ? true : false;
    }
}