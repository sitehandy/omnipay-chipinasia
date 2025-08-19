<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Omnipay\ChipInAsia\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * CHIP Complete Purchase Request
 *
 * Handles transaction completion by retrieving purchase details from CHIP API.
 * This is typically called after a successful payment redirect to verify the transaction status.
 *
 * @see https://github.com/CHIPAsia/chip-php-sdk
 */
class CompletePurchaseRequest extends AbstractRequest
{
    protected $endpoint = 'https://gate.chip-in.asia/api/v1/purchases/';
    
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

    public function getWebhookSecret()
    {
        return $this->getParameter('webhookSecret');
    }

    public function setWebhookSecret($value)
    {
        return $this->setParameter('webhookSecret', $value);
    }

    public function getData()
    {
        try {
            $this->validateRequest();
            
            // Get purchase ID from multiple sources following CHIP SDK patterns
            $purchaseId = $this->getPurchaseId();
            
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
     * Get purchase ID from various sources
     */
    protected function getPurchaseId(): string
    {
        // Priority order: explicit parameter > query params > POST data > transaction reference
        $purchaseId = $this->getParameter('purchase_id')
                   ?: $this->httpRequest->query->get('purchase_id')
                   ?: $this->httpRequest->request->get('purchase_id')
                   ?: $this->httpRequest->query->get('id')
                   ?: $this->httpRequest->request->get('id')
                   ?: $this->getTransactionReference();

        if (!$purchaseId) {
            throw new InvalidRequestException('Missing purchase_id parameter. Provide via setPurchaseId(), query parameter, or POST data.');
        }
        
        return $this->sanitizePurchaseId($purchaseId);
    }

    /**
     * Set purchase ID parameter
     */
    public function setPurchaseId($value)
    {
        return $this->setParameter('purchase_id', $value);
    }

    /**
     * Get purchase ID parameter
     */
    public function getPurchaseIdParameter()
    {
        return $this->getParameter('purchase_id');
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
        // CHIP purchase IDs are typically UUIDs or alphanumeric strings
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($purchaseId));
        
        if (empty($sanitized)) {
            throw new InvalidRequestException('Invalid purchase ID format. Must contain only alphanumeric characters, hyphens, and underscores.');
        }
        
        // Validate length (CHIP IDs are typically 20-40 characters)
        if (strlen($sanitized) < 10 || strlen($sanitized) > 50) {
            throw new InvalidRequestException('Invalid purchase ID length. Must be between 10 and 50 characters.');
        }
        
        return $sanitized;
    }

    public function sendData($data)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $this->getUserAgent()
        ];

        $url = $this->getEndpoint() . $data['purchase_id'] . '/';

        try {
            $this->logger->info('Sending complete purchase request to CHIP', [
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
            
            $this->logger->info('Received complete purchase response from CHIP', [
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
                'Failed to communicate with CHIP API: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get User-Agent string
     */
    protected function getUserAgent(): string
    {
        return $this->getParameter('creator_agent') ?: 'Omnipay-ChipInAsia/2.0';
    }

    /**
     * Set User-Agent string
     */
    public function setCreatorAgent($value)
    {
        return $this->setParameter('creator_agent', $value);
    }

    public function createResponse($data)
    {
        return new CompletePurchaseResponse($this, $data);
    }

    /**
     * Get the endpoint URL for the request
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}