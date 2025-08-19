<?php

namespace Omnipay\ChipInAsia\Tests\Message;

use Omnipay\ChipInAsia\Message\CompletePurchaseResponse;
use Omnipay\Tests\TestCase;

class CompletePurchaseResponseTest extends TestCase
{
    public function testConstructorWithStringData()
    {
        $jsonData = json_encode([
            'id' => 'purchase_123',
            'status' => 'paid',
            'total_override' => 1000,
            'currency' => 'MYR'
        ]);
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), $jsonData);
        $this->assertEquals('purchase_123', $response->getPurchaseId());
        $this->assertEquals('paid', $response->getStatus());
    }

    public function testConstructorWithArrayData()
    {
        $data = [
            'id' => 'purchase_456',
            'status' => 'pending',
            'total_override' => 2000,
            'currency' => 'SGD'
        ];
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), $data);
        $this->assertEquals('purchase_456', $response->getPurchaseId());
        $this->assertEquals('pending', $response->getStatus());
    }

    public function testConstructorWithInvalidJson()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), 'invalid json');
        $this->assertNull($response->getPurchaseId());
        $this->assertFalse($response->isSuccessful());
    }

    public function testIsSuccessful()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertTrue($response->isSuccessful());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'pending']);
        $this->assertFalse($response->isSuccessful());
    }

    public function testIsPending()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'pending']);
        $this->assertTrue($response->isPending());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'created']);
        $this->assertTrue($response->isPending());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertFalse($response->isPending());
    }

    public function testIsCancelled()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'cancelled']);
        $this->assertTrue($response->isCancelled());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'expired']);
        $this->assertTrue($response->isCancelled());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'failed']);
        $this->assertTrue($response->isCancelled());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertFalse($response->isCancelled());
    }

    public function testIsFailed()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'failed']);
        $this->assertTrue($response->isFailed());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertFalse($response->isFailed());
    }

    public function testIsExpired()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'expired']);
        $this->assertTrue($response->isExpired());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertFalse($response->isExpired());
    }

    public function testGetAmount()
    {
        // Test with total_override
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'purchase' => ['total_override' => 1500, 'total' => 1000]
        ]);
        $this->assertEquals(15.00, $response->getAmount());
        
        // Test with purchase.total fallback
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'purchase' => ['total' => 2000]
        ]);
        $this->assertEquals(20.00, $response->getAmount());
    }

    public function testGetAmountInteger()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'purchase' => ['total_override' => 1500]
        ]);
        $this->assertEquals(1500, $response->getAmountInteger());
    }

    public function testGetCurrency()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'purchase' => ['currency' => 'MYR']
        ]);
        $this->assertEquals('MYR', $response->getCurrency());
        
        // Test with different currency
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'purchase' => ['currency' => 'SGD']
        ]);
        $this->assertEquals('SGD', $response->getCurrency());
    }

    public function testGetTransactionReference()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'id' => 'purchase_789'
        ]);
        $this->assertEquals('purchase_789', $response->getTransactionReference());
    }

    public function testGetGatewayTransactionId()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'transaction_data' => ['id' => 'txn_123456']
        ]);
        $this->assertEquals('txn_123456', $response->getGatewayTransactionId());
    }

    public function testGetGatewayReference()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'transaction_data' => ['reference' => 'gw_ref_789']
        ]);
        $this->assertEquals('gw_ref_789', $response->getGatewayReference());
    }

    public function testGetMessage()
    {
        // Test with errors array (field-based format)
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'errors' => ['payment' => ['Payment failed']]
        ]);
        $this->assertEquals('payment: Payment failed', $response->getMessage());
        
        // Test with error string
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'error' => 'Invalid payment method'
        ]);
        $this->assertEquals('Invalid payment method', $response->getMessage());
        
        // Test status-based messages
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'status' => 'paid'
        ]);
        $this->assertEquals('Payment completed successfully', $response->getMessage());
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'status' => 'created'
        ]);
        $this->assertEquals('Payment is pending completion', $response->getMessage());
    }

    public function testGetPurchaseDetails()
    {
        $purchaseData = [
            'total' => 1000,
            'currency' => 'MYR',
            'products' => [['name' => 'Test Product']]
        ];
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'purchase' => $purchaseData
        ]);
        
        $this->assertEquals($purchaseData, $response->getPurchaseDetails());
    }

    public function testGetClientDetails()
    {
        $clientData = [
            'email' => 'test@example.com',
            'phone' => '+60123456789'
        ];
        
        $response = new CompletePurchaseResponse($this->getMockRequest(), [
            'client' => $clientData
        ]);
        
        $this->assertEquals($clientData, $response->getClientDetails());
    }

    public function testGetData()
    {
        $data = ['id' => 'purchase_123', 'status' => 'paid'];
        $response = new CompletePurchaseResponse($this->getMockRequest(), $data);
        $this->assertEquals($data, $response->getData());
    }
}