<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Relations;

use Aphrodite\ORM\Entity;

/**
 * Belongs-to-many relationship (many-to-many with pivot table).
 * Example: User belongs to many Roles (user->roles)
 */
class BelongsToMany extends Relation
{
    protected string $pivotTable = '';
    protected string $pivotForeignKey = '';
    protected string $pivotRelatedKey = '';
    protected static array $pivotStore = [];

    public function __construct(
        Entity $parent,
        string $related,
        string $pivotTable = '',
        string $pivotForeignKey = '',
        string $pivotRelatedKey = '',
        string $foreignKey = 'id',
        string $localKey = 'id'
    ) {
        parent::__construct($parent, $related, $foreignKey, $localKey);

        $this->pivotTable = $pivotTable ?: $this->guessPivotTable();
        $this->pivotForeignKey = $pivotForeignKey ?: $this->guessPivotForeignKey();
        $this->pivotRelatedKey = $pivotRelatedKey ?: $this->guessPivotRelatedKey();
    }

    /**
     * Guess the pivot table name.
     */
    protected function guessPivotTable(): string
    {
        $parentTable = $this->parent::getTable();
        $relatedTable = $this->related::getTable();

        $tables = [$parentTable, $relatedTable];
        sort($tables);

        return implode('_', $tables);
    }

    /**
     * Guess the pivot foreign key for parent.
     */
    protected function guessPivotForeignKey(): string
    {
        $parentClass = basename(str_replace('\\', '/', $this->parent::class));
        return strtolower($parentClass) . '_id';
    }

    /**
     * Guess the pivot foreign key for related.
     */
    protected function guessPivotRelatedKey(): string
    {
        $relatedClass = basename(str_replace('\\', '/', $this->related));
        return strtolower($relatedClass) . '_id';
    }

    /**
     * Get the pivot table name.
     */
    public function getPivotTable(): string
    {
        return $this->pivotTable;
    }

    /**
     * Get all related entities.
     *
     * @return Entity[]
     */
    public function get(): array
    {
        if ($this->loaded) {
            return $this->result;
        }

        $localKeyValue = $this->parent->{$this->localKey};

        if ($this->related::isUsingMemoryStore()) {
            $relatedIds = $this->getPivotIdsFromMemory($localKeyValue);

            if (empty($relatedIds)) {
                $this->result = [];
                $this->loaded = true;
                return [];
            }

            $all = $this->related::all();
            $this->result = array_filter($all, fn($entity) => in_array($entity->id, $relatedIds));
            $this->result = array_values($this->result);
            $this->loaded = true;
            return $this->result;
        }

        // Get pivot IDs
        $pdo = $this->related::getPdo();
        $pivotSql = "SELECT {$this->pivotRelatedKey} FROM {$this->pivotTable} WHERE {$this->pivotForeignKey} = ?";
        $stmt = $pdo->prepare($pivotSql);
        $stmt->execute([$localKeyValue]);
        $relatedIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($relatedIds)) {
            $this->result = [];
            $this->loaded = true;
            return [];
        }

        // Get related entities using whereIn
        $rows = $this->related::query()
            ->whereIn($this->foreignKey, $relatedIds)
            ->get();

        $this->result = array_map(
            fn($row) => $this->related::fromArray($row),
            $rows
        );

