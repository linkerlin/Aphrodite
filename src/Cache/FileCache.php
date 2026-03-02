<?php

declare(strict_types=1);

namespace Aphrodite\Cache;

/**
 * File-based cache implementation.
 */
class FileCache implements CacheInterface
{
    protected string $path;
    protected array $locks = [];

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/storage/cache';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $file = $this->getFilePath($key);

        $expires = $ttl > 0 ? time() + $ttl : 0;

        $data = [
            'value' => $value,
            'expires' => $expires,
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($file);
            return false;
        }

        return true;
    }

    public function forget(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    public function flush(): bool
    {
        $files = glob($this->path . '/*.cache');

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    protected function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->path . '/' . $hash . '.cache';
    }
}
