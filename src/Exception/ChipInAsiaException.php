<?php

namespace Omnipay\ChipInAsia\Exception;

use Omnipay\Common\Exception\OmnipayException;

/**
 * Base exception class for Chip-in Asia gateway
 */
class ChipInAsiaException extends \Exception implements OmnipayException
{
    /**
     * @var array|null
     */
    protected $responseData;

    /**
     * @var int|null
     */
    protected $httpStatusCode;

    public function __construct(
        string $message = '',
        int $code = 0,
        \Throwable $previous = null,
        array $responseData = null,
        int $httpStatusCode = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * Get the response data that caused the exception
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * Get the HTTP status code
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
}