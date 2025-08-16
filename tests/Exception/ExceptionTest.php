<?php

namespace Omnipay\ChipInAsia\Exception;

use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testChipInAsiaException()
    {
        $responseData = ['error' => 'Test error'];
        $httpStatusCode = 400;
        
        $exception = new ChipInAsiaException(
            'Test message',
            123,
            null,
            $responseData,
            $httpStatusCode
        );
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
        $this->assertEquals($responseData, $exception->getResponseData());
        $this->assertEquals($httpStatusCode, $exception->getHttpStatusCode());
    }
    
    public function testInvalidRequestException()
    {
        $validationErrors = ['field1' => 'Error 1', 'field2' => 'Error 2'];
        
        $exception = new InvalidRequestException(
            'Validation failed',
            $validationErrors,
            400
        );
        
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertEquals($validationErrors, $exception->getValidationErrors());
    }
    
    public function testApiException()
    {
        $responseData = ['message' => 'API Error', 'code' => 500];
        $httpStatusCode = 500;
        
        $exception = ApiException::fromResponse($responseData, $httpStatusCode);
        
        $this->assertEquals('API Error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals($responseData, $exception->getResponseData());
        $this->assertEquals($httpStatusCode, $exception->getHttpStatusCode());
    }
    
    public function testApiExceptionWithoutMessage()
    {
        $responseData = ['code' => 404];
        $httpStatusCode = 404;
        
        $exception = ApiException::fromResponse($responseData, $httpStatusCode);
        
        $this->assertEquals('API request failed', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }
    
    public function testWebhookException()
    {
        $exception = new WebhookException('Webhook verification failed');
        
        $this->assertEquals('Webhook verification failed', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
    }
    
    public function testWebhookExceptionWithCustomCode()
    {
        $exception = new WebhookException('Custom error', 403);
        
        $this->assertEquals('Custom error', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
    }
}