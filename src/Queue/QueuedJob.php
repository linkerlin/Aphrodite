<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

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
