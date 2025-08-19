<?php

namespace Omnipay\ChipInAsia;

use Omnipay\Common\AbstractGateway;
use Omnipay\ChipInAsia\Message\PurchaseRequest;
use Omnipay\ChipInAsia\Message\CompletePurchaseRequest;
use Omnipay\ChipInAsia\Message\WebhookRequest;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * CHIP Gateway
 *
 * This gateway provides integration with CHIP payment platform following
 * the official CHIP PHP SDK patterns and Omnipay standards.
 *
 * Example:
 * <code>
 *   // Create a gateway for the CHIP Gateway
 *   $gateway = Omnipay::create('ChipInAsia');
 *
 *   // Initialize the gateway
 *   $gateway->setApiKey('your-api-key');
 *   $gateway->setBrandId('your-brand-id');
 *   $gateway->setTestMode(true);
 *   $gateway->setWebhookSecret('your-webhook-secret');
 *
 *   // Create a purchase request
 *   $response = $gateway->purchase([
 *       'amount' => '10.00',
 *       'currency' => 'MYR',
 *       'transactionId' => 'ORDER123',
 *       'description' => 'Test Purchase',
 *       'returnUrl' => 'https://example.com/success',
 *       'cancelUrl' => 'https://example.com/cancel',
 *       'notifyUrl' => 'https://example.com/webhook',
 *       'card' => [
 *           'email' => 'customer@example.com',
 *           'firstName' => 'John',
 *           'lastName' => 'Doe',
 *           'phone' => '+60123456789'
 *       ]
 *   ])->send();
 * </code>
 *
 * @link https://developer.chip-in.asia/
 */
class Gateway extends AbstractGateway
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    public function __construct($httpClient = null, $httpRequest = null, LoggerInterface $logger = null)
    {
        parent::__construct($httpClient, $httpRequest);
        $this->logger = $logger ?: new NullLogger();
    }
    
    public function getName()
    {
        return 'CHIP';
    }

    public function getShortName()
    {
        return 'ChipInAsia';
    }

    public function getDefaultParameters()
    {
        return [
            'apiKey' => '',
            'brandId' => '',
            'testMode' => false,
            'webhookSecret' => '',
            'endpoint' => null, // Allow custom endpoint override
            'webhookPublicKey' => '', // For webhook signature verification
        ];
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

    public function getTestMode()
    {
        return $this->getParameter('testMode');
    }

    public function setTestMode($value)
    {
        return $this->setParameter('testMode', $value);
    }

    public function getWebhookSecret()
    {
        return $this->getParameter('webhookSecret');
    }

    public function setWebhookSecret($value)
    {
        return $this->setParameter('webhookSecret', $value);
    }

    public function getWebhookPublicKey()
    {
        return $this->getParameter('webhookPublicKey');
    }

    public function setWebhookPublicKey($value)
    {
        return $this->setParameter('webhookPublicKey', $value);
    }

    public function getEndpoint()
    {
        return $this->getParameter('endpoint');
    }

    public function setEndpoint($value)
    {
        return $this->setParameter('endpoint', $value);
    }

    public function purchase(array $parameters = [])
    {
        try {
            $this->validateGatewayConfiguration();
            $this->logger->info('Creating purchase request', [
                'parameters' => array_keys($parameters)
            ]);
            
            return $this->createRequest(PurchaseRequest::class, $parameters);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create purchase request', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function completePurchase(array $parameters = [])
    {
        try {
            $this->validateGatewayConfiguration();
            $this->logger->info('Creating complete purchase request', [
                'parameters' => array_keys($parameters)
            ]);
            
            return $this->createRequest(CompletePurchaseRequest::class, $parameters);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create complete purchase request', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function acceptNotification(array $parameters = [])
    {
        try {
            $this->validateWebhookConfiguration();
            $this->logger->info('Creating webhook request', [
                'parameters' => array_keys($parameters)
            ]);
            
            return $this->createRequest(WebhookRequest::class, $parameters);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create webhook request', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Validate gateway configuration
     */
    protected function validateGatewayConfiguration(): void
    {
        $errors = [];
        
        if (empty($this->getApiKey())) {
            $errors[] = 'API key is required';
        }
        
        if (empty($this->getBrandId())) {
            $errors[] = 'Brand ID is required';
        }
        
        if (!empty($errors)) {
            throw new InvalidRequestException('Gateway configuration invalid: ' . implode(', ', $errors));
        }
    }
    
    /**
     * Validate webhook configuration
     */
    protected function validateWebhookConfiguration(): void
    {
        if (empty($this->getWebhookSecret()) && empty($this->getWebhookPublicKey())) {
            throw new InvalidRequestException('Webhook secret or webhook public key is required for webhook processing');
        }
    }
    
    /**
     * Get the appropriate API endpoint
     */
    public function getApiEndpoint()
    {
        // Allow custom endpoint override
        if ($this->getEndpoint()) {
            return rtrim($this->getEndpoint(), '/') . '/api/v1/';
        }
        
        // Use standard endpoints - both test and production use the same endpoint
        // as per CHIP SDK documentation
        return 'https://gate.chip-in.asia/api/v1/';
    }

    /**
     * Get the base URL for the CHIP platform
     */
    public function getBaseUrl()
    {
        return 'https://gate.chip-in.asia';
    }

    /**
     * Check if gateway supports purchase
     */
    public function supportsPurchase()
    {
        return true;
    }

    /**
     * Check if gateway supports complete purchase
     */
    public function supportsCompletePurchase()
    {
        return true;
    }

    /**
     * Check if gateway supports webhooks
     */
    public function supportsAcceptNotification()
    {
        return true;
    }

    /**
     * Get logger instance
     */
    public function getLogger()
    {
        return $this->logger;
    }
}