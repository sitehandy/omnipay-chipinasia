<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractResponse;

class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return isset($this->data['status']) && $this->data['status'] === 'paid';
    }

    public function isPending()
    {
        return isset($this->data['status']) && in_array($this->data['status'], ['pending', 'processing']);
    }

    public function isCancelled()
    {
        return isset($this->data['status']) && $this->data['status'] === 'cancelled';
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
        if (isset($this->data['status_details'])) {
            return $this->data['status_details'];
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

    public function getAmount()
    {
        if (isset($this->data['purchase']['total'])) {
            return $this->data['purchase']['total'] / 100; // Convert from cents
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

    public function __construct($request, $data)
    {
        parent::__construct($request, $data);
        $this->data = json_decode($data, true);
    }
}