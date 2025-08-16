<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * Webhook response for Chip-in Asia
 */
class WebhookResponse extends AbstractResponse
{
    public function __construct(RequestInterface $request, $data)
    {
        parent::__construct($request, $data);
    }

    /**
     * Webhook processing is always successful if we reach this point
     */
    public function isSuccessful()
    {
        return true;
    }

    /**
     * Get the purchase ID from webhook data
     */
    public function getTransactionReference()
    {
        return $this->data['id'] ?? null;
    }

    /**
     * Get the payment status
     */
    public function getStatus()
    {
        return $this->data['status'] ?? null;
    }

    /**
     * Check if payment is successful
     */
    public function isPaymentSuccessful()
    {
        return in_array($this->getStatus(), ['paid', 'completed', 'success']);
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending()
    {
        return in_array($this->getStatus(), ['pending', 'processing']);
    }

    /**
     * Check if payment failed
     */
    public function isPaymentFailed()
    {
        return in_array($this->getStatus(), ['failed', 'cancelled', 'expired']);
    }

    /**
     * Get the payment amount
     */
    public function getAmount()
    {
        if (isset($this->data['purchase']['total'])) {
            return number_format($this->data['purchase']['total'] / 100, 2, '.', '');
        }
        return null;
    }

    /**
     * Get the payment currency
     */
    public function getCurrency()
    {
        return $this->data['purchase']['currency'] ?? null;
    }

    /**
     * Get the payment method used
     */
    public function getPaymentMethod()
    {
        return $this->data['payment_method'] ?? null;
    }

    /**
     * Get the payment date
     */
    public function getPaymentDate()
    {
        if (isset($this->data['paid_at'])) {
            return new \DateTime($this->data['paid_at']);
        }
        return null;
    }

    /**
     * Get the transaction ID (your reference)
     */
    public function getTransactionId()
    {
        return $this->data['reference'] ?? null;
    }

    /**
     * Get customer information
     */
    public function getCustomer()
    {
        return $this->data['client'] ?? null;
    }

    /**
     * Get all webhook data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get status message
     */
    public function getMessage()
    {
        $status = $this->getStatus();
        $messages = [
            'paid' => 'Payment completed successfully',
            'completed' => 'Payment completed successfully',
            'success' => 'Payment completed successfully',
            'pending' => 'Payment is pending',
            'processing' => 'Payment is being processed',
            'failed' => 'Payment failed',
            'cancelled' => 'Payment was cancelled',
            'expired' => 'Payment expired'
        ];

        return $messages[$status] ?? "Payment status: {$status}";
    }
}