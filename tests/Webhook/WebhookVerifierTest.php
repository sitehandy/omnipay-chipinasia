<?php

namespace Omnipay\ChipInAsia\Webhook;

use PHPUnit\Framework\TestCase;
use Omnipay\ChipInAsia\Exception\WebhookException;
use Omnipay\ChipInAsia\TestLogger;

class WebhookVerifierTest extends TestCase
{
    private $verifier;
    private $logger;
    private $secret = 'test_webhook_secret';
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new TestLogger();
        $this->verifier = new WebhookVerifier($this->secret, $this->logger);
    }
    
    public function testValidSignatureVerification()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $signature = hash_hmac('sha256', $payload, $this->secret);
        
        $result = $this->verifier->verifySignature($payload, $signature);
        
        $this->assertTrue($result);
        $this->assertTrue($this->logger->hasInfo('Webhook signature verified successfully'));
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
    
    public function testInvalidSignature()
    {
        $payload = '{"id":"purchase_123","status":"paid"}';
        $invalidSignature = 'invalid_signature';
        
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Invalid webhook signature');
        
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
        $this->expectExceptionMessage('Invalid webhook signature');
        
        $this->verifier->verifyAndParse($payload, $invalidSignature);
    }
}