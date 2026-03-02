<?php

declare(strict_types=1);

namespace Aphrodite\Http;

/**
 * HTTP Request representation.
 */
class Request
{
    protected string $method;
    protected string $uri;
    protected array $query = [];
    protected array $post = [];
    protected array $headers = [];
    protected array $server = [];
    protected mixed $body;
    protected array $attributes = [];

    public function __construct(
        ?string $method = null,
        ?string $uri = null,
        ?array $query = null,
        ?array $post = null,
        ?array $headers = null,
        ?array $server = null,
        mixed $body = null
    ) {
        $this->method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        
        // Parse query string from URI if provided
        if ($query === null) {
            $query = [];
            if (($pos = strpos($this->uri, '?')) !== false) {
                parse_str(substr($this->uri, $pos + 1), $query);
            }
            // Fall back to $_GET if no query string in URI
            if (empty($query)) {
                $query = $_GET;
            }
        }
        
        $this->query = $query;
        $this->post = $post ?? $_POST;
        $this->headers = $headers ?? $this->parseHeaders();
        $this->server = $server ?? $_SERVER;
        $this->body = $body ?? file_get_contents('php://input');
    }

    /**
     * Create request from superglobals.
     */
    public static function capture(): self
    {
        return new self();
    }

    /**
     * Parse headers from SERVER.
     */
    protected function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = substr($key, 5);
                $headers[strtolower(str_replace('_', '-', $header))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request URI.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get path (without query string).
     */
    public function getPath(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        return $path ?? '/';
    }

    /**
     * Get query parameter.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Get post parameter.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get all post parameters.
     */
    public function getPost(): array
    {
        return $this->post;
    }

    /**
     * Get request body.
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * Get JSON body as array.
     */
    public function getJson(): ?array
    {
        if ($this->isJson()) {
            return json_decode($this->body, true);
        }
        return null;
    }

    /**
     * Check if request is JSON.
     */
    public function isJson(): bool
    {
        return $this->header('Content-Type') === 'application/json';
    }

    /**
     * Get header value.
     */
    public function header(string $key, ?string $default = null): ?string
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    /**
     * Get all headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get server variable.
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get all server variables.
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * Get input (merges query and post).
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get all input (merged query and post).
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Check if input exists.
     */
    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]);
    }

    /**
     * Get only specified keys.
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->input($key);
            }
        }
        return $result;
    }

    /**
     * Get except specified keys.
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Set attribute (for routing).
     */
    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get attribute.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Get all attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Check if request method is GET.
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * Check if request method is POST.
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Check if request method is PUT.
     */
    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    /**
     * Check if request method is DELETE.
     */
    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    /**
     * Check if request method is PATCH.
     */
    public function isPatch(): bool
    {
        return $this->method === 'PATCH';
    }

    /**
     * Check if request is AJAX.
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get client IP address.
     */
    public function ip(): ?string
    {
        return $this->server('HTTP_X_FORWARDED_FOR')
            ?? $this->server('HTTP_X_REAL_IP')
            ?? $this->server('REMOTE_ADDR')
            ?? null;
    }

    /**
     * Get user agent.
     */
    public function userAgent(): ?string
    {
        return $this->server('HTTP_USER_AGENT');
    }
}
