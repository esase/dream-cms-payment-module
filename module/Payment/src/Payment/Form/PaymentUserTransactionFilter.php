<?php

namespace Payment\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;
use Payment\Model\PaymentBase as PaymentBaseModel;

class PaymentUserTransactionFilter extends ApplicationAbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'transaction-filter';

    /**
     * Form method
     * @var string
     */
    protected $method = 'get';

    /**
     * List of not validated elements
     * @var array
     */
    protected $notValidatedElements = ['submit'];

    /**
     * Fields postfix
     * @var string
     */
    protected $postfix;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'slug' => [
            'name' => 'filter_slug',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Code'
        ],
        'paid' => [
            'name' => 'filter_paid',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Paid',
            'values' => [
                PaymentBaseModel::TRANSACTION_PAID  => 'Yes',
                PaymentBaseModel::TRANSACTION_NOT_PAID => 'No'
            ]
        ],
        'date' => [
            'name' => 'filter_date',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'Date'
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
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
            // add a postfix to the end of the all fields
            if ($this->fieldsPostfix) {
                foreach($this->formElements as &$options) {
                    $options['name'] = $options['name'] . $this->fieldsPostfix;
                }
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set fields postfix
     *
     * @param string $postfix
     * @return Payment\Form\PaymentUserTransactionFilter
     */
    public function setFieldsPostfix($postfix)
    {
        $this->fieldsPostfix = $postfix;
        return $this;
    }

    /**
     * Get form data
     *
     * @return array
     */
    public function getData()
    {
        $data = $this->getForm()->getData();

        if ($this->fieldsPostfix) {
            $processedData = [];
            $postfixLength = strlen($this->fieldsPostfix);

            foreach($data as $name => $value) {
                $processedData[substr($name, 0, -$postfixLength)] = $value; 
            }

            $data = $processedData;
        }

        return $data;
    }
}