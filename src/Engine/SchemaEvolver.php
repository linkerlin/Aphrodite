<?php

declare(strict_types=1);

namespace Aphrodite\Engine;

/**
 * Handles intelligent database schema evolution by diffing current schema
 * against new intent and producing migration operations.
 */
class SchemaEvolver
{
    /**
     * Compare current schema to new intent and return migration operations.
     *
     * @return array<int, array{type: string, field: string, definition?: string}>
     */
    public function evolve(array $currentSchema, array $newIntent): array
    {
        $currentFields = $currentSchema['fields'] ?? [];
        $newFields     = $newIntent['fields']     ?? [];

        $operations = [];

        // Detect added fields
        foreach ($newFields as $field) {
            $fieldName = is_array($field) ? ($field['name'] ?? $field) : $field;
            if (!in_array($fieldName, $this->normalizeFields($currentFields), true)) {
                $operations[] = [
                    'type'       => 'add_column',
                    'field'      => $fieldName,
                    'definition' => is_array($field) ? ($field['type'] ?? 'VARCHAR(255)') : 'VARCHAR(255)',
                ];
            }
        }

        // Detect removed fields
        foreach ($currentFields as $field) {
            $fieldName = is_array($field) ? ($field['name'] ?? $field) : $field;
            if (!in_array($fieldName, $this->normalizeFields($newFields), true)) {
                $operations[] = [
                    'type'  => 'drop_column',
                    'field' => $fieldName,
                ];
            }
        }

        return $operations;
    }

    /**
     * Generate a PHP migration code string from an array of operations.
     */
    public function generateMigration(array $operations): string
    {
        $lines = [];
        foreach ($operations as $op) {
            $field = $op['field'];
            $lines[] = match ($op['type']) {
                'add_column'  => "\$table->addColumn('{$field}', '{$op['definition']}');",
                'drop_column' => "\$table->dropColumn('{$field}');",
                'modify_column' => "\$table->modifyColumn('{$field}', '{$op['definition']}');",
                default       => "// Unknown operation: {$op['type']} on {$field}",
            };
        }

        $body = implode("\n        ", $lines);

        return <<<PHP
<?php

declare(strict_types=1);

use Aphrodite\Database\Migration;

return new class extends Migration
{
    public function up(): void
    {
        {$body}
    }

    public function down(): void
    {
        // Reverse operations here
    }
};
PHP;
    }

    /**
     * Return descriptions of breaking changes (e.g., dropped columns).
     *
     * @return string[]
     */
    public function detectBreakingChanges(array $operations): array
    {
        $breaking = [];
        foreach ($operations as $op) {
            if (in_array($op['type'], ['drop_column', 'drop_table', 'rename_column'], true)) {
                $breaking[] = "Breaking change: {$op['type']} on field '{$op['field']}'";
            }
        }
        return $breaking;
    }

    /** @return string[] */
    private function normalizeFields(array $fields): array
    {
        return array_map(
            static fn($f) => is_array($f) ? ($f['name'] ?? (string) $f) : (string) $f,
            $fields
        );
    }
}
