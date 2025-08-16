<?php

namespace Omnipay\ChipInAsia\Webhook;

use Omnipay\ChipInAsia\Exception\WebhookException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Webhook signature verification utility
 */
class WebhookVerifier
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $webhookSecret;

    public function __construct(string $webhookSecret, LoggerInterface $logger = null)
    {
        $this->webhookSecret = $webhookSecret;
        $this->logger = $logger ?: new NullLogger();
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

            // Generate expected signature
            $expectedSignature = $this->generateSignature($payload, $timestamp);

            // Compare signatures using hash_equals to prevent timing attacks
            if (!hash_equals($expectedSignature, $signature)) {
                $this->logger->warning('Webhook signature verification failed', [
                    'expected_signature' => $expectedSignature,
                    'received_signature' => $signature
                ]);
                throw new WebhookException('Invalid webhook signature');
            }

            $this->logger->info('Webhook signature verified successfully');
            return true;

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
     * Generate signature for payload
     */
    protected function generateSignature(string $payload, string $timestamp = null): string
    {
        $data = $timestamp ? $timestamp . '.' . $payload : $payload;
        return hash_hmac('sha256', $data, $this->webhookSecret);
    }

    /**
     * Validate timestamp to prevent replay attacks
     */
    protected function validateTimestamp(string $timestamp, int $toleranceSeconds = 300): void
    {
        $webhookTime = (int) $timestamp;
        $currentTime = time();
        $timeDifference = abs($currentTime - $webhookTime);

        if ($timeDifference > $toleranceSeconds) {
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
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WebhookException('Invalid JSON payload: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new WebhookException('Webhook payload must be a JSON object');
        }

        // Validate required fields
        $requiredFields = ['id', 'status'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new WebhookException("Missing required field: {$field}");
            }
        }

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