<?php

declare(strict_types=1);

namespace Aphrodite\Logger;

/**
 * File-based logger.
 */
class FileLogger extends AbstractLogger
{
    protected string $path;
    /**
     * @var resource|null
     */
    protected mixed $handle = null;

    public function __construct(string $path, ?int $minLevel = null)
    {
        $this->path = $path;
        parent::__construct($minLevel);

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function writeLog(LogEntry $entry): void
    {
        $date = date('Y-m-d H:i:s');
        $level = strtoupper($entry->level);
        $message = $this->formatMessage($entry);

        $line = "[{$date}] {$level}: {$message}\n";

        file_put_contents($this->path, $line, FILE_APPEND);
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

    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
