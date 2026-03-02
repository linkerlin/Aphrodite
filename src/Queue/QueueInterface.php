<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

/**
 * Queue interface.
 */
interface QueueInterface
{
    public function push(string $job, array $data = [], ?string $queue = null): string;
    public function later(int $delay, string $job, array $data = [], ?string $queue = null): string;
    public function pop(?string $queue = null): ?QueuedJob;
    public function acknowledge(QueuedJob $job): bool;
    public function release(QueuedJob $job, int $delay = 0): bool;
}
