<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Omnipay\ChipInAsia\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CompletePurchaseRequest extends AbstractRequest
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

    public function getData()
    {
        try {
            $this->validateRequest();
            
            // Get purchase ID from query parameters or POST data
            $purchaseId = $this->httpRequest->query->get('purchase_id') 
                       ?: $this->httpRequest->request->get('purchase_id')
                       ?: $this->getTransactionReference();

            if (!$purchaseId) {
                throw new InvalidRequestException('Missing purchase_id parameter');
            }
            
            // Sanitize purchase ID
            $purchaseId = $this->sanitizePurchaseId($purchaseId);
            
            $this->logger->info('Creating complete purchase request', [
                'purchase_id' => $purchaseId
            ]);

            return [
                'purchase_id' => $purchaseId
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Complete purchase request validation failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate the request data
     * 
     * @throws InvalidRequestException
     */
    protected function validateRequest(): void
    {
        $errors = [];
        
        if (empty($this->getApiKey())) {
            $errors['apiKey'] = 'API key is required';
        }
        
        if (!empty($errors)) {
            throw new InvalidRequestException('Request validation failed', $errors);
        }
    }
    
    /**
     * Sanitize purchase ID to prevent injection attacks
     */
    protected function sanitizePurchaseId(string $purchaseId): string
    {
        // Remove any non-alphanumeric characters except hyphens and underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $purchaseId);
        
        if (empty($sanitized)) {
            throw new InvalidRequestException('Invalid purchase ID format');
        }
        
        return $sanitized;
    }

    public function sendData($data)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Omnipay-ChipInAsia/1.0'
        ];

        $url = $this->getEndpoint() . $data['purchase_id'] . '/';

        try {
            $this->logger->info('Sending complete purchase request to Chip-in Asia', [
                'url' => $url,
                'purchase_id' => $data['purchase_id']
            ]);

            $httpResponse = $this->httpClient->request(
                'GET',
                $url,
                $headers
            );

            $statusCode = $httpResponse->getStatusCode();
            $responseBody = $httpResponse->getBody()->getContents();
            
            $this->logger->info('Received complete purchase response from Chip-in Asia', [
                'status_code' => $statusCode,
                'response_length' => strlen($responseBody)
            ]);

            // Handle HTTP error status codes
            if ($statusCode >= 400) {
                $responseData = json_decode($responseBody, true) ?? [];
                $this->logger->error('Complete purchase API request failed', [
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
            
            $this->logger->error('Complete purchase HTTP request failed', [
                'error' => $e->getMessage(),
                'url' => $url
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
        return new CompletePurchaseResponse($this, $data);
    }

    protected function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
}