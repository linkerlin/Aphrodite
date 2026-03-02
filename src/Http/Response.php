<?php

declare(strict_types=1);

namespace Aphrodite\Http;

/**
 * HTTP Response representation.
 */
class Response
{
    protected mixed $content;
    protected int $statusCode;
    protected array $headers;

    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    public function __construct(mixed $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Create a new response.
     */
    public static function make(mixed $content = '', int $statusCode = 200, array $headers = []): self
    {
        return new self($content, $statusCode, $headers);
    }

    /**
     * Create a JSON response.
     */
    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new self($content, $statusCode, $headers);
    }

    /**
     * Create a success response.
     */
    public static function success(mixed $data = null, ?string $message = 'Success', int $statusCode = 200): self
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return self::json($response, $statusCode);
    }

    /**
     * Create an error response.
     */
    public static function error(mixed $message = 'Error', int $statusCode = 400, ?array $errors = null): self
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return self::json($response, $statusCode);
    }

    /**
     * Create a not found response.
     */
    public static function notFound(?string $message = 'Resource not found'): self
    {
        return self::error($message, self::HTTP_NOT_FOUND);
    }

    /**
     * Create an unauthorized response.
     */
    public static function unauthorized(?string $message = 'Unauthorized'): self
    {
        return self::error($message, self::HTTP_UNAUTHORIZED);
    }

    /**
     * Create a forbidden response.
     */
    public static function forbidden(?string $message = 'Forbidden'): self
    {
        return self::error($message, self::HTTP_FORBIDDEN);
    }

    /**
     * Create a validation error response.
     */
    public static function validationError(array $errors, ?string $message = 'Validation failed'): self
    {
        return self::error($message, self::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Create an internal server error response.
     */
    public static function serverError(?string $message = 'Internal server error'): self
    {
        return self::error($message, self::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Create a redirect response.
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    /**
     * Send file download response.
     */
    public static function download(string $filePath, ?string $name = null): self
    {
        $name = $name ?? basename($filePath);
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
            'Content-Length' => (string) filesize($filePath),
        ];

        return new self(file_get_contents($filePath), 200, $headers);
    }

    /**
     * Get response content.
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set response content.
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set status code.
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get header value.
     */
    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Set header.
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Get all headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Send the response to the client.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo $this->content;
    }

    /**
     * Get prepared response string.
     */
    public function prepare(): string
    {
        $output = '';

        foreach ($this->headers as $key => $value) {
            $output .= "{$key}: {$value}\r\n";
        }

        $output .= "\r\n";
        $output .= is_string($this->content) ? $this->content : json_encode($this->content);

        return $output;
    }

    /**
     * Convert to string.
     */
    public function __toString(): string
    {
        return $this->prepare();
    }
}
