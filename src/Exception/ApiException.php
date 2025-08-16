<?php

namespace Omnipay\ChipInAsia\Exception;

/**
 * Exception thrown when API communication fails
 */
class ApiException extends ChipInAsiaException
{
    public function __construct(
        string $message = 'API communication failed',
        int $code = 0,
        \Throwable $previous = null,
        array $responseData = null,
        int $httpStatusCode = null
    ) {
        parent::__construct($message, $code, $previous, $responseData, $httpStatusCode);
    }

    /**
     * Create exception from HTTP response
     */
    public static function fromResponse(array $responseData, int $httpStatusCode): self
    {
        $message = $responseData['message'] ?? 'API request failed';
        $code = $responseData['code'] ?? $httpStatusCode;
        
        return new self($message, $code, null, $responseData, $httpStatusCode);
    }
}