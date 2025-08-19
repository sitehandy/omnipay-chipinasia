<?php

namespace Omnipay\ChipInAsia\Tests\Message;

use Omnipay\ChipInAsia\Message\WebhookResponse;
use Omnipay\Tests\TestCase;

class WebhookResponseTest extends TestCase
{
    public function testConstructorWithStringData()
    {
        $jsonData = json_encode([
            'id' => 'purchase_123',
            'status' => 'paid',
            'total_override' => 1000,
            'currency' => 'MYR'
        ]);
        
        $response = new WebhookResponse($this->getMockRequest(), $jsonData);
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
        
        $response = new WebhookResponse($this->getMockRequest(), $data);
        $this->assertEquals('purchase_456', $response->getPurchaseId());
        $this->assertEquals('pending', $response->getStatus());
    }

    public function testConstructorWithInvalidJson()
    {
        $response = new WebhookResponse($this->getMockRequest(), 'invalid json');
        $this->assertNull($response->getPurchaseId());
        $this->assertFalse($response->isPaymentSuccessful());
    }

    public function testIsPaymentSuccessful()
    {
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertTrue($response->isPaymentSuccessful());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'completed']);
        $this->assertTrue($response->isPaymentSuccessful());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'success']);
        $this->assertTrue($response->isPaymentSuccessful());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'pending']);
        $this->assertFalse($response->isPaymentSuccessful());
    }

    public function testIsPaymentPending()
    {
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'pending']);
        $this->assertTrue($response->isPaymentPending());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'created']);
        $this->assertTrue($response->isPaymentPending());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertFalse($response->isPaymentPending());
    }

    public function testIsPaymentCancelled()
    {
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'cancelled']);
        $this->assertTrue($response->isPaymentCancelled());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'canceled']);
        $this->assertTrue($response->isPaymentCancelled());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'expired']);
        $this->assertFalse($response->isPaymentCancelled());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'failed']);
        $this->assertFalse($response->isPaymentCancelled());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertFalse($response->isPaymentCancelled());
    }

    public function testIsPaymentExpired()
    {
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'expired']);
        $this->assertTrue($response->isPaymentExpired());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertFalse($response->isPaymentExpired());
    }

    public function testGetAmount()
    {
        // Test with purchase.total_override
        $response = new WebhookResponse($this->getMockRequest(), [
            'purchase' => ['total_override' => 1500, 'total' => 1000]
        ]);
        $this->assertEquals('15.00', $response->getAmount());
        
        // Test with purchase.total fallback
        $response = new WebhookResponse($this->getMockRequest(), [
            'purchase' => ['total' => 1000]
        ]);
        $this->assertEquals('10.00', $response->getAmount());
    }

    public function testGetAmountInteger()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'purchase' => ['total_override' => 1500]
        ]);
        $this->assertEquals(1500, $response->getAmountInteger());
    }

    public function testGetCurrency()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'purchase' => ['currency' => 'MYR']
        ]);
        $this->assertEquals('MYR', $response->getCurrency());
        
        // Test fallback to root currency
        $response = new WebhookResponse($this->getMockRequest(), [
            'currency' => 'SGD'
        ]);
        $this->assertEquals('SGD', $response->getCurrency());
    }

    public function testGetPaymentMethod()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'payment_method' => 'fpx',
            'payment' => ['method' => 'card']
        ]);
        $this->assertEquals('fpx', $response->getPaymentMethod());
        
        // Test fallback
        $response = new WebhookResponse($this->getMockRequest(), [
            'payment' => ['method' => 'ewallet']
        ]);
        $this->assertEquals('ewallet', $response->getPaymentMethod());
    }

    public function testGetPaymentDate()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'paid_at' => '2023-01-01T12:00:00Z'
        ]);
        $date = $response->getPaymentDate();
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2023-01-01T12:00:00+00:00', $date->format('c'));
        
        // Test fallback to completed_at
        $response = new WebhookResponse($this->getMockRequest(), [
            'completed_at' => '2023-01-02T12:00:00Z'
        ]);
        $date = $response->getPaymentDate();
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2023-01-02T12:00:00+00:00', $date->format('c'));
    }

    public function testGetCreatedDate()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'created_at' => '2023-01-01T10:00:00Z'
        ]);
        $date = $response->getCreatedDate();
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2023-01-01T10:00:00+00:00', $date->format('c'));
        
        // Test with no created_at
        $response = new WebhookResponse($this->getMockRequest(), []);
        $this->assertNull($response->getCreatedDate());
    }

    public function testGetTransactionId()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'reference' => 'txn_fallback',
            'transaction_id' => 'txn_123'
        ]);
        $this->assertEquals('txn_fallback', $response->getTransactionId());
        
        // Test fallback to transaction_id
        $response = new WebhookResponse($this->getMockRequest(), [
            'transaction_id' => 'txn_456'
        ]);
        $this->assertEquals('txn_456', $response->getTransactionId());
    }

    public function testGetPurchaseId()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'id' => 'purchase_789'
        ]);
        $this->assertEquals('purchase_789', $response->getPurchaseId());
    }

    public function testGetCustomerEmail()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'client' => ['email' => 'test@example.com']
        ]);
        $this->assertEquals('test@example.com', $response->getCustomerEmail());
    }

    public function testGetCustomerPhone()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'client' => ['phone' => '+60123456789']
        ]);
        $this->assertEquals('+60123456789', $response->getCustomerPhone());
    }

    public function testGetPurchaseDetails()
    {
        $purchaseData = [
            'total' => 1000,
            'currency' => 'MYR',
            'products' => [['name' => 'Test Product']]
        ];
        
        $response = new WebhookResponse($this->getMockRequest(), [
            'purchase' => $purchaseData
        ]);
        
        $this->assertEquals($purchaseData, $response->getPurchaseDetails());
    }

    public function testGetProducts()
    {
        $products = [
            ['name' => 'Product 1', 'price' => 500],
            ['name' => 'Product 2', 'price' => 300]
        ];
        
        $response = new WebhookResponse($this->getMockRequest(), [
            'purchase' => ['products' => $products]
        ]);
        
        $this->assertEquals($products, $response->getProducts());
    }

    public function testGetPaymentStatus()
    {
        $response = new WebhookResponse($this->getMockRequest(), [
            'status' => 'completed'
        ]);
        $this->assertEquals('completed', $response->getStatus());
    }

    public function testGetMessage()
    {
        // Test with errors array
        $response = new WebhookResponse($this->getMockRequest(), [
            'errors' => [['detail' => 'Payment failed']]
        ]);
        $this->assertEquals('Payment failed', $response->getMessage());
        
        // Test with error string
        $response = new WebhookResponse($this->getMockRequest(), [
            'error' => 'Invalid payment method'
        ]);
        $this->assertEquals('Invalid payment method', $response->getMessage());
        
        // Test status-based messages
        $response = new WebhookResponse($this->getMockRequest(), [
            'status' => 'paid'
        ]);
        $this->assertEquals('Payment completed successfully', $response->getMessage());
        
        $response = new WebhookResponse($this->getMockRequest(), [
            'status' => 'created'
        ]);
        $this->assertEquals('Payment created and awaiting processing', $response->getMessage());
        
        $response = new WebhookResponse($this->getMockRequest(), [
            'status' => 'canceled'
        ]);
        $this->assertEquals('Payment was cancelled', $response->getMessage());
    }

    public function testGetData()
    {
        $data = ['id' => 'purchase_123', 'status' => 'paid'];
        $response = new WebhookResponse($this->getMockRequest(), $data);
        $this->assertEquals($data, $response->getData());
    }

    public function testIsSuccessful()
    {
        // Webhook processing is always successful if we reach this point
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'failed']);
        $this->assertTrue($response->isSuccessful());
        
        $response = new WebhookResponse($this->getMockRequest(), ['status' => 'paid']);
        $this->assertTrue($response->isSuccessful());
    }
}