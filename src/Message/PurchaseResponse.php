<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * CHIP Purchase Response
 *
 * Handles responses from CHIP purchase creation API calls.
 * Follows CHIP SDK patterns for response parsing and redirect handling.
 *
 * @see https://github.com/CHIPAsia/chip-php-sdk
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful()
    {
        // Purchase creation is successful if we have a valid response with checkout URL
        // but we still need to redirect for payment completion
        return $this->isRedirect() && !$this->hasError();
    }

    public function isRedirect()
    {
        return !empty($this->getRedirectUrl());
    }

    /**
     * Check if response contains an error
     */
    public function hasError()
    {
        return isset($this->data['errors']) || 
               isset($this->data['error']) || 
               isset($this->data['__all__']) ||
               (isset($this->data['status']) && $this->data['status'] >= 400);
    }

    public function getTransactionReference()
    {
        return isset($this->data['id']) ? $this->data['id'] : null;
    }

    public function getRedirectUrl()
    {
        // Priority order based on CHIP SDK response structure
        if (!empty($this->data['checkout_url'])) {
            return $this->data['checkout_url'];
        }
        
        if (!empty($this->data['redirect_url'])) {
            return $this->data['redirect_url'];
        }
        
        if (!empty($this->data['payment_url'])) {
            return $this->data['payment_url'];
        }

        return null;
    }

    public function getRedirectMethod()
    {
        return 'GET';
    }

    public function getRedirectData()
    {
        return null;
    }

    public function getMessage()
    {
        // Handle various error message formats from CHIP API
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

        if (isset($this->data['__all__'])) {
            return is_array($this->data['__all__']) ? implode(' ', $this->data['__all__']) : $this->data['__all__'];
        }

        if (isset($this->data['detail'])) {
            return $this->data['detail'];
        }

        if (isset($this->data['message'])) {
            return $this->data['message'];
        }

        return null;
    }

    public function getCode()
    {
        return isset($this->data['status']) ? $this->data['status'] : null;
    }

    /**
     * Get purchase details from response
     */
    public function getPurchaseDetails()
    {
        return isset($this->data['purchase']) ? $this->data['purchase'] : null;
    }

    /**
     * Get client details from response
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
        return isset($this->data['status']) ? $this->data['status'] : null;
    }

    /**
     * Get purchase amount
     */
    public function getAmount()
    {
        if (isset($this->data['purchase']['total_override'])) {
            return $this->data['purchase']['total_override'];
        }
        
        if (isset($this->data['purchase']['total'])) {
            return $this->data['purchase']['total'];
        }
        
        return null;
    }

    /**
     * Get purchase currency
     */
    public function getCurrency()
    {
        return isset($this->data['purchase']['currency']) ? $this->data['purchase']['currency'] : null;
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