        $this->loaded = true;
        return $this->result;
    }

    /**
     * Get pivot IDs from memory store.
     */
    protected function getPivotIdsFromMemory(int|string $localKeyValue): array
    {
        if (!isset(self::$pivotStore[$this->pivotTable])) {
            return [];
        }

        $ids = [];
        foreach (self::$pivotStore[$this->pivotTable] as $pivot) {
            if ($pivot[$this->pivotForeignKey] == $localKeyValue) {
                $ids[] = $pivot[$this->pivotRelatedKey];
            }
        }

        return $ids;
    }

    /**
     * Attach an entity to this relation.
     */
    public function attach(Entity|int $entity, array $pivotData = []): bool
    {
        $relatedId = $entity instanceof Entity ? $entity->id : $entity;
        $localKeyValue = $this->parent->{$this->localKey};

        if ($this->related::isUsingMemoryStore()) {
            if (!isset(self::$pivotStore[$this->pivotTable])) {
                self::$pivotStore[$this->pivotTable] = [];
            }

            // Check if already attached
            foreach (self::$pivotStore[$this->pivotTable] as $pivot) {
                if ($pivot[$this->pivotForeignKey] == $localKeyValue && $pivot[$this->pivotRelatedKey] == $relatedId) {
                    return true;
                }
            }

            $pivotRecord = array_merge([
                $this->pivotForeignKey => $localKeyValue,
                $this->pivotRelatedKey => $relatedId,
            ], $pivotData);

            self::$pivotStore[$this->pivotTable][] = $pivotRecord;
            return true;
        }

        $pdo = $this->related::getPdo();

        // Check if already attached
        $checkSql = "SELECT COUNT(*) FROM {$this->pivotTable} WHERE {$this->pivotForeignKey} = ? AND {$this->pivotRelatedKey} = ?";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([$localKeyValue, $relatedId]);

        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // Insert pivot record
        $pivotColumns = [$this->pivotForeignKey, $this->pivotRelatedKey];
        $pivotValues = [$localKeyValue, $relatedId];

        if (!empty($pivotData)) {
            foreach ($pivotData as $column => $value) {
                $pivotColumns[] = $column;
                $pivotValues[] = $value;
            }
        }

        $placeholders = implode(',', array_fill(0, count($pivotColumns), '?'));
        $columns = implode(',', $pivotColumns);
        $insertSql = "INSERT INTO {$this->pivotTable} ({$columns}) VALUES ({$placeholders})";

        $stmt = $pdo->prepare($insertSql);
        return $stmt->execute($pivotValues);
    }

    /**
     * Detach an entity from this relation.
     */
    public function detach(Entity|int|null $entity = null): bool
    {
        $localKeyValue = $this->parent->{$this->localKey};

        if ($this->related::isUsingMemoryStore()) {
            if (!isset(self::$pivotStore[$this->pivotTable])) {
                return true;
            }

            if ($entity === null) {
                // Detach all
                self::$pivotStore[$this->pivotTable] = array_filter(
                    self::$pivotStore[$this->pivotTable],
                    fn($pivot) => $pivot[$this->pivotForeignKey] != $localKeyValue
                );
                return true;
            }

            $relatedId = $entity instanceof Entity ? $entity->id : $entity;
            self::$pivotStore[$this->pivotTable] = array_filter(
                self::$pivotStore[$this->pivotTable],
                fn($pivot) => !($pivot[$this->pivotForeignKey] == $localKeyValue && $pivot[$this->pivotRelatedKey] == $relatedId)
            );
            return true;
        }

        $pdo = $this->related::getPdo();

        if ($entity === null) {
            // Detach all
            $sql = "DELETE FROM {$this->pivotTable} WHERE {$this->pivotForeignKey} = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$localKeyValue]);
        }

        $relatedId = $entity instanceof Entity ? $entity->id : $entity;
        $sql = "DELETE FROM {$this->pivotTable} WHERE {$this->pivotForeignKey} = ? AND {$this->pivotRelatedKey} = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$localKeyValue, $relatedId]);
    }

    /**
     * Sync related entities (detach all, then attach given).
     */
    public function sync(array $ids): void
    {
        $this->detach();

        foreach ($ids as $id) {
            $this->attach($id);
        }

        $this->loaded = false;
    }

    /**
     * Toggle related entities.
     */
    public function toggle(Entity|int $entity): bool
    {
        $relatedId = $entity instanceof Entity ? $entity->id : $entity;
        $localKeyValue = $this->parent->{$this->localKey};

        if ($this->related::isUsingMemoryStore()) {
            $ids = $this->getPivotIdsFromMemory($localKeyValue);
            $this->loaded = false; // Reset cache before checking
            if (in_array($relatedId, $ids)) {
                $result = $this->detach($entity);
                $this->loaded = false; // Reset cache after detach
                return $result;
            }
            $result = $this->attach($entity);
            $this->loaded = false; // Reset cache after attach
            return $result;
        }

        $pdo = $this->related::getPdo();
        $checkSql = "SELECT COUNT(*) FROM {$this->pivotTable} WHERE {$this->pivotForeignKey} = ? AND {$this->pivotRelatedKey} = ?";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([$localKeyValue, $relatedId]);

        if ($stmt->fetchColumn() > 0) {
            $result = $this->detach($entity);
            $this->loaded = false;
            return $result;
        }

        $result = $this->attach($entity);
        $this->loaded = false;
        return $result;
    }

    /**
     * Count related entities.
     */
    public function count(): int
    {
        return count($this->get());
    }

    /**
     * Reset pivot store (for testing).
     */
    public static function resetPivotStore(): void
    {
        self::$pivotStore = [];
    }
}
