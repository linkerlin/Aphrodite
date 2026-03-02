<?php

declare(strict_types=1);

namespace Aphrodite\Exceptions;

use Aphrodite\Container\Container;
use Aphrodite\Logger\LoggerInterface;
use Throwable;

/**
 * Global exception handler for the framework.
 */
class Handler
{
    /**
     * @var array<int, class-string<Throwable>>
     */
    protected array $dontReport = [];

    /**
     * @var array<int, class-string<Throwable>>
     */
    protected array $internalDontReport = [
        ValidationException::class,
        EntityNotFoundException::class,
        RouteNotFoundException::class,
        AuthenticationException::class,
        AuthorizationException::class,
    ];

    protected Container $container;

    protected bool $debug = false;

    public function __construct(Container $container, bool $debug = false)
    {
        $this->container = $container;
        $this->debug = $debug;
    }

    /**
     * Enable or disable debug mode.
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Add exception type to dont-report list.
     *
     * @param class-string<Throwable> $exceptionClass
     */
    public function dontReport(string $exceptionClass): self
    {
        $this->dontReport[] = $exceptionClass;
        return $this;
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception): void
    {
        if ($this->shouldntReport($exception)) {
            return;
        }

        if ($this->container->has(LoggerInterface::class)) {
            $logger = $this->container->get(LoggerInterface::class);
            $logger->error($exception->getMessage(), $this->getExceptionContext($exception));
        }
    }

    /**
     * Determine if the exception should not be reported.
     */
    protected function shouldntReport(Throwable $exception): bool
    {
        foreach (array_merge($this->dontReport, $this->internalDontReport) as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get context for exception logging.
     *
     * @return array<string, mixed>
     */
    protected function getExceptionContext(Throwable $exception): array
    {
        $context = [];

        if ($exception instanceof AphroditeException) {
            $context = $exception->getContext();
        }

        $context['exception'] = get_class($exception);
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();

        return $context;
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render(Throwable $exception): string
    {
        $statusCode = $this->getStatusCode($exception);
        $headers = $this->getHeaders($exception);

        if ($this->isApiRequest()) {
            return $this->renderJson($exception, $statusCode);
        }

        return $this->renderHtml($exception, $statusCode);
    }

    /**
     * Get HTTP status code for exception.
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof EntityNotFoundException || $exception instanceof RouteNotFoundException) {
            return 404;
        }

        if ($exception instanceof AuthenticationException) {
            return 401;
        }

        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        return 500;
    }

    /**
     * Get headers for exception response.
     *
     * @return array<string, string>
     */
    protected function getHeaders(Throwable $exception): array
    {
        if ($exception instanceof AuthenticationException) {
            return ['WWW-Authenticate' => 'Bearer'];
        }

        return [];
    }

    /**
     * Check if request expects JSON.
     */
    protected function isApiRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json') ||
               str_contains($contentType, 'application/json') ||
               str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }

    /**
     * Render exception as JSON.
     */
    protected function renderJson(Throwable $exception, int $statusCode): string
    {
        $response = [
            'success' => false,
            'message' => $exception->getMessage(),
        ];

        if ($exception instanceof ValidationException) {
            $response['errors'] = $exception->getErrors();
        }

        if ($this->debug) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        http_response_code($statusCode);
        header('Content-Type: application/json');

        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Render exception as HTML.
     */
    protected function renderHtml(Throwable $exception, int $statusCode): string
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');

        if ($this->debug) {
            return $this->renderDebugHtml($exception);
        }

        return $this->renderProductionHtml($exception, $statusCode);
    }

    /**
     * Render debug HTML response.
     */
    protected function renderDebugHtml(Throwable $exception): string
    {
        $class = get_class($exception);
        $message = htmlspecialchars($exception->getMessage());
        $file = htmlspecialchars($exception->getFile());
        $line = $exception->getLine();
        $trace = htmlspecialchars($exception->getTraceAsString());

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{$class}: {$message}</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .error { background: #fff; padding: 20px; border-left: 4px solid #e74c3c; margin-bottom: 20px; }
        .error h1 { color: #e74c3c; margin-top: 0; }
        .location { color: #666; margin: 10px 0; }
        .trace { background: #2c3e50; color: #ecf0f1; padding: 15px; overflow-x: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="error">
        <h1>{$class}</h1>
        <p><strong>{$message}</strong></p>
        <p class="location">in {$file} on line {$line}</p>
    </div>
    <div class="trace">{$trace}</div>
</body>
</html>
HTML;
    }

    /**
     * Render production HTML response.
     */
    protected function renderProductionHtml(Throwable $exception, int $statusCode): string
    {
        $statusText = match ($statusCode) {
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            500 => 'Internal Server Error',
            default => 'Error',
        };

        $statusText = htmlspecialchars($statusText);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{$statusCode} {$statusText}</title>
    <style>
        body { font-family: sans-serif; padding: 40px; text-align: center; background: #f9f9f9; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; font-size: 72px; margin: 0; }
        h2 { color: #666; font-size: 24px; margin: 10px 0 20px; }
        p { color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{$statusCode}</h1>
        <h2>{$statusText}</h2>
        <p>An error occurred while processing your request.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Register the exception handler.
     */
    public function register(): void
    {
        set_exception_handler([$this, 'handle']);
    }

    /**
     * Handle an uncaught exception.
     */
    public function handle(Throwable $exception): void
    {
        $this->report($exception);
        echo $this->render($exception);
    }

    /**
     * Create handler from container.
     */
    public static function create(Container $container): self
    {
        $debug = $container->has('app.debug')
            ? (bool) $container->get('app.debug')
            : false;

        return new self($container, $debug);
    }
}
