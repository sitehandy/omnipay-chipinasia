<?php

namespace Omnipay\ChipInAsia\Exception;

/**
 * Exception thrown when webhook verification fails
 */
class WebhookException extends ChipInAsiaException
{
    public function __construct(
        string $message = 'Webhook verification failed',
        int $code = 401,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}