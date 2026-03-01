<?php

declare(strict_types=1);

namespace Aphrodite\Monitor;

/**
 * Lightweight performance monitor for tracking queries and operations.
 */
class AphroditeMonitor
{
    /** @var array<int, array{operation: string, duration: float, timestamp: float}> */
    private array $records = [];

    /**
     * Track an operation with its duration (in seconds).
     */
    public function track(string $queryOrOperation, float $duration): void
    {
        $this->records[] = [
            'operation' => $queryOrOperation,
            'duration'  => $duration,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Return all operations whose duration exceeds the given threshold.
     *
     * @return array<int, array{operation: string, duration: float, timestamp: float}>
     */
    public function detectSlowQueries(float $threshold = 1.0): array
    {
        return array_values(
            array_filter($this->records, static fn($r) => $r['duration'] >= $threshold)
        );
    }

    /**
     * Detect statistical anomalies using a simple Z-score approach (> 2 std deviations).
     *
     * @return array<int, array{operation: string, duration: float, timestamp: float}>
     */
    public function detectAnomalies(): array
    {
        if (count($this->records) < 2) {
            return [];
        }

        $durations = array_column($this->records, 'duration');
        $mean      = array_sum($durations) / count($durations);

        $variance = array_sum(
            array_map(static fn($d) => ($d - $mean) ** 2, $durations)
        ) / count($durations);

        $stdDev = sqrt($variance);

        if ($stdDev < 1e-10) {
            return [];
        }

        $anomalies = [];
        foreach ($this->records as $record) {
            $z = abs($record['duration'] - $mean) / $stdDev;
            if ($z > 2.0) {
                $anomalies[] = $record;
            }
        }

        return $anomalies;
    }

    /**
     * Return a summary of all tracked operations.
     *
     * @return array{total: int, avg_duration: float, max_duration: float, min_duration: float}
     */
    public function getSummary(): array
    {
        $total = count($this->records);

        if ($total === 0) {
            return ['total' => 0, 'avg_duration' => 0.0, 'max_duration' => 0.0, 'min_duration' => 0.0];
        }

        $durations = array_column($this->records, 'duration');

        return [
            'total'        => $total,
            'avg_duration' => array_sum($durations) / $total,
            'max_duration' => max($durations),
            'min_duration' => min($durations),
        ];
    }
}
