<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * CHIP Complete Purchase Response
 *
 * Handles responses from CHIP purchase completion API calls.
 * Provides transaction status, payment details, and completion information.
 *
 * @see https://github.com/CHIPAsia/chip-php-sdk
 */
class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return isset($this->data['status']) && $this->data['status'] === 'paid';
    }

    public function isPending()
    {
        return isset($this->data['status']) && in_array($this->data['status'], ['pending', 'processing', 'created']);
    }

    public function isCancelled()
    {
        return isset($this->data['status']) && in_array($this->data['status'], ['cancelled', 'expired', 'failed']);
    }

    /**
     * Check if the transaction failed
     */
    public function isFailed()
    {
        return isset($this->data['status']) && in_array($this->data['status'], ['failed', 'error']);
    }

    /**
     * Check if the transaction is expired
     */
    public function isExpired()
    {
        return isset($this->data['status']) && $this->data['status'] === 'expired';
    }

    public function getTransactionReference()
    {
        return isset($this->data['id']) ? $this->data['id'] : null;
    }

    public function getTransactionId()
    {
        return isset($this->data['reference']) ? $this->data['reference'] : null;
    }

    public function getMessage()
    {
        // Handle various message formats from CHIP API
        if (isset($this->data['errors'])) {
            if (is_array($this->data['errors'])) {
                $messages = [];
                foreach ($this->data['errors'] as $field => $errors) {
                    if (is_array($errors)) {
                        $messages[] = $field . ': ' . implode(', ', $errors);
                    } else {
                        $messages[] = $field . ': ' . $errors;
                    }
                }
                return implode('; ', $messages);
            }
            return $this->data['errors'];
        }

        if (isset($this->data['error'])) {
            return is_array($this->data['error']) ? implode(' ', $this->data['error']) : $this->data['error'];
        }

        if (isset($this->data['status_details'])) {
            return $this->data['status_details'];
        }

        if (isset($this->data['detail'])) {
            return $this->data['detail'];
        }

        if (isset($this->data['message'])) {
            return $this->data['message'];
        }

        // Provide status-based messages
        $status = $this->getCode();
        switch ($status) {
            case 'paid':
                return 'Payment completed successfully';
            case 'pending':
            case 'processing':
            case 'created':
                return 'Payment is pending completion';
            case 'cancelled':
                return 'Payment was cancelled';
            case 'expired':
                return 'Payment has expired';
            case 'failed':
            case 'error':
                return 'Payment failed';
            default:
                return null;
        }
    }

    public function getCode()
    {
        return isset($this->data['status']) ? $this->data['status'] : null;
    }

    public function getAmount()
    {
        // CHIP amounts are in the smallest currency unit (cents)
        if (isset($this->data['purchase']['total_override'])) {
            return $this->data['purchase']['total_override'] / 100;
        }
        
        if (isset($this->data['purchase']['total'])) {
            return $this->data['purchase']['total'] / 100;
        }

        return null;
    }

    /**
     * Get the raw amount in cents
     */
    public function getAmountInteger()
    {
        if (isset($this->data['purchase']['total_override'])) {
            return (int) $this->data['purchase']['total_override'];
        }
        
        if (isset($this->data['purchase']['total'])) {
            return (int) $this->data['purchase']['total'];
        }

        return null;
    }

    public function getCurrency()
    {
        return isset($this->data['purchase']['currency']) ? $this->data['purchase']['currency'] : null;
    }

    public function getPaymentMethod()
    {
        return isset($this->data['payment_method_type']) ? $this->data['payment_method_type'] : null;
    }

    public function getPaymentDate()
    {
        return isset($this->data['paid_at']) ? $this->data['paid_at'] : null;
    }

    public function getReceiptUrl()
    {
        return isset($this->data['receipt_url']) ? $this->data['receipt_url'] : null;
    }

    /**
     * Get purchase details
     */
    public function getPurchaseDetails()
    {
        return isset($this->data['purchase']) ? $this->data['purchase'] : null;
    }

    /**
     * Get client details
     */
    public function getClientDetails()
    {
        return isset($this->data['client']) ? $this->data['client'] : null;
    }

    /**
     * Get purchase ID (same as transaction reference)
     */
    public function getPurchaseId()
    {
        return $this->getTransactionReference();
    }

    /**
     * Get purchase status
     */
    public function getStatus()
    {
        return $this->getCode();
    }

    /**
     * Get payment gateway transaction ID
     */
    public function getGatewayTransactionId()
    {
        return isset($this->data['transaction_data']['id']) ? $this->data['transaction_data']['id'] : null;
    }

    /**
     * Get payment gateway reference
     */
    public function getGatewayReference()
    {
        return isset($this->data['transaction_data']['reference']) ? $this->data['transaction_data']['reference'] : null;
    }

    /**
     * Get all response data
     */
    public function getData()
    {
        return $this->data;
    }

    public function __construct($request, $data)
    {
        parent::__construct($request, $data);
        
        // Handle both string and array data
        if (is_string($data)) {
            $this->data = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->data = ['error' => 'Invalid JSON response: ' . $data];
            }
        } else {
            $this->data = $data;
        }
        
        // Ensure data is an array
        if (!is_array($this->data)) {
            $this->data = ['error' => 'Invalid response format'];
        }
    }
}