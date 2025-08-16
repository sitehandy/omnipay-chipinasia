<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\ChipInAsia\Exception\WebhookException;
use Omnipay\ChipInAsia\Webhook\WebhookVerifier;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Webhook request handler for Chip-in Asia
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
     * Get webhook secret
     */
    public function getWebhookSecret()
    {
        return $this->getParameter('webhookSecret');
    }

    /**
     * Set webhook secret
     */
    public function setWebhookSecret($value)
    {
        return $this->setParameter('webhookSecret', $value);
    }

    /**
     * Get webhook verifier instance
     */
    protected function getVerifier(): WebhookVerifier
    {
        if ($this->verifier === null) {
            $secret = $this->getWebhookSecret();
            if (empty($secret)) {
                throw new WebhookException('Webhook secret is required for verification');
            }
            $this->verifier = new WebhookVerifier($secret, $this->logger);
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
        // Try different header formats that Chip-in Asia might use
        $headers = [
            'X-Chip-Signature',
            'X-Signature',
            'Chip-Signature',
            'Signature'
        ];

        foreach ($headers as $header) {
            $signature = $this->httpRequest->headers->get($header);
            if (!empty($signature)) {
                // Remove any prefix like 'sha256='
                return preg_replace('/^[a-z0-9]+=/', '', $signature);
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
            'Timestamp'
        ];

        foreach ($headers as $header) {
            $timestamp = $this->httpRequest->headers->get($header);
            if (!empty($timestamp)) {
                return $timestamp;
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