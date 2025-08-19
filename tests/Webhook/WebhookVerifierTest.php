<?php

namespace Omnipay\ChipInAsia\Webhook;

use PHPUnit\Framework\TestCase;
use Omnipay\ChipInAsia\Exception\WebhookException;
use Omnipay\ChipInAsia\TestLogger;

class WebhookVerifierTest extends TestCase
{
    private $verifier;
    private $rsaVerifier;
    private $logger;
    private $secret = 'test_webhook_secret';
    private $publicKey = '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4f5wg5l2hKsTeNem/V41\nfGnJm6gOdrj8ym3rFkEjWT2btf06kkstX4KE2ZiKGYKQVyAiYI+e2JZpUy2qdx05\n-----END PUBLIC KEY-----';
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new TestLogger();
        $this->verifier = new WebhookVerifier($this->secret, $this->logger);
        $this->rsaVerifier = new WebhookVerifier(null, $this->logger, $this->publicKey);
    }
    
    public function testValidHmacSignatureVerification()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $signature = hash_hmac('sha256', $payload, $this->secret);
        
        $result = $this->verifier->verifySignature($payload, $signature);
        
        $this->assertTrue($result);
        $this->assertTrue($this->logger->hasInfo('HMAC webhook signature verified successfully'));
    }
    
    public function testValidSignatureWithTimestamp()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $timestamp = (string) time();
        $data = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $data, $this->secret);
        
        $result = $this->verifier->verifySignature($payload, $signature, $timestamp);
        
        $this->assertTrue($result);
    }
    
    public function testInvalidHmacSignature()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $invalidSignature = 'invalid_signature';
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Invalid HMAC webhook signature');
        
        $this->verifier->verifySignature($payload, $invalidSignature);
    }
    
    public function testExpiredTimestamp()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $oldTimestamp = (string) (time() - 400); // 400 seconds ago
        $data = $oldTimestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $data, $this->secret);
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Webhook timestamp too old');
        
        $this->verifier->verifySignature($payload, $signature, $oldTimestamp);
    }
    
    public function testParseValidPayload()
    {
        $payload = '{"id":"purchase_123","status":"paid","amount":1000}';
        
        $result = $this->verifier->parsePayload($payload);
        
        $this->assertEquals([
            'id' => 'purchase_123',
            'status' => 'paid',
            'amount' => 1000
        ], $result);
        
        $this->assertTrue($this->logger->hasInfo('Webhook payload parsed successfully'));
    }
    
    public function testParseInvalidJson()
    {
        $payload = '{"id":"purchase_123","status":"paid"';
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Invalid JSON payload');
        
        $this->verifier->parsePayload($payload);
    }
    
    public function testParseMissingRequiredFields()
    {
        $payload = '{"status":"paid"}';
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Missing required field: id');
        
        $this->verifier->parsePayload($payload);
    }
    
    public function testVerifyAndParse()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $signature = hash_hmac('sha256', $payload, $this->secret);
        
        $result = $this->verifier->verifyAndParse($payload, $signature);
        
        $this->assertEquals([
            'id' => 'purchase_123',
            'status' => 'paid'
        ], $result);
    }
    
    public function testVerifyAndParseWithInvalidSignature()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $invalidSignature = 'invalid';
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Invalid HMAC webhook signature');
        
        $this->verifier->verifyAndParse($payload, $invalidSignature);
    }
    
    public function testConstructorWithoutCredentials()
    {
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Either webhook secret or public key must be provided');
        
        new WebhookVerifier();
    }
    
    public function testRsaSignatureVerification()
    {
        // Test with an invalid RSA public key format
        $invalidFormatKey = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1234567890\n-----END PUBLIC KEY-----";
        $rsaVerifier = new WebhookVerifier(null, $this->logger, $invalidFormatKey);
        
        $payload = '{"test": "data"}';
        $signature = 'rsa_signature_here';
        $timestamp = (string) time();
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Invalid public key format');
        
        $rsaVerifier->verifyAndParse($payload, $signature, $timestamp);
    }
    
    public function testInvalidPublicKeyFormat()
    {
        // Test with an invalid RSA public key format
        $invalidFormatKey = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1234567890\n-----END PUBLIC KEY-----";
        $rsaVerifier = new WebhookVerifier(null, $this->logger, $invalidFormatKey);
        
        $payload = '{"test": "data"}';
        $signature = 'valid_base64_signature';
        $timestamp = (string) time();
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Invalid public key format');
        
        $rsaVerifier->verifyAndParse($payload, $signature, $timestamp);
    }
    
    public function testEmptyPayload()
    {
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Empty webhook payload');
        
        $this->verifier->parsePayload('');
    }
    
    public function testInvalidTimestampFormat()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $signature = hash_hmac('sha256', 'invalid_timestamp.' . $payload, $this->secret);
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Invalid timestamp format');
        
        $this->verifier->verifySignature($payload, $signature, 'invalid_timestamp');
    }
    
    public function testTimestampValidationLogging()
    {
        $payload = '{"test": "data"}';
        $signature = 'valid_signature';
        $timestamp = (string) (time() - 400); // 400 seconds old
        
        $this->expectException(WebhookException::class);
        $this->verifier->verifyAndParse($payload, $signature, $timestamp);
        
        // Check that warning was logged
        $this->assertTrue($this->logger->hasRecords('warning'));
    }
}