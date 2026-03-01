<?php

declare(strict_types=1);

namespace Aphrodite\Engine;

/**
 * Auto-generates PHPUnit test cases from structured intent or entity definitions.
 */
class TestSynthesizer
{
    /**
     * Generate a PHPUnit test class from an intent array.
     */
    public function synthesize(array $intent): string
    {
        $entityName = $intent['entity'] ?? 'Unknown';
        $testClass  = $entityName . 'Test';
        $fields     = $intent['fields'] ?? [];
        $operations = $intent['operations'] ?? [];

        $testMethods = $this->buildEntityTestMethods($entityName, $fields);
        $testMethods .= $this->buildOperationTestMethods($entityName, $operations);

        return $this->wrapInClass($testClass, "App\\Entity\\{$entityName}", $testMethods);
    }

    /**
     * Generate a test class focused on entity property access/mutation.
     */
    public function synthesizeFromEntity(array $entityDefinition): string
    {
        $entityName = $entityDefinition['name'] ?? 'Unknown';
        $testClass  = $entityName . 'EntityTest';
        $fields     = $entityDefinition['fields'] ?? [];

        $testMethods = $this->buildEntityTestMethods($entityName, $fields);

        return $this->wrapInClass($testClass, "App\\Entity\\{$entityName}", $testMethods);
    }

    private function buildEntityTestMethods(string $entityName, array $fields): string
    {
        $methods = '';

        $methods .= <<<PHP

    public function testCanInstantiate{$entityName}(): void
    {
        \$entity = new {$entityName}();
        \$this->assertInstanceOf({$entityName}::class, \$entity);
    }
PHP;

        foreach ($fields as $field) {
            $fieldName = is_array($field) ? ($field['name'] ?? 'field') : (string) $field;
            $getter    = 'get' . ucfirst($fieldName);
            $setter    = 'set' . ucfirst($fieldName);
            $type      = is_array($field) ? ($field['type'] ?? 'string') : 'string';
            $sample    = $this->sampleValue($type);

            $methods .= <<<PHP

    public function test{$entityName}{$fieldName}GetterSetter(): void
    {
        \$entity = new {$entityName}();
        \$entity->{$setter}({$sample});
        \$this->assertSame({$sample}, \$entity->{$getter}());
    }
PHP;
        }

        return $methods;
    }

    private function buildOperationTestMethods(string $entityName, array $operations): string
    {
        $methods = '';

        foreach ($operations as $operation) {
            $methodName = 'test' . ucfirst($operation) . $entityName;
            $methods .= <<<PHP

    public function {$methodName}(): void
    {
        // TODO: Implement {$operation} test for {$entityName}
        \$this->markTestIncomplete('Auto-generated test for {$operation} needs implementation.');
    }
PHP;
        }

        return $methods;
    }

    private function wrapInClass(string $testClass, string $useClass, string $methods): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use {$useClass};

class {$testClass} extends TestCase
{{$methods}
}
PHP;
    }

    private function sampleValue(string $type): string
    {
        return match ($type) {
            'int', 'integer' => '42',
            'float', 'double' => '3.14',
            'bool', 'boolean' => 'true',
            default => "'sample_value'",
        };
    }
}
