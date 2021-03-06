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
use Payment\Model\PaymentWidget as PaymentWidgetModel;

class PaymentDiscountForm extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     *
     * @var string
     */
    protected $formName = 'discount';

    /**
     * Model instance
     *
     * @var \Payment\Model\PaymentWidget
     */
    protected $model;

    /**
     * Form elements
     *
     * @var array
     */
    protected $formElements = [
        'csrf' => [
            'name' => 'csrf',
            'type' => ApplicationCustomFormBuilder::FIELD_CSRF
        ],
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
     * @return \Application\Form\ApplicationCustomFormBuilder
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
     * @param \Payment\Model\PaymentWidget $model
     * @return \Payment\Form\PaymentDiscountForm
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