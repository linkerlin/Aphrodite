<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

require_once __DIR__ . '/JobInterface.php';
require_once __DIR__ . '/Job.php';
require_once __DIR__ . '/QueuedJob.php';
require_once __DIR__ . '/QueueInterface.php';
require_once __DIR__ . '/SyncQueue.php';
require_once __DIR__ . '/ArrayQueue.php';
require_once __DIR__ . '/FileQueue.php';

/**
 * Queue manager.
 */
class Queue
{
    protected static ?QueueInterface $driver = null;
    protected static ?string $defaultQueue = 'default';

    /**
     * Get queue driver.
     */
    public static function getDriver(): QueueInterface
    {
        if (self::$driver === null) {
            self::$driver = new ArrayQueue();
        }

        return self::$driver;
    }

    /**
     * Set queue driver.
     */
    public static function setDriver(QueueInterface $driver): void
    {
        self::$driver = $driver;
    }

    /**
     * Set default queue.
     */
    public static function setDefaultQueue(string $queue): void
    {
        self::$defaultQueue = $queue;
    }

    /**
     * Push a job to the queue.
     */
    public static function push(string $job, array $data = [], ?string $queue = null): string
    {
        return self::getDriver()->push($job, $data, $queue ?? self::$defaultQueue);
    }

    /**
     * Push a job to run after a delay.
     */
    public static function later(int $delay, string $job, array $data = [], ?string $queue = null): string
    {
        return self::getDriver()->later($delay, $job, $data, $queue ?? self::$defaultQueue);
    }

    /**
     * Push a job to the sync queue (immediate execution).
     */
    public static function dispatchSync(string $job, array $data = []): void
    {
        (new SyncQueue())->push($job, $data);
    }

    /**
     * Pop a job from the queue.
     */
    public static function pop(?string $queue = null): ?QueuedJob
    {
        return self::getDriver()->pop($queue ?? self::$defaultQueue);
    }

    /**
     * Process a job.
     */
    public static function process(?string $queue = null): bool
    {
        $job = self::pop($queue);

        if ($job === null) {
            return false;
        }

        try {
            $instance = $job->resolve();
            $instance->handle();
            self::getDriver()->acknowledge($job);
            return true;
        } catch (\Throwable $e) {
            if ($job->attempts >= 3) {
                $instance = $job->resolve();
                $instance->failed($e);
                self::getDriver()->acknowledge($job);
            } else {
                self::getDriver()->release($job, 60);
            }
            return false;
        }
    }

    /**
     * Run the queue worker.
     */
    public static function work(?string $queue = null, int $maxJobs = 0): void
    {
        $processed = 0;

        while (true) {
            if ($maxJobs > 0 && $processed >= $maxJobs) {
                break;
            }

            if (!self::process($queue)) {
                usleep(100000); // 100ms
            }

            $processed++;
        }
    }
}
