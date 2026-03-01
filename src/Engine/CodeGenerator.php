<?php

declare(strict_types=1);

namespace Aphrodite\Engine;

/**
 * Generates PHP code (entity classes, controllers, migrations) from structured intent.
 */
class CodeGenerator
{
    /**
     * Generate a PHP entity class from intent.
     */
    public function generateEntity(array $intent): string
    {
        $entityName = $intent['entity'] ?? 'Entity';
        $fields     = $intent['fields'] ?? [];

        $properties = '';
        foreach ($fields as $field) {
            $name = $field['name'] ?? 'field';
            $type = $field['type'] ?? 'mixed';
            $properties .= "    private {$type} \${$name};\n";
        }

        $getters = '';
        foreach ($fields as $field) {
            $name   = $field['name'] ?? 'field';
            $type   = $field['type'] ?? 'mixed';
            $method = 'get' . ucfirst($name);
            $setter = 'set' . ucfirst($name);
            $getters .= <<<PHP

    public function {$method}(): {$type}
    {
        return \$this->{$name};
    }

    public function {$setter}({$type} \${$name}): void
    {
        \$this->{$name} = \${$name};
    }
PHP;
        }

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Entity;

class {$entityName}
{
    private int \$id;
{$properties}
    public function getId(): int
    {
        return \$this->id;
    }
{$getters}
}
PHP;
    }

    /**
     * Generate a controller with CRUD methods from intent.
     */
    public function generateController(array $intent): string
    {
        $entityName     = $intent['entity'] ?? 'Entity';
        $controllerName = $entityName . 'Controller';
        $varName        = lcfirst($entityName);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\\{$entityName};

class {$controllerName}
{
    public function index(): array
    {
        return {$entityName}::all();
    }

    public function show(int \$id): ?{$entityName}
    {
        return {$entityName}::find(\$id);
    }

    public function store(array \$data): {$entityName}
    {
        \${$varName} = new {$entityName}();
        foreach (\$data as \$key => \$value) {
            \${$varName}->{\$key} = \$value;
        }
        \${$varName}->save();
        return \${$varName};
    }

    public function update(int \$id, array \$data): ?{$entityName}
    {
        \${$varName} = {$entityName}::find(\$id);
        if (\${$varName} === null) {
            return null;
        }
        foreach (\$data as \$key => \$value) {
            \${$varName}->{\$key} = \$value;
        }
        \${$varName}->save();
        return \${$varName};
    }

    public function destroy(int \$id): bool
    {
        \${$varName} = {$entityName}::find(\$id);
        return \${$varName} !== null;
    }
}
PHP;
    }

    /**
     * Generate a DB migration SQL from intent.
     */
    public function generateMigration(array $intent): string
    {
        $entityName = $intent['entity'] ?? 'Entity';
        $tableName  = strtolower($entityName) . 's';
        $fields     = $intent['fields'] ?? [];

        $columns = "    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n";
        foreach ($fields as $field) {
            $name    = $field['name'] ?? 'field';
            $type    = $this->mapPhpTypeToSql($field['type'] ?? 'string');
            $columns .= "    {$name} {$type} NOT NULL,\n";
        }
        $columns .= "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        $columns .= "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

        return <<<SQL
CREATE TABLE IF NOT EXISTS `{$tableName}` (
{$columns}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }

    private function mapPhpTypeToSql(string $phpType): string
    {
        return match ($phpType) {
            'int', 'integer' => 'INT',
            'float', 'double' => 'DOUBLE',
            'bool', 'boolean' => 'TINYINT(1)',
            'string' => 'VARCHAR(255)',
            'text'   => 'TEXT',
            default  => 'VARCHAR(255)',
        };
    }
}
