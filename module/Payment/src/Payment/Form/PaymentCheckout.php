<?php

namespace Payment\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;

class PaymentCheckout extends ApplicationAbstractCustomForm 
{
    /**
     * Comments string length
     */
    const COMMENTS_MAX_LENGTH = 65535;

    /**
     * First name string length
     */
    const FIRST_NAME_MAX_LENGTH = 100;

    /**
     * Last name string length
     */
    const LAST_NAME_MAX_LENGTH = 100;

    /**
     * Email string length
     */
    const EMAIL_MAX_LENGTH = 50;

    /**
     * Phone string length
     */
    const PHONE_MAX_LENGTH = 50;

    /**
     * Address string length
     */
    const ADDRESS_MAX_LENGTH = 100;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'checkout';

    /**
     * Payments types
     * @var array
     */
    protected $paymentsTypes = [];

    /**
     * Hide payment type
     * @var boolean
     */
    protected $hidePaymentType = false;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'payment_type' => [
            'name' => 'payment_type',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Payment type',
            'required' => true,
            'category' => 'Order information'
        ],
        'comments' => [
            'name' => 'comments',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT_AREA,
            'label' => 'Comments',
            'required' => false,
            'category' => 'Order information',
            'max_length' => self::COMMENTS_MAX_LENGTH
        ],
        'first_name' => [
            'name' => 'first_name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'First Name',
            'required' => true,
            'category' => 'Delivery details',
            'max_length' => self::FIRST_NAME_MAX_LENGTH
        ],
        'last_name' => [
            'name' => 'last_name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Last Name',
            'required' => true,
            'category' => 'Delivery details',
            'max_length' => self::LAST_NAME_MAX_LENGTH
        ],
        'email' => array(
            'name' => 'email',
            'type' => ApplicationCustomFormBuilder::FIELD_EMAIL,
            'label' => 'Email',
            'required' => true,
            'category' => 'Delivery details',
            'max_length' => self::EMAIL_MAX_LENGTH
        ),
        'phone' => [
            'name' => 'phone',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Phone',
            'required' => true,
            'category' => 'Delivery details',
            'max_length' => self::PHONE_MAX_LENGTH
        ],
        'address' => [
            'name' => 'address',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Address',
            'required' => false,
            'category' => 'Delivery details',
            'max_length' => self::ADDRESS_MAX_LENGTH
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ]
    ];

    /**
     * Get form instance
     *
     * @return Application\Form\ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // hide a payment type field
            if ($this->hidePaymentType) {
                unset($this->formElements['payment_type']);    
            }else {
                // fill the form with default values
                $this->formElements['payment_type']['values'] = $this->paymentsTypes;  
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set payments types
     *
     * @param array $paymentsTypes
     * @return Payment\Form\PaymentCheckout fluent interface
     */
    public function setPaymentsTypes(array $paymentsTypes)
    {
        $this->paymentsTypes = $paymentsTypes;
        return $this;
    }

    /**
     * Hide payment type
     *
     * @param boolean $hide
     * @return Payment\Form\PaymentCheckout fluent interface
     */
    public function hidePaymentType($hide)
    {
        $this->hidePaymentType = $hide;
        return $this;
    }
}