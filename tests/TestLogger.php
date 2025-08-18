<?php

namespace Omnipay\ChipInAsia;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Simple test logger for unit tests
 */
class TestLogger implements LoggerInterface
{
    private $logs = [];

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
    }

    public function hasInfo($message): bool
    {
        foreach ($this->logs as $log) {
            if ($log['level'] === LogLevel::INFO && strpos($log['message'], $message) !== false) {
                return true;
            }
        }
        return false;
    }

    public function hasError($message): bool
    {
        foreach ($this->logs as $log) {
            if ($log['level'] === LogLevel::ERROR && strpos($log['message'], $message) !== false) {
                return true;
            }
        }
        return false;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function clear(): void
    {
        $this->logs = [];
    }
}