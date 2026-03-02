<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

/**
 * Array-based queue (in-memory).
 */
class ArrayQueue implements QueueInterface
{
    protected array $jobs = [];
    protected array $reserved = [];
    protected int $maxAttempts = 3;

    public function push(string $job, array $data = [], ?string $queue = null): string
    {
        $queue = $queue ?? 'default';
        $queuedJob = new QueuedJob($job, $data, null, 0, null, null, $queue);

        $this->jobs[$queue][] = $queuedJob;

        return $queuedJob->id;
    }

    public function later(int $delay, string $job, array $data = [], ?string $queue = null): string
    {
        $queue = $queue ?? 'default';
        $availableAt = time() + $delay;
        $queuedJob = new QueuedJob($job, $data, null, 0, null, $availableAt, $queue);

        $this->jobs[$queue][] = $queuedJob;

        return $queuedJob->id;
    }

    public function pop(?string $queue = null): ?QueuedJob
    {
        $queue = $queue ?? 'default';

        if (empty($this->jobs[$queue])) {
            return null;
        }

        $now = time();

        foreach ($this->jobs[$queue] as $index => $job) {
            if ($job->availableAt !== null && $job->availableAt > $now) {
                continue;
            }

            if ($job->attempts >= $this->maxAttempts) {
                continue;
            }

            unset($this->jobs[$queue][$index]);
            $this->jobs[$queue] = array_values($this->jobs[$queue]);

            $job->attempts++;
            $job->reservedAt = $now;

            $this->reserved[$queue][$job->id] = $job;

            return $job;
        }

        return null;
    }

    public function acknowledge(QueuedJob $job): bool
    {
        $queue = $job->getQueue();

        if (isset($this->reserved[$queue][$job->id])) {
            unset($this->reserved[$queue][$job->id]);
            return true;
        }

        return false;
    }

    public function release(QueuedJob $job, int $delay = 0): bool
    {
        $queue = $job->getQueue();

        $this->acknowledge($job);

        $job->availableAt = $delay > 0 ? time() + $delay : time();
        $job->reservedAt = null;

        $this->jobs[$queue][] = $job;

        return true;
    }

    public function setMaxAttempts(int $attempts): void
    {
        $this->maxAttempts = $attempts;
    }
}
