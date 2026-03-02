<?php

declare(strict_types=1);

namespace Aphrodite\Logger;

/**
 * Console logger (outputs to stdout).
 */
class ConsoleLogger extends AbstractLogger
{
    protected bool $colored;

    public function __construct(?int $minLevel = null, bool $colored = true)
    {
        parent::__construct($minLevel);
        $this->colored = $colored;
    }

    protected function writeLog(LogEntry $entry): void
    {
        $date = date('Y-m-d H:i:s');
        $level = strtoupper($entry->level);
        $message = $this->formatMessage($entry);

        $line = "[{$date}] {$level}: {$message}";

        if ($this->colored) {
            $line = $this->colorize($line, $entry->level);
        }

        echo $line . "\n";
    }

    protected function formatMessage(LogEntry $entry): string
    {
        $message = $entry->message;

        if (!empty($entry->context)) {
            $contextStr = json_encode($entry->context, JSON_UNESCAPED_UNICODE);
            $message .= ' ' . $contextStr;
        }

        return $message;
    }

    protected function colorize(string $line, string $level): string
    {
        $colors = [
            Level::DEBUG => "\033[36m",
            Level::INFO => "\033[32m",
            Level::NOTICE => "\033[34m",
            Level::WARNING => "\033[33m",
            Level::ERROR => "\033[31m",
            Level::CRITICAL => "\033[35m",
            Level::ALERT => "\033[31m",
            Level::EMERGENCY => "\033[41;37m",
        ];

        $color = $colors[$level] ?? "\033[0m";
        $reset = "\033[0m";

        return $color . $line . $reset;
    }
}
