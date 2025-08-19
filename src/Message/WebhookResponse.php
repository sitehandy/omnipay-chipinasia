<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * CHIP Webhook Response
 * 
 * Handles webhook notification data from CHIP payment gateway.
 * Provides methods to extract transaction information, payment status,
 * and other relevant data from verified webhook payloads.
 * 
 * @package Omnipay\ChipInAsia\Message
 */
class WebhookResponse extends AbstractResponse
{
    public function __construct(RequestInterface $request, $data)
    {
        // Handle both array and string data
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }
        
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
        return in_array($this->getStatus(), ['pending', 'processing', 'created']);
    }

    /**
     * Check if payment failed
     */
    public function isPaymentFailed()
    {
        return in_array($this->getStatus(), ['failed', 'cancelled', 'expired']);
    }

    /**
     * Check if payment is cancelled
     */
    public function isPaymentCancelled()
    {
        return in_array($this->getStatus(), ['cancelled', 'canceled']);
    }

    /**
     * Check if payment is expired
     */
    public function isPaymentExpired()
    {
        return $this->getStatus() === 'expired';
    }

    /**
     * Get the payment amount (formatted)
     */
    public function getAmount()
    {
        $amount = $this->getAmountInteger();
        if ($amount !== null) {
            return number_format($amount / 100, 2, '.', '');
        }
        return null;
    }

    /**
     * Get the payment amount in cents/smallest currency unit
     */
    public function getAmountInteger()
    {
        // Try different possible amount fields
        if (isset($this->data['purchase']['total_override'])) {
            return (int) $this->data['purchase']['total_override'];
        }
        if (isset($this->data['purchase']['total'])) {
            return (int) $this->data['purchase']['total'];
        }
        if (isset($this->data['total'])) {
            return (int) $this->data['total'];
        }
        return null;
    }

    /**
     * Get the payment currency
     */
    public function getCurrency()
    {
        return $this->data['purchase']['currency'] ?? $this->data['currency'] ?? null;
    }

    /**
     * Get the payment method used
     */
    public function getPaymentMethod()
    {
        return $this->data['payment_method'] ?? $this->data['payment']['method'] ?? null;
    }

    /**
     * Get the payment date
     */
    public function getPaymentDate()
    {
        $dateFields = ['paid_at', 'completed_at', 'updated_at'];
        
        foreach ($dateFields as $field) {
            if (isset($this->data[$field])) {
                try {
                    return new \DateTime($this->data[$field]);
                } catch (\Exception $e) {
                    // Continue to next field if date parsing fails
                }
            }
        }
        
        return null;
    }

    /**
     * Get the purchase creation date
     */
    public function getCreatedDate()
    {
        if (isset($this->data['created_at'])) {
            try {
                return new \DateTime($this->data['created_at']);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get the transaction ID (your reference)
     */
    public function getTransactionId()
    {
        return $this->data['reference'] ?? $this->data['transaction_id'] ?? null;
    }

    /**
     * Get the purchase ID (CHIP's internal ID)
     */
    public function getPurchaseId()
    {
        return $this->data['id'] ?? null;
    }

    /**
     * Get customer information
     */
    public function getCustomer()
    {
        return $this->data['client'] ?? $this->data['customer'] ?? null;
    }

    /**
     * Get customer email
     */
    public function getCustomerEmail()
    {
        $client = $this->getCustomer();
        return $client['email'] ?? null;
    }

    /**
     * Get customer phone
     */
    public function getCustomerPhone()
    {
        $client = $this->getCustomer();
        return $client['phone'] ?? null;
    }

    /**
     * Get purchase details
     */
    public function getPurchaseDetails()
    {
        return $this->data['purchase'] ?? null;
    }

    /**
     * Get products from purchase
     */
    public function getProducts()
    {
        $purchase = $this->getPurchaseDetails();
        return $purchase['products'] ?? [];
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
        // Check for error messages first
        if (isset($this->data['errors']) && is_array($this->data['errors'])) {
            $errors = [];
            foreach ($this->data['errors'] as $error) {
                if (is_string($error)) {
                    $errors[] = $error;
                } elseif (is_array($error) && isset($error['detail'])) {
                    $errors[] = $error['detail'];
                } elseif (is_array($error) && isset($error['message'])) {
                    $errors[] = $error['message'];
                }
            }
            if (!empty($errors)) {
                return implode('; ', $errors);
            }
        }
        
        if (isset($this->data['error'])) {
            if (is_string($this->data['error'])) {
                return $this->data['error'];
            } elseif (is_array($this->data['error']) && isset($this->data['error']['message'])) {
                return $this->data['error']['message'];
            }
        }
        
        // Default status messages
        $status = $this->getStatus();
        $messages = [
            'paid' => 'Payment completed successfully',
            'completed' => 'Payment completed successfully', 
            'success' => 'Payment completed successfully',
            'pending' => 'Payment is pending',
            'processing' => 'Payment is being processed',
            'created' => 'Payment created and awaiting processing',
            'failed' => 'Payment failed',
            'cancelled' => 'Payment was cancelled',
            'canceled' => 'Payment was cancelled',
            'expired' => 'Payment expired'
        ];

        return $messages[$status] ?? "Payment status: {$status}";
    }
}