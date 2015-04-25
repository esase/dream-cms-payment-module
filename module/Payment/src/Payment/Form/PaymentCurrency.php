<?php
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
     * @var string
     */
    protected $formName = 'currency';

    /**
     * Model instance
     * @var PaymentAdministrationModel  
     */
    protected $model;

    /**
     * Currency code id
     * @var integer
     */
    protected $currencyCodeId;

    /**
     * The primary site currency enabled flag
     * @var boolean
     */
    protected $isEnabledPrimaryCurrency = true;

    /**
     * Form elements
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
     * @return object
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
     * @param PaymentAdministrationModel $model
     * @return object fluent interface
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
     * @return object fluent interface
     */
    public function setCurrencyCodeId($currencyCodeId)
    {
        $this->currencyCodeId = $currencyCodeId;
        return $this;
    }

    /**
     * Enable or diasble the primary currecny option in the form
     *
     * @param boolean $enable
     * @return object fluent interface
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