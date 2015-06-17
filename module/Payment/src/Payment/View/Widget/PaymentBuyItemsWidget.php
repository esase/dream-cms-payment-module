<?php

namespace Payment\View\Widget;

class PaymentBuyItemsWidget extends PaymentAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        // get transaction info
        $transactionInfo = $this->
                getModel()->getTransactionInfo($this->getSlug(), true, 'slug');

        // get payment types
        $paymentsTypes = $this->getModel()->getPaymentsTypes(false, true);

        // get a default payment type
        $paymentName = $this->getRequest()->getQuery('payment_name');
        $currentPayment = $transactionInfo['payment_name'];

        if ($paymentName && array_key_exists($paymentName, $paymentsTypes)) {
            $currentPayment = $paymentName;
        }

        $currentPayment = mb_strtolower($currentPayment);

        // process payments
        $processedPayments = [];
        foreach ($paymentsTypes as $paymentName => $paymentInfo) {
            $processedPayments[$paymentName] = $paymentInfo['description'];
        }
        
        $paymentType = $this->getServiceLocator()
            ->get('Payment\Type\PaymentTypeManager')
            ->getInstance($paymentsTypes[$currentPayment]['handler']);

        return $this->getView()->partial('payment/payment-type/' . $currentPayment, [
            'transaction' => $transactionInfo,
            'payments' => $processedPayments,
            'current_payment' => $currentPayment,
            'amount' => $transactionInfo['amount'],
            'payment_options' => $paymentType->getPaymentOptions($transactionInfo['amount'], $transactionInfo),
            'payment_url' => $paymentType->getPaymentUrl()
        ]);
    }
}