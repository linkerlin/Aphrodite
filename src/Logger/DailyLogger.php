<?php

declare(strict_types=1);

namespace Aphrodite\Logger;

/**
 * Daily file logger (rotates log files by day).
 */
class DailyLogger extends FileLogger
{
    protected string $path;

    public function __construct(string $path, ?int $minLevel = null)
    {
        $this->path = $path;
        $path = $this->getDailyPath();
        parent::__construct($path, $minLevel);
    }

    protected function getDailyPath(): string
    {
        $dir = dirname($this->path);
        $basename = basename($this->path, '.log');
        $date = date('Y-m-d');

        return $dir . '/' . $basename . '_' . $date . '.log';
    }
}
