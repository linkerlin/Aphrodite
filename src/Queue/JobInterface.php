<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

/**
 * Queue job interface.
 */
interface JobInterface
{
    public function handle(): void;
    public function failed(\Throwable $exception): void;
    public function getJobId(): ?string;
    public function getQueue(): string;
    public function getAttempts(): int;
}
