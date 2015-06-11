<?php

namespace Payment\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Zend\Form\Exception\InvalidArgumentException;

class PaymentShoppingCart extends ApplicationAbstractCustomForm 
{
    /**
     * Count string length
     */
    const COUNT_MAX_LENGTH = 4;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'shopping-cart';

    /**
     * Object Id
     * @var integer
     */
    protected $objectId;

    /**
     * Module name
     * @var string
     */
    protected $moduleName;

    /**
     * Hide count field
     * @var boolean
     */
    protected $hideCountField;

    /**
     * Discount
     * @var integer
     */
    protected $discount;

    /**
     * Tariffs
     * @var array
     */
    protected $tariffs;

    /**
     * Count limit
     * @var integer
     */
    protected $countLimit;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'count' => [
            'name' => 'count',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Item count',
            'required' => true,
            'description' => '',
            'description_params' => [],
            'max_length' => self::COUNT_MAX_LENGTH
        ],
        'cost' => [
            'name' => 'cost',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Choose the tariff',
            'required' => true,
        ],
        'discount' => [
            'name' => 'discount',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Use discount',
            'description' => 'Item discount info'
        ],
        'object_id' => [
            'name' => 'object_id',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN,
            'required' => true
        ],
        'module' => [
            'name' => 'module',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN,
            'required' => true
        ],
        'validate' => [
            'name' => 'validate',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN,
            'value' => 1
        ]
    ];

    /**
     * Extra form elements
     * @var array
     */
    protected $extraFormElements = [];

    /**
     * Get extra options
     *
     * @param array $formData
     * @param boolean $skipEmptyValues
     * @return array
     */
    public function getExtraOptions(array $formData, $skipEmptyValues = true)
    {
        $extraData = [];
        foreach ($formData as $name => $value) {
            if (array_key_exists($name, $this->formElements) || ($skipEmptyValues && !$value)) {
                continue;
            }

            $extraData[$name] = $value;
        }

        return $extraData;
    }

    /**
     * Get form instance
     *
     * @return Application\Form\ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get the form builder
        if (!$this->form) {
            // add extra settings for "cost" field
            if ($this->tariffs) {
                $this->formElements['cost']['values'] = $this->tariffs;
            }
            else {
                unset($this->formElements['cost']);
            }

            // add extra validators for "count" field
            if (!$this->hideCountField) {
                if ($this->countLimit) {
                    $this->formElements['count']['description'] = 'Max items count description';
                    $this->formElements['count']['description_params'] = [
                        $this->countLimit
                    ];
                }

                $this->formElements['count']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validateItemCount'],
                            'message' => 'Value should be greater than 0'
                        ]
                    ],
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validateItemMaxCount'],
                            'message' => sprintf($this->translator->translate('Item count must be less or equal %d'), $this->countLimit)
                        ]
                    ]
                ];

            }
            else {
                unset($this->formElements['count']);
            }

            // add extra settings for "discount" field
            if ($this->discount) {
                $this->formElements['discount']['description_params'] = [
                    ServiceLocatorService::getServiceLocator()->
                            get('viewHelperManager')->get('paymentProcessCost')->__invoke($this->discount)
                ];
            }
            else {
                unset($this->formElements['discount']);
            }

            $formElements = $this->formElements;

            // add extra form elements
            if ($this->extraFormElements) {
                $formElements = array_merge($formElements, $this->extraFormElements);
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName, $formElements,
                    $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set count limit
     *
     * @param integer $limit
     * @return Payment\Form\PaymentShoppingCart fluent interface
     */
    public function setCountLimit($limit)
    {
        $this->countLimit = $limit;
        return $this;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     * @return Payment\Form\PaymentShoppingCart fluent interface
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
        return $this;
    }

    /**
     * Set tariffs
     *
     * @param array $tariffs
     * @throws Zend\Form\Exception\InvalidArgumentException
     * @return Payment\Form\PaymentShoppingCart fluent interface
     */
    public function setTariffs(array $tariffs)
    {
        if (null == ($this->tariffs = $tariffs)) {
            throw new InvalidArgumentException('Tariffs list must not be empty');    
        }

        return $this;
    }

    /**
     * Set extra options
     *
     * @param array $extraOptions
     * @return Payment\Form\PaymentShoppingCart fluent interface
     */
    public function setExtraOptions(array $extraOptions)
    {
        $this->extraFormElements = $extraOptions;
        return $this;
    }

    /**
     * Hide count field
     *
     * @param boolean $hide
     * @return Payment\Form\PaymentShoppingCart fluent interface
     */
    public function hideCountField($hide)
    {
        $this->hideCountField = $hide;
        return $this;
    }

    /**
     * Validate the item's count
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateItemCount($value, array $context = [])
    {
        return (int) $value > 0;
    }

    /**
     * Validate the item's max count
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateItemMaxCount($value, array $context = [])
    {
        return (int) $value <= $this->countLimit || !$this->countLimit;
    }
}