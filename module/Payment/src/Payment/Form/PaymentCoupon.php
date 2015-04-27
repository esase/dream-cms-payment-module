<?php
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
     * @var string
     */
    protected $formName = 'coupon';

    /**
     * Discount
     * @var integer
     */
    protected $discount;

    /**
     * Date start
     * @var integer
     */
    protected $dateStart;

    /**
     * Date end
     * @var integer
     */
    protected $dateEnd;

    /**
     * Form elements
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
     * @return PaymentCoupon fluent interface
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
     * @return PaymentCoupon fluent interface
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
     * @return PaymentCoupon fluent interface
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