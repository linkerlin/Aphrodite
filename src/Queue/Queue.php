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

/**
 * Queued job wrapper.
 */
class QueuedJob
{
    public string $id;
    public string $class;
    public array $data;
    public int $attempts;
    public ?int $reservedAt;
    public ?int $availableAt;
    public ?int $createdAt;
    public ?string $queue;

    public function __construct(
        string $class,
        array $data = [],
        ?string $id = null,
        int $attempts = 0,
        ?int $reservedAt = null,
        ?int $availableAt = null,
        ?string $queue = null
    ) {
        $this->id = $id ?? uniqid('job_');
        $this->class = $class;
        $this->data = $data;
        $this->attempts = $attempts;
        $this->reservedAt = $reservedAt ?? time();
        $this->availableAt = $availableAt;
        $this->createdAt = time();
        $this->queue = $queue;
    }

    public function getQueue(): ?string
    {
        return $this->queue;
    }

    public function resolve(): JobInterface
    {
        $job = new $this->class(...$this->data);
        $job->setJobId($this->id);
        $job->setAttempts($this->attempts);
        return $job;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'class' => $this->class,
            'data' => $this->data,
            'attempts' => $this->attempts,
            'reserved_at' => $this->reservedAt,
            'available_at' => $this->availableAt,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['class'],
            $data['data'] ?? [],
            $data['id'] ?? null,
            $data['attempts'] ?? 0,
            $data['reserved_at'] ?? null,
            $data['available_at'] ?? null
        );
    }
}

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

/**
 * File-based queue (persistent).
 */
class FileQueue implements QueueInterface
{
    protected string $path;
    protected string $reservedPath;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/storage/queue';
        $this->reservedPath = $this->path . '/reserved';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }

        if (!is_dir($this->reservedPath)) {
            mkdir($this->reservedPath, 0755, true);
        }
    }

    public function push(string $job, array $data = [], ?string $queue = null): string
    {
        $queue = $queue ?? 'default';
        $queuedJob = new QueuedJob($job, $data, null, 0, null, null, $queue);

        $file = $this->path . '/' . $queue . '_' . $queuedJob->id . '.json';
        file_put_contents($file, json_encode($queuedJob->toArray()));

        return $queuedJob->id;
    }

    public function later(int $delay, string $job, array $data = [], ?string $queue = null): string
    {
        $queue = $queue ?? 'default';
        $availableAt = time() + $delay;
        $queuedJob = new QueuedJob($job, $data, null, 0, null, $availableAt, $queue);

        $file = $this->path . '/' . $queue . '_' . $queuedJob->id . '.json';
        file_put_contents($file, json_encode($queuedJob->toArray()));

        return $queuedJob->id;
    }

    public function pop(?string $queue = null): ?QueuedJob
    {
        $queue = $queue ?? 'default';

        $files = glob($this->path . '/' . $queue . '_*.json');

        if (empty($files)) {
            return null;
        }

        $now = time();

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if (!$data) {
                continue;
            }

            $job = QueuedJob::fromArray($data);

            if ($job->availableAt !== null && $job->availableAt > $now) {
                continue;
            }

            if ($job->attempts >= 3) {
                @unlink($file);
                continue;
            }

            // Move to reserved
            $reservedFile = $this->reservedPath . '/' . $job->id . '.json';
            $job->attempts++;
            $job->reservedAt = $now;
            file_put_contents($reservedFile, json_encode($job->toArray()));
            @unlink($file);

            return $job;
        }

        return null;
    }

    public function acknowledge(QueuedJob $job): bool
    {
        $file = $this->reservedPath . '/' . $job->id . '.json';

        if (file_exists($file)) {
            @unlink($file);
            return true;
        }

        return false;
    }

    public function release(QueuedJob $job, int $delay = 0): bool
    {
        $this->acknowledge($job);

        $queue = $job->getQueue();
        $job->availableAt = $delay > 0 ? time() + $delay : time();
        $job->reservedAt = null;

        $file = $this->path . '/' . $queue . '_' . $job->id . '.json';
        return file_put_contents($file, json_encode($job->toArray())) !== false;
    }
}

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
