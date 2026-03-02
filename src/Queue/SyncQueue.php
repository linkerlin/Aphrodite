<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

/**
 * Sync queue (immediate execution).
 */
class SyncQueue implements QueueInterface
{
    public function push(string $job, array $data = [], ?string $queue = null): string
    {
        $jobInstance = new $job(...$data);
        $jobInstance->handle();
        return uniqid('sync_');
    }

    public function later(int $delay, string $job, array $data = [], ?string $queue = null): string
    {
        return $this->push($job, $data, $queue);
    }

    public function pop(?string $queue = null): ?QueuedJob
    {
        return null;
    }

    public function acknowledge(QueuedJob $job): bool
    {
        return true;
    }

    public function release(QueuedJob $job, int $delay = 0): bool
    {
        return true;
    }
}
