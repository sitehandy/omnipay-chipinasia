<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Omnipay\ChipInAsia\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PurchaseRequest extends AbstractRequest
{
    protected $liveEndpoint = 'https://gate.chip-in.asia/api/v1/purchases/';
    protected $testEndpoint = 'https://gate.chip-in.asia/api/v1/purchases/';
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    public function __construct($httpClient, $httpRequest, LoggerInterface $logger = null)
    {
        parent::__construct($httpClient, $httpRequest);
        $this->logger = $logger ?: new NullLogger();
    }

    public function getApiKey()
    {
        return $this->getParameter('apiKey');
    }

    public function setApiKey($value)
    {
        return $this->setParameter('apiKey', $value);
    }

    public function getBrandId()
    {
        return $this->getParameter('brandId');
    }

    public function setBrandId($value)
    {
        return $this->setParameter('brandId', $value);
    }

    public function getSuccessUrl()
    {
        return $this->getParameter('success_redirect');
    }

    public function setSuccessUrl($value)
    {
        return $this->setParameter('success_redirect', $value);
    }

    public function getCancelUrl()
    {
        return $this->getParameter('cancel_redirect');
    }

    public function setCancelUrl($value)
    {
        return $this->setParameter('cancel_redirect', $value);
    }

    public function getFailureUrl()
    {
        return $this->getParameter('failure_redirect');
    }

    public function setFailureUrl($value)
    {
        return $this->setParameter('failure_redirect', $value);
    }

    public function getWebhookUrl()
    {
        return $this->getParameter('webhook_url');
    }

    public function setWebhookUrl($value)
    {
        return $this->setParameter('webhook_url', $value);
    }

    public function getReference()
    {
        return $this->getParameter('reference');
    }

    public function setReference($value)
    {
        return $this->setParameter('reference', $value);
    }

    public function getDueStrictly()
    {
        return $this->getParameter('due_strictly');
    }

    public function setDueStrictly($value)
    {
        return $this->setParameter('due_strictly', $value);
    }

    public function getSendReceipt()
    {
        return $this->getParameter('send_receipt');
    }

    public function setSendReceipt($value)
    {
        return $this->setParameter('send_receipt', $value);
    }

    public function getData()
    {
        try {
            $this->validateRequest();
            $this->logger->info('Creating purchase request', [
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency(),
                'reference' => $this->getTransactionId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Purchase request validation failed', [
                'error' => $e->getMessage(),
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency()
            ]);
            throw $e;
        }
    
        // Create purchase object matching official SDK structure
        $data = [
            'brand_id' => $this->getBrandId(),
            'success_redirect' => $this->getReturnUrl(),
            'failure_redirect' => $this->getCancelUrl(),
            'success_callback' => $this->getNotifyUrl(),
            'creator_agent' => 'Omnipay-ChipInAsia/1.0.1',
            'reference' => $this->getReference() ?: $this->getTransactionId(),
            'platform' => 'api',
            'send_receipt' => $this->getSendReceipt() ?? true,
            'due_strict' => $this->getDueStrictly() ?? false,
        ];
    
        // Add client details (matching official SDK ClientDetails model)
        if ($this->getCard()) {
            $data['client'] = array_filter([
                'email' => $this->getCard()->getEmail(),
                'phone' => $this->getCard()->getPhone(),
                'full_name' => trim($this->getCard()->getFirstName() . ' ' . $this->getCard()->getLastName()),
                'personal_code' => $this->getCard()->getNumber(),
                'legal_name' => $this->getCard()->getCompany(),
                'brand_name' => $this->getCard()->getCompany(),
            ]);
        }
    
        // Add purchase details (matching official SDK PurchaseDetails model)
        $data['purchase'] = [
            'timezone' => 'Asia/Kuala_Lumpur',
            'currency' => $this->getCurrency(),
            'due' => time() + (24 * 60 * 60), // Default 24 hours from now
            'products' => [
                [
                    'name' => $this->getDescription() ?: 'Payment',
                    'price' => (int) ($this->getAmount() * 100), // Convert to cents
                    'quantity' => 1
                ]
            ]
        ];
    
        return array_filter($data);
    }

    /**
     * Validate the request data
     * 
     * @throws InvalidRequestException
     */
    protected function validateRequest(): void
    {
        $errors = [];
        
        // Validate required fields
        if (empty($this->getApiKey())) {
            $errors['apiKey'] = 'API key is required';
        }
        
        if (empty($this->getBrandId())) {
            $errors['brandId'] = 'Brand ID is required';
        }
        
        if (empty($this->getAmount()) || !is_numeric($this->getAmount()) || $this->getAmount() <= 0) {
            $errors['amount'] = 'Amount must be a positive number';
        }
        
        if (empty($this->getCurrency())) {
            $errors['currency'] = 'Currency is required';
        } elseif (!in_array(strtoupper($this->getCurrency()), ['MYR', 'SGD', 'USD', 'EUR'])) {
            $errors['currency'] = 'Currency must be one of: MYR, SGD, USD, EUR';
        }
        
        // Validate email if provided
        if ($this->getCard() && $this->getCard()->getEmail()) {
            if (!filter_var($this->getCard()->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
        }
        
        // Validate URLs if provided
        $urls = [
            'successUrl' => $this->getSuccessUrl(),
            'cancelUrl' => $this->getCancelUrl(),
            'failureUrl' => $this->getFailureUrl(),
            'webhookUrl' => $this->getWebhookUrl()
        ];
        
        foreach ($urls as $field => $url) {
            if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[$field] = "Invalid {$field} format";
            }
        }
        
        if (!empty($errors)) {
            throw new InvalidRequestException('Request validation failed', $errors);
        }
    }

    public function sendData($data)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Omnipay-ChipInAsia/1.0'
        ];

        try {
            $this->logger->info('Sending purchase request to Chip-in Asia', [
                'endpoint' => $this->getEndpoint(),
                'reference' => $data['reference'] ?? null
            ]);

            $httpResponse = $this->httpClient->request(
                'POST',
                $this->getEndpoint(),
                $headers,
                json_encode($data)
            );

            $statusCode = $httpResponse->getStatusCode();
            $responseBody = $httpResponse->getBody()->getContents();
            
            $this->logger->info('Received response from Chip-in Asia', [
                'status_code' => $statusCode,
                'response_length' => strlen($responseBody)
            ]);

            // Handle HTTP error status codes
            if ($statusCode >= 400) {
                $responseData = json_decode($responseBody, true) ?? [];
                $this->logger->error('API request failed', [
                    'status_code' => $statusCode,
                    'response' => $responseData
                ]);
                throw ApiException::fromResponse($responseData, $statusCode);
            }

            return $this->createResponse($responseBody);
            
        } catch (\Exception $e) {
            if ($e instanceof ApiException) {
                throw $e;
            }
            
            $this->logger->error('HTTP request failed', [
                'error' => $e->getMessage(),
                'endpoint' => $this->getEndpoint()
            ]);
            
            throw new ApiException(
                'Failed to communicate with Chip-in Asia API: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function createResponse($data)
    {
        return new PurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
}