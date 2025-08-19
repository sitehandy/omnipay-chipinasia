<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Tests\TestCase;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Omnipay\ChipInAsia\Exception\ApiException;
use Omnipay\ChipInAsia\TestLogger;

class PurchaseRequestTest extends TestCase
{
    private $request;
    private $logger;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->logger = new TestLogger();
        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest(), $this->logger);
        
        $this->request->setApiKey('test_api_key');
        $this->request->setBrandId('test_brand_id');
        $this->request->setAmount('10.00');
        $this->request->setCurrency('MYR');
        $this->request->setTransactionId('TEST123');
    }
    
    public function testGetDataWithValidParameters()
    {
        $data = $this->request->getData();
        
        $this->assertArrayHasKey('brand_id', $data);
        $this->assertArrayHasKey('purchase', $data);
        $this->assertEquals('test_brand_id', $data['brand_id']);
        $this->assertEquals('MYR', $data['purchase']['currency']);
        $this->assertEquals(1000, $data['purchase']['products'][0]['price']); // 10.00 * 100
    }
    
    public function testValidationWithMissingApiKey()
    {
        $this->request->setApiKey('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testValidationWithMissingBrandId()
    {
        $this->request->setBrandId('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testValidationWithInvalidAmount()
    {
        $this->request->setAmount('0');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testValidationWithNegativeAmount()
    {
        $this->request->setAmount('-10.00');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testValidationWithInvalidCurrency()
    {
        $this->request->setCurrency('INVALID');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testValidationWithValidCurrencies()
    {
        $validCurrencies = ['MYR', 'SGD', 'USD', 'EUR', 'THB', 'VND', 'IDR'];
        
        foreach ($validCurrencies as $currency) {
            $this->request->setCurrency($currency);
            $data = $this->request->getData();
            $this->assertEquals($currency, $data['purchase']['currency']);
        }
    }
    
    public function testValidationWithInvalidEmail()
    {
        $card = $this->getValidCard();
        $card['email'] = 'invalid-email';
        $this->request->setCard($card);
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testValidationWithInvalidUrl()
    {
        $this->request->setSuccessUrl('not-a-url');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testValidationWithValidUrls()
    {
        $this->request->setSuccessUrl('https://example.com/success');
        $this->request->setCancelUrl('https://example.com/cancel');
        $this->request->setFailureUrl('https://example.com/failure');
        $this->request->setWebhookUrl('https://example.com/webhook');
        
        $data = $this->request->getData();
        
        $this->assertEquals('https://example.com/success', $data['success_redirect']);
        $this->assertEquals('https://example.com/cancel', $data['cancel_redirect']);
        $this->assertEquals('https://example.com/failure', $data['failure_redirect']);
        $this->assertEquals('https://example.com/webhook', $data['webhook_url']);
    }
    
    public function testDueDateHandling()
    {
        $dueDate = new \DateTime('+1 day');
        $this->request->setDue($dueDate);
        
        $data = $this->request->getData();
        
        $this->assertArrayHasKey('due', $data['purchase']);
        $this->assertEquals($dueDate->getTimestamp(), $data['purchase']['due']);
    }
    
    public function testInvalidDueDate()
    {
        $pastDate = new \DateTime('-1 day');
        $this->request->setDue($pastDate);
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testTimezoneHandling()
    {
        $this->request->setTimezone('Asia/Kuala_Lumpur');
        
        $data = $this->request->getData();
        
        $this->assertArrayHasKey('timezone', $data['purchase']);
        $this->assertEquals('Asia/Kuala_Lumpur', $data['purchase']['timezone']);
    }
    
    public function testCreatorAgentHandling()
    {
        $this->request->setCreatorAgent('TestApp/1.0');
        
        $data = $this->request->getData();
        
        $this->assertArrayHasKey('creator_agent', $data['purchase']);
        $this->assertEquals('TestApp/1.0', $data['purchase']['creator_agent']);
    }
    
    public function testFallbackUrlHandling()
    {
        // Test fallback to standard Omnipay methods
        $this->request->setReturnUrl('https://example.com/return');
        $this->request->setCancelUrl('https://example.com/cancel');
        $this->request->setNotifyUrl('https://example.com/notify');
        
        $data = $this->request->getData();
        
        $this->assertEquals('https://example.com/return', $data['success_redirect']);
        $this->assertEquals('https://example.com/cancel', $data['cancel_redirect']);
        $this->assertEquals('https://example.com/notify', $data['webhook_url']);
    }
    
    public function testProductDetailsStructure()
    {
        $this->request->setDescription('Test Product');
        
        $data = $this->request->getData();
        
        $this->assertArrayHasKey('products', $data['purchase']);
        $this->assertIsArray($data['purchase']['products']);
        $this->assertCount(1, $data['purchase']['products']);
        
        $product = $data['purchase']['products'][0];
        $this->assertEquals('Test Product', $product['name']);
        $this->assertEquals(1000, $product['price']); // 10.00 * 100
        $this->assertEquals(1, $product['quantity']);
    }
    
    public function testClientDetailsStructure()
    {
        $card = $this->getValidCard();
        $this->request->setCard($card);
        
        $data = $this->request->getData();
        
        $this->assertArrayHasKey('client', $data);
        $this->assertEquals('test@example.com', $data['client']['email']);
        $this->assertEquals('John Doe', $data['client']['full_name']);
        $this->assertEquals('+60123456789', $data['client']['phone']);
    }
    
    public function testLoggingOnValidation()
    {
        $this->request->getData();
        
        $this->assertTrue($this->logger->hasInfo('Creating purchase request'));
    }
    
    public function testLoggingOnValidationError()
    {
        $this->request->setApiKey('');
        
        try {
            $this->request->getData();
        } catch (InvalidRequestException $e) {
            // Expected exception
        }
        
        $this->assertTrue($this->logger->hasError('Purchase request validation failed'));
    }
    
    public function testGetEndpoint()
    {
        $endpoint = $this->request->getEndpoint();
        
        $this->assertEquals('https://gate.chip-in.asia/api/v1/purchases/', $endpoint);
    }
    
    public function testUserAgentHeader()
    {
        $this->request->setCreatorAgent('TestApp/1.0');
        
        // Test that User-Agent is set correctly in headers
        $headers = $this->request->getHeaders();
        
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertEquals('TestApp/1.0', $headers['User-Agent']);
    }
    
    public function testReferenceHandling()
    {
        $this->request->setTransactionId('TEST123');
        
        $data = $this->request->getData();
        
        $this->assertArrayHasKey('reference', $data['purchase']);
        $this->assertEquals('TEST123', $data['purchase']['reference']);
    }
    
    public function getValidCard()
    {
        return [
            'email' => 'test@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'phone' => '+60123456789'
        ];
    }
}