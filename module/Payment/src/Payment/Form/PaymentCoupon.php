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
use Localization\Utility\LocalizationLocale as LocalizationLocaleUtility;

class PaymentCoupon extends ApplicationAbstractCustomForm
{
    /**
     * Min discount
     */
    const MIN_DISCOUNT = 0;

    /**
     * Max discount
     */
    const MAX_DISCOUNT = 100;

    /**
     * Discount max string length
     */
    const DISCOUNT_MAX_LENGTH = 11;

    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'coupon';

    /**
     * Discount
     *
     * @var integer
     */
    protected $discount;

    /**
     * Date start
     *
     * @var integer
     */
    protected $dateStart;

    /**
     * Date end
     *
     * @var integer
     */
    protected $dateEnd;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'discount' => [
            'name' => 'discount',
            'type' => ApplicationCustomFormBuilder::FIELD_FLOAT,
            'label' => 'Discount',
            'required' => true,
            'category' => 'General info',
            'description' => 'Percentage ratio',
            'max_length' => self::DISCOUNT_MAX_LENGTH
        ],
        'date_start' => [
            'name' => 'date_start',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Activation date',
            'category' => 'Miscellaneous info'
        ],
        'date_end' => [
            'name' => 'date_end',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Deactivation date',
            'category' => 'Miscellaneous info'
        ],
        'csrf' => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
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
            // fill the form with default values
            $this->formElements['discount']['value'] = $this->discount;
            $this->formElements['date_start']['value'] = $this->dateStart;
            $this->formElements['date_end']['value'] = $this->dateEnd;

            // add extra validators
            $this->formElements['discount']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateDiscount'],
                        'message' => 'The discount must be more than 0 and less or equal 100'
                    ]
                ]
            ];

            $this->formElements['date_end']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateDateEnd'],
                        'message' => 'The deactivation date must be more than activation date'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set a discount
     *
     * @param integer $discount
     * @return \Payment\Form\PaymentCoupon
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Set a date start
     *
     * @param integer $dateStart
     * @return \Payment\Form\PaymentCoupon
     */
    public function setDateStart($dateStart)
    {
        if ((int) $dateStart) {
            $this->dateStart = $dateStart;
        }

        return $this;
    }

    /**
     * Set a date end
     *
     * @param integer $dateEnd
     * @return \Payment\Form\PaymentCoupon
     */
    public function setDateEnd($dateEnd)
    {
        if ((int) $dateEnd) {
            $this->dateEnd = $dateEnd;
        }

        return $this;
    }

    /**
     * Validate the discount
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateDiscount($value, array $context = [])
    {
        $value = LocalizationLocaleUtility::convertFromLocalizedValue($value, 'float');

        return $value > self::MIN_DISCOUNT && $value <= self::MAX_DISCOUNT;
    }

    /**
     * Validate the date end
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateDateEnd($value, array $context = [])
    {
        // compare the date start and date end 
        if (!empty($context['date_start'])) {
            return LocalizationLocaleUtility::convertFromLocalizedValue($value,
                    'date_unixtime') > LocalizationLocaleUtility::convertFromLocalizedValue($context['date_start'], 'date_unixtime');
        }

        return true;
    }
 }