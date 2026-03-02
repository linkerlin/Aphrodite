<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

/**
 * Base job class.
 */
abstract class Job implements JobInterface
{
    protected int $attempts = 0;
    protected ?string $jobId = null;
    protected string $queue = 'default';
    protected int $timeout = 60;

    public function handle(): void
    {
        // Override in subclass
    }

    public function failed(\Throwable $exception): void
    {
        // Override in subclass
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function setJobId(string $id): void
    {
        $this->jobId = $id;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function setQueue(string $queue): void
    {
        $this->queue = $queue;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }
}
