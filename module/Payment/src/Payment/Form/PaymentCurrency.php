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
use Payment\Model\PaymentAdministration as PaymentAdministrationModel;

class PaymentCurrency extends ApplicationAbstractCustomForm
{
    /**
     * Code max string length
     */
    const CODE_MAX_LENGTH = 3;

    /**
     * Name max string length
     */
    const NAME_MAX_LENGTH = 50;

    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'currency';

    /**
     * Model instance
     *
     * @var \Payment\Model\PaymentAdministration
     */
    protected $model;

    /**
     * Currency code id
     *
     * @var integer
     */
    protected $currencyCodeId;

    /**
     * The primary site currency enabled flag
     *
     * @var boolean
     */
    protected $isEnabledPrimaryCurrency = true;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'code' => [
            'name' => 'code',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Currency code',
            'required' => true,
            'category' => 'General info',
            'max_length' => self::CODE_MAX_LENGTH
        ],
        'name' => [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Currency name',
            'required' => true,
            'category' => 'General info',
            'max_length' => self::NAME_MAX_LENGTH
        ],
        'primary_currency' => [
            'name' => 'primary_currency',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Primary site currency',
            'required' => false,
            'category' => 'General info'
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
        // get the form builder
        if (!$this->form) {

            // delete the "primary_currency" field from the form
            if (!$this->isEnabledPrimaryCurrency) {
                unset($this->formElements['primary_currency']);
            }

            // add extra filters
            $this->formElements['code']['filters'] = [
                [
                    'name' => 'stringtoupper'
                ]
            ];

            // add extra validators
            $this->formElements['code']['validators'] = [
                [
                    'name' => 'regex',
                    'options' => [
                        'pattern' => '/^[a-z]{3}$/i',
                        'message' => 'Length of the currency code must be 3 characters and contain only Latin letters'
                    ]
                ],
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateCurrencyCode'],
                        'message' => 'Currency code already used'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
 
    /**
     * Set a model
     *
     * @param \Payment\Model\PaymentAdministration $model
     * @return \Payment\Form\PaymentCurrency
     */
    public function setModel(PaymentAdministrationModel $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set a currency code id
     *
     * @param integer $currencyCodeId
     * @return \Payment\Form\PaymentCurrency
     */
    public function setCurrencyCodeId($currencyCodeId)
    {
        $this->currencyCodeId = $currencyCodeId;

        return $this;
    }

    /**
     * Enable or disable the primary currency option in the form
     *
     * @param boolean $enable
     * @return \Payment\Form\PaymentCurrency
     */
    public function enabledPrimaryCurrency($enable)
    {
        $this->isEnabledPrimaryCurrency = $enable;

        return $this;
    }

    /**
     * Validate the currency code
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateCurrencyCode($value, array $context = [])
    {
        return $this->model->isCurrencyCodeFree($value, $this->currencyCodeId);
    }
}