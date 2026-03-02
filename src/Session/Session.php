<?php

declare(strict_types=1);

namespace Aphrodite\Session;

/**
 * Session interface.
 */
interface SessionInterface
{
    public function start(): bool;
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function forget(string $key): void;
    public function flush(): void;
    public function regenerate(): bool;
    public function invalidate(): bool;
    public function getId(): string;
    public function save(): bool;
    public function close(): bool;
}

/**
 * Native PHP session implementation.
 */
class Session implements SessionInterface
{
    protected string $name = 'APHRODITE_SESSION';
    protected string $path = '/';
    protected ?string $domain = null;
    protected bool $secure = false;
    protected bool $httponly = true;
    protected ?string $sameSite = 'Lax';

    public function __construct(?string $name = null)
    {
        if ($name !== null) {
            $this->name = $name;
        }
    }

    /**
     * Start the session.
     */
    public function start(): bool
    {
        if ($this->isStarted()) {
            return true;
        }

        session_name($this->name);
        
        $options = [
            'cookie_lifetime' => 0,
            'cookie_path' => $this->path,
            'cookie_domain' => $this->domain ?? '',
            'cookie_secure' => $this->secure,
            'cookie_httponly' => $this->httponly,
            'cookie_samesite' => $this->sameSite ?? '',
            'use_strict_mode' => 1,
            'use_only_cookies' => 1,
        ];

        foreach ($options as $key => $value) {
            if ($value !== '') {
                ini_set("session.{$key}", (string)$value);
            }
        }

        return session_start();
    }

    /**
     * Check if session is started.
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Get session value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value.
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session key exists.
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value.
     */
    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data.
     */
    public function flush(): void
    {
        $_SESSION = [];
    }

    /**
     * Regenerate session ID.
     */
    public function regenerate(): bool
    {
        return session_regenerate_id(true);
    }

    /**
     * Invalidate and destroy session.
     */
    public function invalidate(): bool
    {
        $this->flush();
        return session_destroy();
    }

    /**
     * Get session ID.
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Save session data.
     */
    public function save(): bool
    {
        return session_write_close();
    }

    /**
     * Close session.
     */
    public function close(): bool
    {
        if ($this->isStarted()) {
            return $this->save();
        }
        return true;
    }

    /**
     * Flash a value for the next request.
     */
    public function flash(string $key, mixed $value = null): mixed
    {
        if ($value === null) {
            $flashKey = '_flash_' . $key;
            $value = $this->get($flashKey);
            $this->forget($flashKey);
            return $value;
        }

        $this->set('_flash_' . $key, $value);
        return null;
    }

    /**
     * Set session configuration.
     */
    public function setConfig(array $config): self
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * Token for CSRF protection.
     */
    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->set('_token', bin2hex(random_bytes(32)));
        }
        return $this->get('_token');
    }

    /**
     * Verify CSRF token.
     */
    public function verifyToken(string $token): bool
    {
        return hash_equals($this->token(), $token);
    }
}

/**
 * Session manager with helpers.
 */
class SessionManager
{
    protected static ?Session $instance = null;

    /**
     * Get session instance.
     */
    public static function getInstance(): Session
    {
        if (self::$instance === null) {
            self::$instance = new Session();
            self::$instance->start();
        }
        return self::$instance;
    }

    /**
     * Set session instance.
     */
    public static function setInstance(Session $session): void
    {
        self::$instance = $session;
    }

    /**
     * Magic method for static calls.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        return self::getInstance()->$method(...$args);
    }
}
