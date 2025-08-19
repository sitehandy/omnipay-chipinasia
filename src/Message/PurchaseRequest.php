<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Omnipay\ChipInAsia\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * CHIP Purchase Request
 *
 * This class handles the creation of purchase requests following the official
 * CHIP PHP SDK structure with proper client details and purchase details models.
 *
 * @link https://developer.chip-in.asia/
 */
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
        return $this->getParameter('success_redirect') ?: $this->getReturnUrl();
    }

    public function setSuccessUrl($value)
    {
        return $this->setParameter('success_redirect', $value);
    }

    public function getCancelUrl()
    {
        return $this->getParameter('cancel_redirect') ?: $this->getCancelUrl();
    }

    public function setCancelUrl($value)
    {
        return $this->setParameter('cancel_redirect', $value);
    }

    public function getFailureUrl()
    {
        return $this->getParameter('failure_redirect') ?: $this->getCancelUrl();
    }

    public function setFailureUrl($value)
    {
        return $this->setParameter('failure_redirect', $value);
    }

    public function getWebhookUrl()
    {
        return $this->getParameter('webhook_url') ?: $this->getNotifyUrl();
    }

    public function setWebhookUrl($value)
    {
        return $this->setParameter('webhook_url', $value);
    }

    public function getReference()
    {
        return $this->getParameter('reference') ?: $this->getTransactionId();
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

    public function getDue()
    {
        return $this->getParameter('due');
    }

    public function setDue($value)
    {
        return $this->setParameter('due', $value);
    }

    public function getTimezone()
    {
        return $this->getParameter('timezone') ?: 'Asia/Kuala_Lumpur';
    }

    public function setTimezone($value)
    {
        return $this->setParameter('timezone', $value);
    }

    public function getCreatorAgent()
    {
        return $this->getParameter('creator_agent') ?: 'Omnipay-ChipInAsia/1.0.1';
    }

    public function setCreatorAgent($value)
    {
        return $this->setParameter('creator_agent', $value);
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
            $this->logger->info('Creating purchase request', [
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency(),
                'reference' => $this->getReference()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Purchase request validation failed', [
                'error' => $e->getMessage(),
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency()
            ]);
            throw $e;
        }
    
        // Create purchase object matching official CHIP SDK structure
        $data = [
            'brand_id' => $this->getBrandId(),
            'success_redirect' => $this->getSuccessUrl(),
            'failure_redirect' => $this->getFailureUrl(),
            'cancel_redirect' => $this->getCancelUrl(),
            'success_callback' => $this->getWebhookUrl(),
            'creator_agent' => $this->getCreatorAgent(),
            'reference' => $this->getReference(),
            'platform' => 'api',
            'send_receipt' => $this->getSendReceipt() ?? true,
            'due_strict' => $this->getDueStrictly() ?? false,
        ];
    
        // Add client details (matching official SDK ClientDetails model)
        $clientData = $this->buildClientDetails();
        if (!empty($clientData)) {
            $data['client'] = $clientData;
        }
    
        // Add purchase details (matching official SDK PurchaseDetails model)
        $data['purchase'] = $this->buildPurchaseDetails();
    
        return array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Build client details following CHIP SDK ClientDetails model
     */
    protected function buildClientDetails()
    {
        $clientData = [];
        
        if ($this->getCard()) {
            $card = $this->getCard();
            
            // Map card data to CHIP client structure
            if ($card->getEmail()) {
                $clientData['email'] = $card->getEmail();
            }
            
            if ($card->getPhone()) {
                $clientData['phone'] = $card->getPhone();
            }
            
            $fullName = trim($card->getFirstName() . ' ' . $card->getLastName());
            if ($fullName && $fullName !== ' ') {
                $clientData['full_name'] = $fullName;
            }
            
            if ($card->getNumber()) {
                $clientData['personal_code'] = $card->getNumber();
            }
            
            if ($card->getCompany()) {
                $clientData['legal_name'] = $card->getCompany();
                $clientData['brand_name'] = $card->getCompany();
            }
            
            // Additional client fields from card data
            if ($card->getBillingAddress1()) {
                $clientData['street_address'] = $card->getBillingAddress1();
            }
            
            if ($card->getBillingCity()) {
                $clientData['city'] = $card->getBillingCity();
            }
            
            if ($card->getBillingState()) {
                $clientData['state'] = $card->getBillingState();
            }
            
            if ($card->getBillingPostcode()) {
                $clientData['zip_code'] = $card->getBillingPostcode();
            }
            
            if ($card->getBillingCountry()) {
                $clientData['country'] = $card->getBillingCountry();
            }
        }
        
        return array_filter($clientData);
    }

    /**
     * Build purchase details following CHIP SDK PurchaseDetails model
     */
    protected function buildPurchaseDetails()
    {
        $purchaseData = [
            'timezone' => $this->getTimezone(),
            'currency' => $this->getCurrency(),
            'due' => $this->getDue() ?: (time() + (24 * 60 * 60)), // Default 24 hours from now
            'products' => $this->buildProducts()
        ];
        
        return $purchaseData;
    }

    /**
     * Build products array for purchase
     */
    protected function buildProducts()
    {
        $products = [];
        
        // Check if products are provided as parameter
        $providedProducts = $this->getParameter('products');
        if (is_array($providedProducts) && !empty($providedProducts)) {
            foreach ($providedProducts as $product) {
                $products[] = [
                    'name' => $product['name'] ?? 'Product',
                    'price' => isset($product['price']) ? (int)($product['price'] * 100) : 0,
                    'quantity' => $product['quantity'] ?? 1
                ];
            }
        } else {
            // Default single product from amount and description
            $products[] = [
                'name' => $this->getDescription() ?: 'Payment',
                'price' => (int)($this->getAmount() * 100), // Convert to cents
                'quantity' => 1
            ];
        }
        
        return $products;
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
        } elseif (!in_array(strtoupper($this->getCurrency()), ['MYR', 'SGD', 'USD', 'EUR', 'THB', 'VND', 'IDR'])) {
            $errors['currency'] = 'Currency must be one of: MYR, SGD, USD, EUR, THB, VND, IDR';
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
            'webhookUrl' => $this->getWebhookUrl(),
            'returnUrl' => $this->getReturnUrl(),
            'notifyUrl' => $this->getNotifyUrl()
        ];
        
        foreach ($urls as $field => $url) {
            if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[$field] = "Invalid {$field} format";
            }
        }
        
        // Validate due date if provided
        if ($this->getDue() && !is_numeric($this->getDue())) {
            $errors['due'] = 'Due date must be a valid timestamp';
        }
        
        // Validate products if provided
        $products = $this->getParameter('products');
        if ($products && is_array($products)) {
            foreach ($products as $index => $product) {
                if (!isset($product['name']) || empty($product['name'])) {
                    $errors["products.{$index}.name"] = 'Product name is required';
                }
                if (!isset($product['price']) || !is_numeric($product['price']) || $product['price'] <= 0) {
                    $errors["products.{$index}.price"] = 'Product price must be a positive number';
                }
                if (isset($product['quantity']) && (!is_numeric($product['quantity']) || $product['quantity'] <= 0)) {
                    $errors["products.{$index}.quantity"] = 'Product quantity must be a positive number';
                }
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
            'User-Agent' => $this->getCreatorAgent()
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