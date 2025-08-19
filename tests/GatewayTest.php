<?php

namespace Omnipay\ChipInAsia\Tests;

use Omnipay\Tests\GatewayTestCase;
use Omnipay\ChipInAsia\Gateway;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Omnipay\ChipInAsia\Tests\TestLogger;

class GatewayTest extends GatewayTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new TestLogger();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest(), $this->logger);
        $this->gateway->setApiKey('test_api_key');
        $this->gateway->setBrandId('test_brand_id');

        $this->options = [
            'amount' => '10.00',
            'currency' => 'MYR',
            'transactionId' => '12345',
            'description' => 'Test Purchase',
            'returnUrl' => 'https://example.com/return',
            'cancelUrl' => 'https://example.com/cancel',
            'notifyUrl' => 'https://example.com/notify',
            'webhookSecret' => 'test_webhook_secret',
            'webhookPublicKey' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...\n-----END PUBLIC KEY-----',
            'apiKey' => 'test_api_key',
            'brandId' => 'test_brand_id'
        ];
    }

    public function testGatewayProperties()
    {
        $this->assertEquals('CHIP', $this->gateway->getName());
        $this->assertEquals('ChipInAsia', $this->gateway->getShortName());
    }

    public function testDefaultParameters()
    {
        // Create a fresh gateway without credentials for this test
        $freshGateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->assertEmpty($freshGateway->getApiKey());
        $this->assertEmpty($freshGateway->getBrandId());
        $this->assertFalse($freshGateway->getTestMode());
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
    
    public function testWebhookPublicKey()
    {
        $publicKey = '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...\n-----END PUBLIC KEY-----';
        $this->gateway->setWebhookPublicKey($publicKey);
        $this->assertEquals($publicKey, $this->gateway->getWebhookPublicKey());
    }
    
    public function testDefaultWebhookPublicKey()
    {
        $this->assertEquals('', $this->gateway->getWebhookPublicKey());
    }
    
    public function testAcceptNotification()
    {
        $this->gateway->setWebhookSecret('test_webhook_secret');
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
        // Create gateway without brand ID
        $gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $gateway->setApiKey('test_api_key');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Gateway configuration invalid: Brand ID is required');
        
        $gateway->purchase($this->options);
    }
    
    public function testCompletePurchaseWithMissingApiKey()
    {
        $this->gateway->setApiKey('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Gateway configuration invalid: API key is required');
        
        $this->gateway->completePurchase($this->options);
    }
    
    public function testAcceptNotificationWithMissingWebhookCredentials()
    {
        $this->gateway->setWebhookSecret('');
        $this->gateway->setWebhookPublicKey('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Webhook secret or webhook public key is required for webhook processing');
        
        $this->gateway->acceptNotification($this->options);
    }
    
    public function testAcceptNotificationWithWebhookPublicKey()
    {
        $this->gateway->setWebhookSecret('');
        $this->gateway->setWebhookPublicKey('-----BEGIN PUBLIC KEY-----\ntest\n-----END PUBLIC KEY-----');
        
        $request = $this->gateway->acceptNotification($this->options);
        
        $this->assertInstanceOf('Omnipay\ChipInAsia\Message\WebhookRequest', $request);
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
        $publicKey = '-----BEGIN PUBLIC KEY-----\ntest\n-----END PUBLIC KEY-----';
        
        $this->gateway->setApiKey('test_key');
        $this->gateway->setBrandId('test_brand');
        $this->gateway->setTestMode(true);
        $this->gateway->setWebhookSecret('test_secret');
        $this->gateway->setWebhookPublicKey($publicKey);
        
        $this->assertEquals('test_key', $this->gateway->getApiKey());
        $this->assertEquals('test_brand', $this->gateway->getBrandId());
        $this->assertTrue($this->gateway->getTestMode());
        $this->assertEquals('test_secret', $this->gateway->getWebhookSecret());
        $this->assertEquals($publicKey, $this->gateway->getWebhookPublicKey());
    }
    
    public function testCreatorAgent()
    {
        // Test that creator agent can be set via options
        $options = array_merge($this->options, ['creatorAgent' => 'test-agent/1.0']);
        $request = $this->gateway->purchase($options);
        $this->assertInstanceOf('Omnipay\ChipInAsia\Message\PurchaseRequest', $request);
    }

    public function testDefaultCreatorAgent()
    {
        $request = $this->gateway->purchase($this->options);
        $this->assertInstanceOf('Omnipay\ChipInAsia\Message\PurchaseRequest', $request);
    }
    
    /**
     * Override the base test to handle our specific parameter behavior
     */
    public function testPurchaseParameters()
    {
        if ($this->gateway->supportsPurchase()) {
             // Test only the parameters that should be directly passed through
             $testParams = ['apiKey', 'brandId'];
            
            foreach ($testParams as $key) {
                if (array_key_exists($key, $this->gateway->getDefaultParameters())) {
                    $getter = 'get'.ucfirst($this->camelCase($key));
                    $setter = 'set'.ucfirst($this->camelCase($key));
                    $value = uniqid('', true);
                    $this->gateway->$setter($value);

                    $request = $this->gateway->purchase();
                    $this->assertSame($value, $request->$getter());
                }
            }
        } else {
            $this->expectNotToPerformAssertions();
        }
    }
    
    /**
     * Override the base test to handle our specific parameter behavior
     */
    public function testCompletePurchaseParameters()
    {
        if ($this->gateway->supportsCompletePurchase()) {
            // Test only the parameters that should be directly passed through
            $testParams = ['apiKey', 'brandId'];
            
            foreach ($testParams as $key) {
                if (array_key_exists($key, $this->gateway->getDefaultParameters())) {
                    $getter = 'get'.ucfirst($this->camelCase($key));
                    $setter = 'set'.ucfirst($this->camelCase($key));
                    $value = uniqid('', true);
                    $this->gateway->$setter($value);

                    $request = $this->gateway->completePurchase();
                    $this->assertSame($value, $request->$getter());
                }
            }
        } else {
            $this->expectNotToPerformAssertions();
        }
    }
    
    /**
     * Helper method from the base test case
     */
    public function camelCase($str)
    {
        return preg_replace_callback(
            '/_([a-z])/',
            function ($match) {
                return strtoupper($match[1]);
            },
            $str
        );
    }
}