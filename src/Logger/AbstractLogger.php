<?php

declare(strict_types=1);

namespace Aphrodite\Logger;

/**
 * Abstract logger with common functionality.
 */
abstract class AbstractLogger implements LoggerInterface
{
    protected int $minLevel = 100; // DEBUG

    public function __construct(?int $minLevel = null)
    {
        $this->minLevel = $minLevel ?? Level::priority(Level::DEBUG);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (Level::priority($level) >= $this->minLevel) {
            $this->writeLog(new LogEntry($level, $message, $context));
        }
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(Level::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(Level::INFO, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(Level::NOTICE, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(Level::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(Level::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(Level::CRITICAL, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(Level::ALERT, $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(Level::EMERGENCY, $message, $context);
    }

    abstract protected function writeLog(LogEntry $entry): void;
}
