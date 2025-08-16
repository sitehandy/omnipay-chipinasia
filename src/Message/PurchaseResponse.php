<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful()
    {
        return false; // Always redirect for Chip-in Asia
    }

    public function isRedirect()
    {
        return isset($this->data['redirect_url']) || isset($this->data['payment_url']);
    }

    public function getTransactionReference()
    {
        return isset($this->data['id']) ? $this->data['id'] : null;
    }

    public function getRedirectUrl()
    {
        if (isset($this->data['redirect_url'])) {
            return $this->data['redirect_url'];
        }
        
        if (isset($this->data['payment_url'])) {
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
        if (isset($this->data['__all__'])) {
            return implode(' ', $this->data['__all__']);
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

    protected function getData()
    {
        return json_decode($this->data, true);
    }

    public function __construct($request, $data)
    {
        parent::__construct($request, $data);
        $this->data = json_decode($data, true);
    }
}