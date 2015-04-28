<?php
namespace Payment\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;

class PaymentExchnageRate extends ApplicationAbstractCustomForm 
{
    /**
     * Rate max string length
     */
    const RATE_MAX_LENGTH = 11;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'exchnage-rate';

    /**
     * Exchange rates
     * @var array
     */
    protected $exchangeRates;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ]
    ];

    /**
     * Get form instance
     *
     * @return ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get the form builder
        if (!$this->form) {
            // process exchange rates
            foreach ($this->exchangeRates as $rate) {
                $this->formElements = array_merge([[
                    'name' => $rate['code'],
                    'type' => ApplicationCustomFormBuilder::FIELD_FLOAT,
                    'label' => $rate['name'],
                    'value' => $rate['rate'],
                    'category' => 'General info',
                    'max_length' => self::RATE_MAX_LENGTH
                ]], $this->formElements);
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set exchange rates
     *
     * @param array $exchangeRates
     * @return object fluent interface
     */
    public function setExchangeRates(array $exchangeRates)
    {
        $this->exchangeRates = $exchangeRates;
        return $this;
    }
}