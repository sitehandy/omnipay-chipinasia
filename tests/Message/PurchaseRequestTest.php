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
        $validCurrencies = ['MYR', 'SGD', 'USD', 'EUR'];
        
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
    
    public function testGetEndpointInTestMode()
    {
        $this->request->setTestMode(true);
        $endpoint = $this->request->getEndpoint();
        
        $this->assertEquals('https://gate.chip-in.asia/api/v1/purchases/', $endpoint);
    }
    
    public function testGetEndpointInLiveMode()
    {
        $this->request->setTestMode(false);
        $endpoint = $this->request->getEndpoint();
        
        $this->assertEquals('https://gate.chip-in.asia/api/v1/purchases/', $endpoint);
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