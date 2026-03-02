<?php

declare(strict_types=1);

namespace Aphrodite\Database;

/**
 * Database connection manager using PDO.
 */
class Connection
{
    private static ?\PDO $pdo = null;

    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'aphrodite',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ], $config);
    }

    /**
     * Get or create PDO instance.
     */
    public static function getInstance(array $config = []): \PDO
    {
        if (self::$pdo === null) {
            self::$pdo = (new self($config))->connect();
        }
        return self::$pdo;
    }

    /**
     * Establish PDO connection.
     */
    public function connect(): \PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new \PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            $options
        );
    }

    /**
     * Set global PDO instance (for dependency injection).
     */
    public static function setPdo(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Reset connection (useful for testing).
     */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
