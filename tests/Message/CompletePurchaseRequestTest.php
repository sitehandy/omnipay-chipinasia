<?php

namespace Omnipay\ChipInAsia\Message;

use Omnipay\Tests\TestCase;
use Omnipay\ChipInAsia\Exception\InvalidRequestException;
use Psr\Log\Test\TestLogger;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class CompletePurchaseRequestTest extends TestCase
{
    private $request;
    private $logger;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->logger = new TestLogger();
        
        // Create a mock HTTP request with purchase_id parameter
        $httpRequest = new HttpRequest(['purchase_id' => 'test_purchase_123']);
        
        $this->request = new CompletePurchaseRequest(
            $this->getHttpClient(),
            $httpRequest,
            $this->logger
        );
        
        $this->request->setApiKey('test_api_key');
    }
    
    public function testGetDataWithValidParameters()
    {
        $data = $this->request->getData();
        
        $this->assertArrayHasKey('purchase_id', $data);
        $this->assertEquals('test_purchase_123', $data['purchase_id']);
    }
    
    public function testValidationWithMissingApiKey()
    {
        $this->request->setApiKey('');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request validation failed');
        
        $this->request->getData();
    }
    
    public function testMissingPurchaseId()
    {
        // Create request without purchase_id
        $httpRequest = new HttpRequest();
        $request = new CompletePurchaseRequest(
            $this->getHttpClient(),
            $httpRequest,
            $this->logger
        );
        $request->setApiKey('test_api_key');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Missing purchase_id parameter');
        
        $request->getData();
    }
    
    public function testPurchaseIdFromPostData()
    {
        // Create request with purchase_id in POST data
        $httpRequest = new HttpRequest([], ['purchase_id' => 'post_purchase_456']);
        $request = new CompletePurchaseRequest(
            $this->getHttpClient(),
            $httpRequest,
            $this->logger
        );
        $request->setApiKey('test_api_key');
        
        $data = $request->getData();
        
        $this->assertEquals('post_purchase_456', $data['purchase_id']);
    }
    
    public function testPurchaseIdFromTransactionReference()
    {
        // Create request without purchase_id in query/post
        $httpRequest = new HttpRequest();
        $request = new CompletePurchaseRequest(
            $this->getHttpClient(),
            $httpRequest,
            $this->logger
        );
        $request->setApiKey('test_api_key');
        $request->setTransactionReference('ref_purchase_789');
        
        $data = $request->getData();
        
        $this->assertEquals('ref_purchase_789', $data['purchase_id']);
    }
    
    public function testPurchaseIdSanitization()
    {
        // Create request with malicious purchase_id
        $httpRequest = new HttpRequest(['purchase_id' => 'test<script>alert(1)</script>_123']);
        $request = new CompletePurchaseRequest(
            $this->getHttpClient(),
            $httpRequest,
            $this->logger
        );
        $request->setApiKey('test_api_key');
        
        $data = $request->getData();
        
        // Should be sanitized to remove script tags
        $this->assertEquals('testscriptalert1script_123', $data['purchase_id']);
    }
    
    public function testInvalidPurchaseIdFormat()
    {
        // Create request with completely invalid purchase_id
        $httpRequest = new HttpRequest(['purchase_id' => '<>!@#$%^&*()']);
        $request = new CompletePurchaseRequest(
            $this->getHttpClient(),
            $httpRequest,
            $this->logger
        );
        $request->setApiKey('test_api_key');
        
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid purchase ID format');
        
        $request->getData();
    }
    
    public function testValidPurchaseIdFormats()
    {
        $validIds = [
            'purchase_123',
            'PURCHASE-456',
            'purchase_test_789',
            'ABC123DEF456',
            'test-purchase-123'
        ];
        
        foreach ($validIds as $purchaseId) {
            $httpRequest = new HttpRequest(['purchase_id' => $purchaseId]);
            $request = new CompletePurchaseRequest(
                $this->getHttpClient(),
                $httpRequest,
                $this->logger
            );
            $request->setApiKey('test_api_key');
            
            $data = $request->getData();
            $this->assertEquals($purchaseId, $data['purchase_id']);
        }
    }
    
    public function testLoggingOnSuccess()
    {
        $this->request->getData();
        
        $this->assertTrue($this->logger->hasInfo('Creating complete purchase request'));
    }
    
    public function testLoggingOnValidationError()
    {
        $this->request->setApiKey('');
        
        try {
            $this->request->getData();
        } catch (InvalidRequestException $e) {
            // Expected exception
        }
        
        $this->assertTrue($this->logger->hasError('Complete purchase request validation failed'));
    }
    
    public function testGetEndpoint()
    {
        $this->request->setTestMode(false);
        $endpoint = $this->request->getEndpoint();
        
        $this->assertEquals('https://gate.chip-in.asia/api/v1/purchases/', $endpoint);
    }
}