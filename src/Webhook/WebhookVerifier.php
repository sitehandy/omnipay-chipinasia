<?php

namespace Omnipay\ChipInAsia\Webhook;

use Omnipay\ChipInAsia\Exception\WebhookException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * CHIP Webhook Signature Verification Utility
 * 
 * Supports both HMAC-SHA256 (webhook secret) and RSA-SHA256 (public key) verification methods.
 * Provides secure webhook signature verification following CHIP's official patterns.
 * 
 * @package Omnipay\ChipInAsia\Webhook
 */
class WebhookVerifier
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string|null
     */
    protected $webhookSecret;

    /**
     * @var string|null
     */
    protected $webhookPublicKey;

    public function __construct(string $webhookSecret = null, LoggerInterface $logger = null, string $webhookPublicKey = null)
    {
        $this->webhookSecret = $webhookSecret;
        $this->webhookPublicKey = $webhookPublicKey;
        $this->logger = $logger ?: new NullLogger();
        
        if (empty($this->webhookSecret) && empty($this->webhookPublicKey)) {
            throw new WebhookException('Either webhook secret or public key must be provided');
        }
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload The raw webhook payload
     * @param string $signature The signature from the webhook headers
     * @param string $timestamp The timestamp from the webhook headers
     * @throws WebhookException
     */
    public function verifySignature(string $payload, string $signature, string $timestamp = null): bool
    {
        try {
            // Validate timestamp if provided (prevent replay attacks)
            if ($timestamp !== null) {
                $this->validateTimestamp($timestamp);
            }

            // Choose verification method based on available credentials
            if (!empty($this->webhookPublicKey)) {
                return $this->verifyRsaSignature($payload, $signature, $timestamp);
            } elseif (!empty($this->webhookSecret)) {
                return $this->verifyHmacSignature($payload, $signature, $timestamp);
            }

            throw new WebhookException('No verification method available');

        } catch (\Exception $e) {
            if ($e instanceof WebhookException) {
                throw $e;
            }

            $this->logger->error('Webhook verification error', [
                'error' => $e->getMessage()
            ]);
            throw new WebhookException('Webhook verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify HMAC signature using webhook secret
     */
    protected function verifyHmacSignature(string $payload, string $signature, string $timestamp = null): bool
    {
        $expectedSignature = $this->generateHmacSignature($payload, $timestamp);
        
        if (!hash_equals($expectedSignature, $signature)) {
            $this->logger->warning('HMAC webhook signature verification failed', [
                'expected_signature' => $expectedSignature,
                'received_signature' => $signature
            ]);
            throw new WebhookException('Invalid HMAC webhook signature');
        }

        $this->logger->info('HMAC webhook signature verified successfully');
        return true;
    }

    /**
     * Verify RSA signature using public key
     */
    protected function verifyRsaSignature(string $payload, string $signature, string $timestamp = null): bool
    {
        $data = $timestamp ? $timestamp . '.' . $payload : $payload;
        
        // Decode base64 signature
        $binarySignature = base64_decode($signature);
        if ($binarySignature === false) {
            throw new WebhookException('Invalid base64 signature format');
        }
        
        // Verify RSA signature
        $publicKey = openssl_pkey_get_public($this->webhookPublicKey);
        if ($publicKey === false) {
            throw new WebhookException('Invalid public key format');
        }
        
        $result = openssl_verify($data, $binarySignature, $publicKey, OPENSSL_ALGO_SHA256);
        
        if ($result === 1) {
            $this->logger->info('RSA webhook signature verified successfully');
            return true;
        } elseif ($result === 0) {
            $this->logger->warning('RSA webhook signature verification failed');
            throw new WebhookException('Invalid RSA webhook signature');
        } else {
            $error = openssl_error_string();
            $this->logger->error('RSA signature verification error', ['openssl_error' => $error]);
            throw new WebhookException('RSA signature verification error: ' . $error);
        }
    }

    /**
     * Generate HMAC signature for payload
     */
    protected function generateHmacSignature(string $payload, string $timestamp = null): string
    {
        $data = $timestamp ? $timestamp . '.' . $payload : $payload;
        return hash_hmac('sha256', $data, $this->webhookSecret);
    }

    /**
     * Validate timestamp to prevent replay attacks
     */
    protected function validateTimestamp(string $timestamp, int $toleranceSeconds = 300): void
    {
        if (!is_numeric($timestamp)) {
            throw new WebhookException('Invalid timestamp format');
        }
        
        $webhookTime = (int) $timestamp;
        $currentTime = time();
        $timeDifference = abs($currentTime - $webhookTime);

        if ($timeDifference > $toleranceSeconds) {
            $this->logger->warning('Webhook timestamp validation failed', [
                'webhook_time' => $webhookTime,
                'current_time' => $currentTime,
                'difference' => $timeDifference,
                'tolerance' => $toleranceSeconds
            ]);
            
            throw new WebhookException(
                sprintf(
                    'Webhook timestamp too old. Difference: %d seconds, tolerance: %d seconds',
                    $timeDifference,
                    $toleranceSeconds
                )
            );
        }
    }

    /**
     * Parse and validate webhook payload
     */
    public function parsePayload(string $payload): array
    {
        if (empty($payload)) {
            throw new WebhookException('Empty webhook payload');
        }
        
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('JSON parsing failed', [
                'error' => json_last_error_msg(),
                'payload_length' => strlen($payload)
            ]);
            throw new WebhookException('Invalid JSON payload: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new WebhookException('Webhook payload must be a JSON object');
        }

        // Validate required fields for CHIP webhooks
        $requiredFields = ['id', 'status'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $this->logger->warning('Missing required webhook field', [
                    'field' => $field,
                    'available_fields' => array_keys($data)
                ]);
                throw new WebhookException("Missing required field: {$field}");
            }
        }

        $this->logger->info('Webhook payload parsed successfully', [
            'purchase_id' => $data['id'],
            'status' => $data['status']
        ]);

        return $data;
    }

    /**
     * Verify and parse webhook in one step
     */
    public function verifyAndParse(
        string $payload,
        string $signature,
        string $timestamp = null
    ): array {
        $this->verifySignature($payload, $signature, $timestamp);
        return $this->parsePayload($payload);
    }
}