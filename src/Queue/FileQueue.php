<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

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
