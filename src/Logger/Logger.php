<?php

declare(strict_types=1);

namespace Aphrodite\Logger;

require_once __DIR__ . '/Level.php';
require_once __DIR__ . '/LogEntry.php';
require_once __DIR__ . '/LoggerInterface.php';
require_once __DIR__ . '/AbstractLogger.php';
require_once __DIR__ . '/FileLogger.php';
require_once __DIR__ . '/DailyLogger.php';
require_once __DIR__ . '/ConsoleLogger.php';

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
