<?php

namespace Omnipay\ChipInAsia;

use Omnipay\Tests\GatewayTestCase;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Omnipay\ChipInAsia\TestLogger;

class GatewayTest extends GatewayTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new TestLogger();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest(), $this->logger);

        $this->options = [
            'apiKey' => 'test_api_key',
            'brandId' => 'test_brand_id',
            'amount' => '10.00',
            'currency' => 'MYR',
            'description' => 'Test Payment',
            'transactionId' => 'TEST123',
            'returnUrl' => 'https://www.example.com/return',
            'cancelUrl' => 'https://www.example.com/cancel',
            'webhookSecret' => 'test_webhook_secret',
        ];
    }

    public function testGatewayProperties()
    {
        $this->assertEquals('Chip-in Asia', $this->gateway->getName());
        $this->assertFalse($this->gateway->getTestMode());
    }

    public function testDefaultParameters()
    {
        $this->assertEquals('', $this->gateway->getApiKey());
        $this->assertEquals('', $this->gateway->getBrandId());
        $this->assertFalse($this->gateway->getTestMode());
    }

    public function testParameterSetters()
    {
        $this->gateway->setApiKey('test_key');
        $this->gateway->setBrandId('test_brand');
        $this->gateway->setTestMode(true);

        $this->assertEquals('test_key', $this->gateway->getApiKey());
        $this->assertEquals('test_brand', $this->gateway->getBrandId());
        $this->assertTrue($this->gateway->getTestMode());
    }

    public function testPurchase()
    {
        $request = $this->gateway->purchase($this->options);

        $this->assertInstanceOf('Omnipay\ChipInAsia\Message\PurchaseRequest', $request);
        $this->assertEquals('10.00', $request->getAmount());
        $this->assertEquals('MYR', $request->getCurrency());
    }

    public function testCompletePurchase()
    {
        $request = $this->gateway->completePurchase($this->options);

        $this->assertInstanceOf('Omnipay\ChipInAsia\Message\CompletePurchaseRequest', $request);
    }
    
    public function testWebhookSecret()
    {
        $this->gateway->setWebhookSecret('new_secret');
        $this->assertEquals('new_secret', $this->gateway->getWebhookSecret());
    }
    
    public function testAcceptNotification()
    {
        $request = $this->gateway->acceptNotification($this->options);
        
        $this->assertInstanceOf('Omnipay\ChipInAsia\Message\WebhookRequest', $request);
    }
    
    public function testPurchaseWithMissingApiKey()
    {
        $this->gateway->setApiKey('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Gateway configuration invalid: API key is required');
        
        $this->gateway->purchase($this->options);
    }
    
    public function testPurchaseWithMissingBrandId()
    {
        $this->gateway->setBrandId('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Gateway configuration invalid: Brand ID is required');
        
        $this->gateway->purchase($this->options);
    }
    
    public function testCompletePurchaseWithMissingApiKey()
    {
        $this->gateway->setApiKey('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Gateway configuration invalid: API key is required');
        
        $this->gateway->completePurchase($this->options);
    }
    
    public function testAcceptNotificationWithMissingWebhookSecret()
    {
        $this->gateway->setWebhookSecret('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Webhook secret is required for webhook processing');
        
        $this->gateway->acceptNotification($this->options);
    }
    
    public function testLoggingIntegration()
    {
        $this->gateway->purchase($this->options);
        
        $this->assertTrue($this->logger->hasInfo('Creating purchase request'));
    }
    
    public function testDefaultWebhookSecret()
    {
        $this->assertEquals('', $this->gateway->getWebhookSecret());
    }
    
    public function testGatewayWithAllParameters()
    {
        $this->gateway->setApiKey('test_key');
        $this->gateway->setBrandId('test_brand');
        $this->gateway->setTestMode(true);
        $this->gateway->setWebhookSecret('test_secret');
        
        $this->assertEquals('test_key', $this->gateway->getApiKey());
        $this->assertEquals('test_brand', $this->gateway->getBrandId());
        $this->assertTrue($this->gateway->getTestMode());
        $this->assertEquals('test_secret', $this->gateway->getWebhookSecret());
    }
}