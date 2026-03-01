<?php

declare(strict_types=1);

namespace Aphrodite\ORM;

/**
 * Query optimizer: detects N+1 patterns and suggests indexes.
 */
class QueryOptimizer
{
    /**
     * Optimize a query descriptor array by adding eager-loading hints.
     */
    public function optimize(array $query): array
    {
        if ($this->detectNPlusOne($query)) {
            $query['eager_load'] = $query['relations'] ?? [];
        }

        if ($where = ($query['where'] ?? [])) {
            $fields = array_keys($where);
            if ($suggestion = $this->suggestIndex($query)) {
                $query['suggested_index'] = $suggestion;
            }
        }

        return $query;
    }

    /**
     * Detect whether a query will trigger an N+1 problem.
     * A query has an N+1 risk when it declares relations but does NOT eager-load them.
     */
    public function detectNPlusOne(array $query): bool
    {
        return isset($query['relations']) && !isset($query['eager_load']);
    }

    /**
     * Suggest an index based on WHERE / ORDER BY clauses.
     */
    public function suggestIndex(array $query): ?string
    {
        $columns = [];

        if (!empty($query['where'])) {
            $columns = array_merge($columns, array_keys($query['where']));
        }

        if (!empty($query['order_by'])) {
            $orderCols = is_array($query['order_by'])
                ? array_keys($query['order_by'])
                : [$query['order_by']];
            $columns = array_merge($columns, $orderCols);
        }

        $columns = array_unique($columns);

        if (empty($columns)) {
            return null;
        }

        $table = $query['table'] ?? 'table';
        $cols  = implode(', ', $columns);

        return "CREATE INDEX idx_{$table}_" . implode('_', $columns) . " ON {$table} ({$cols})";
    }
}
