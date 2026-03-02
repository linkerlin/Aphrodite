<?php

declare(strict_types=1);

namespace Aphrodite\Logger;

/**
 * Log message entry.
 */
class LogEntry
{
    public string $level;
    public string $message;
    public array $context;
    public int $timestamp;

    public function __construct(string $level, string $message, array $context = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
        $this->timestamp = time();
    }
}
