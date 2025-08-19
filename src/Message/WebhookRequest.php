<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\ChipInAsia\Exception\WebhookException;
use Omnipay\ChipInAsia\Webhook\WebhookVerifier;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * CHIP Webhook Request Handler
 * 
 * Handles incoming webhook notifications from CHIP payment gateway.
 * Supports both webhook secret (HMAC) and public key (RSA) verification methods.
 * 
 * @package Omnipay\ChipInAsia\Message
 */
class WebhookRequest extends AbstractRequest
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var WebhookVerifier
     */
    protected $verifier;

    public function __construct($httpClient, $httpRequest, LoggerInterface $logger = null)
    {
        parent::__construct($httpClient, $httpRequest);
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Get webhook secret for HMAC verification
     */
    public function getWebhookSecret()
    {
        return $this->getParameter('webhookSecret');
    }

    /**
     * Set webhook secret for HMAC verification
     */
    public function setWebhookSecret($value)
    {
        return $this->setParameter('webhookSecret', $value);
    }

    /**
     * Get webhook public key for RSA verification
     */
    public function getWebhookPublicKey()
    {
        return $this->getParameter('webhookPublicKey');
    }

    /**
     * Set webhook public key for RSA verification
     */
    public function setWebhookPublicKey($value)
    {
        return $this->setParameter('webhookPublicKey', $value);
    }

    /**
     * Get webhook verifier instance
     */
    protected function getVerifier(): WebhookVerifier
    {
        if ($this->verifier === null) {
            $secret = $this->getWebhookSecret();
            $publicKey = $this->getWebhookPublicKey();
            
            if (empty($secret) && empty($publicKey)) {
                throw new WebhookException('Either webhook secret or public key is required for verification');
            }
            
            $this->verifier = new WebhookVerifier($secret, $this->logger, $publicKey);
        }
        return $this->verifier;
    }

    /**
     * Get webhook data from request
     */
    public function getData()
    {
        try {
            // Get raw payload
            $payload = $this->httpRequest->getContent();
            if (empty($payload)) {
                throw new WebhookException('Empty webhook payload');
            }

            // Get signature from headers
            $signature = $this->getSignatureFromHeaders();
            $timestamp = $this->getTimestampFromHeaders();

            $this->logger->info('Processing webhook request', [
                'payload_length' => strlen($payload),
                'has_signature' => !empty($signature),
                'has_timestamp' => !empty($timestamp)
            ]);

            // Verify and parse webhook
            $data = $this->getVerifier()->verifyAndParse($payload, $signature, $timestamp);

            $this->logger->info('Webhook verified successfully', [
                'purchase_id' => $data['id'] ?? null,
                'status' => $data['status'] ?? null
            ]);

            return $data;

        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get signature from request headers
     */
    protected function getSignatureFromHeaders(): string
    {
        // Try CHIP-specific header formats
        $headers = [
            'X-Chip-Signature',
            'X-Signature', 
            'Chip-Signature',
            'Signature',
            'X-CHIP-Signature' // Alternative casing
        ];

        foreach ($headers as $header) {
            $signature = $this->httpRequest->headers->get($header);
            if (!empty($signature)) {
                // Handle different signature formats
                if (strpos($signature, 'sha256=') === 0) {
                    return substr($signature, 7); // Remove 'sha256=' prefix
                } elseif (strpos($signature, 'rsa-sha256=') === 0) {
                    return substr($signature, 11); // Remove 'rsa-sha256=' prefix
                }
                return $signature; // Return as-is if no known prefix
            }
        }

        throw new WebhookException('Missing webhook signature header');
    }

    /**
     * Get timestamp from request headers
     */
    protected function getTimestampFromHeaders(): ?string
    {
        $headers = [
            'X-Chip-Timestamp',
            'X-Timestamp',
            'Chip-Timestamp', 
            'Timestamp',
            'X-CHIP-Timestamp' // Alternative casing
        ];

        foreach ($headers as $header) {
            $timestamp = $this->httpRequest->headers->get($header);
            if (!empty($timestamp)) {
                // Validate timestamp format
                if (is_numeric($timestamp)) {
                    return $timestamp;
                }
                // Try to parse ISO 8601 format and convert to Unix timestamp
                $dateTime = \DateTime::createFromFormat(\DateTime::ISO8601, $timestamp);
                if ($dateTime !== false) {
                    return (string) $dateTime->getTimestamp();
                }
            }
        }

        return null; // Timestamp is optional
    }

    /**
     * Send data (not applicable for webhooks)
     */
    public function sendData($data)
    {
        return $this->createResponse($data);
    }

    /**
     * Create webhook response
     */
    public function createResponse($data)
    {
        return new WebhookResponse($this, $data);
    }
}