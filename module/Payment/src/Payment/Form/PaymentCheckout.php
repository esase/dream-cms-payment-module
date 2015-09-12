<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
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
     *
     * @var string
     */
    protected $formName = 'checkout';

    /**
     * Payments types
     *
     * @var array
     */
    protected $paymentsTypes = [];

    /**
     * Hide payment type
     *
     * @var boolean
     */
    protected $hidePaymentType = false;

    /**
     * Form elements
     *
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
     * @return \Application\Form\ApplicationCustomFormBuilder
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
     * @return \Payment\Form\PaymentCheckout
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
     * @return \Payment\Form\PaymentCheckout
     */
    public function hidePaymentType($hide)
    {
        $this->hidePaymentType = $hide;

        return $this;
    }
}