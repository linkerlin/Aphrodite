<?php

declare(strict_types=1);

namespace Aphrodite\Logger;

/**
 * Log levels.
 */
class Level
{
    public const DEBUG = 'debug';
    public const INFO = 'info';
    public const NOTICE = 'notice';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const CRITICAL = 'critical';
    public const ALERT = 'alert';
    public const EMERGENCY = 'emergency';

    public static function all(): array
    {
        return [
            self::DEBUG,
            self::INFO,
            self::NOTICE,
            self::WARNING,
            self::ERROR,
            self::CRITICAL,
            self::ALERT,
            self::EMERGENCY,
        ];
    }

    public static function priority(string $level): int
    {
        return match ($level) {
            self::DEBUG => 100,
            self::INFO => 200,
            self::NOTICE => 250,
            self::WARNING => 300,
            self::ERROR => 400,
            self::CRITICAL => 500,
            self::ALERT => 550,
            self::EMERGENCY => 600,
            default => 0,
        };
    }
}

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

/**
 * Logger interface.
 */
interface LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function emergency(string $message, array $context = []): void;
}

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

/**
 * File-based logger.
 */
class FileLogger extends AbstractLogger
{
    protected string $path;
    protected ?resource $handle = null;

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

/**
 * Logger manager.
 */
class Log
{
    protected static ?LoggerInterface $logger = null;

    /**
     * Get logger instance.
     */
    public static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            self::$logger = new ConsoleLogger();
        }

        return self::$logger;
    }

    /**
     * Set logger instance.
     */
    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    /**
     * Create a file logger.
     */
    public static function createFileLogger(string $path, ?int $minLevel = null): FileLogger
    {
        $logger = new FileLogger($path, $minLevel);
        self::$logger = $logger;
        return $logger;
    }

    /**
     * Create a daily logger.
     */
    public static function createDailyLogger(string $path, ?int $minLevel = null): DailyLogger
    {
        $logger = new DailyLogger($path, $minLevel);
        self::$logger = $logger;
        return $logger;
    }

    /**
     * Create a console logger.
     */
    public static function createConsoleLogger(?int $minLevel = null): ConsoleLogger
    {
        $logger = new ConsoleLogger($minLevel);
        self::$logger = $logger;
        return $logger;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        self::getLogger()->log($level, $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::getLogger()->notice($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::getLogger()->alert($message, $context);
    }

    public static function emergency(string $message, array $context = []): void
    {
        self::getLogger()->emergency($message, $context);
    }
}
