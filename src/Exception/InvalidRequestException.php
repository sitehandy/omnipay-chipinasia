<?php

namespace Omnipay\ChipInAsia\Exception;

/**
 * Exception thrown when request data is invalid
 */
class InvalidRequestException extends ChipInAsiaException
{
    /**
     * @var array
     */
    protected $validationErrors;

    public function __construct(
        string $message = 'Invalid request data',
        array $validationErrors = [],
        int $code = 400,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->validationErrors = $validationErrors;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}