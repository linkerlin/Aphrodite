<?php

declare(strict_types=1);

namespace Tests\Config;

use Aphrodite\Config\ConfigSchema;
use Aphrodite\Config\ConfigValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for configuration validation system.
 */
class ConfigValidatorTest extends TestCase
{
    #[Test]
    public function schema_can_be_created(): void
    {
        $schema = ConfigSchema::make('app');

        $this->assertInstanceOf(ConfigSchema::class, $schema);
        $this->assertEquals('app', $schema->getName());
    }

    #[Test]
    public function schema_can_define_string_rule(): void
    {
        $schema = ConfigSchema::make()
            ->string('name', 'default', true);

        $rule = $schema->getRule('name');

        $this->assertNotNull($rule);
        $this->assertEquals('string', $rule['type']);
        $this->assertEquals('default', $rule['default']);
        $this->assertTrue($rule['required']);
    }

    #[Test]
    public function schema_can_define_integer_rule(): void
    {
        $schema = ConfigSchema::make()
            ->integer('port', 8080, false);

        $rule = $schema->getRule('port');

        $this->assertEquals('integer', $rule['type']);
        $this->assertEquals(8080, $rule['default']);
        $this->assertFalse($rule['required']);
    }

    #[Test]
    public function schema_can_define_boolean_rule(): void
    {
        $schema = ConfigSchema::make()
            ->boolean('debug', false, false);

        $rule = $schema->getRule('debug');

        $this->assertEquals('boolean', $rule['type']);
        $this->assertFalse($rule['default']);
    }

    #[Test]
    public function schema_can_define_array_rule(): void
    {
        $schema = ConfigSchema::make()
            ->array('hosts', [], false);

        $rule = $schema->getRule('hosts');

        $this->assertEquals('array', $rule['type']);
        $this->assertEquals([], $rule['default']);
    }

    #[Test]
    public function schema_can_define_enum_rule(): void
    {
        $schema = ConfigSchema::make()
            ->enum('env', ['local', 'production', 'testing'], 'local', false);

        $rule = $schema->getRule('env');

        $this->assertEquals('enum', $rule['type']);
        $this->assertEquals(['local', 'production', 'testing'], $rule['allowed']);
        $this->assertEquals('local', $rule['default']);
    }

    #[Test]
    public function schema_can_define_required_rule(): void
    {
        $schema = ConfigSchema::make()
            ->required('api_key', 'string');

        $rule = $schema->getRule('api_key');

        $this->assertTrue($rule['required']);
        $this->assertEquals('string', $rule['type']);
    }

    #[Test]
    public function schema_can_check_key_exists(): void
    {
        $schema = ConfigSchema::make()
            ->string('name');

        $this->assertTrue($schema->has('name'));
        $this->assertFalse($schema->has('nonexistent'));
    }

    #[Test]
    public function validator_validates_empty_config(): void
    {
        $schema = ConfigSchema::make()
            ->string('name', null, false);

        $validator = ConfigValidator::for($schema);

        $this->assertTrue($validator->validate([]));
    }

    #[Test]
    public function validator_fails_on_missing_required(): void
    {
        $schema = ConfigSchema::make()
            ->required('api_key', 'string');

        $validator = ConfigValidator::for($schema);
        $valid = $validator->validate([]);

        $this->assertFalse($valid);
        $this->assertArrayHasKey('api_key', $validator->getErrors());
    }

    #[Test]
    public function validator_passes_with_required_present(): void
    {
        $schema = ConfigSchema::make()
            ->required('api_key', 'string');

        $validator = ConfigValidator::for($schema);
        $valid = $validator->validate(['api_key' => 'test-key']);

        $this->assertTrue($valid);
        $this->assertEmpty($validator->getErrors());
    }

    #[Test]
    public function validator_validates_string_type(): void
    {
        $schema = ConfigSchema::make()
            ->string('name', null, true);

        $validator = ConfigValidator::for($schema);

        $this->assertTrue($validator->validate(['name' => 'John']));
        $this->assertFalse($validator->validate(['name' => 123]));
    }

    #[Test]
    public function validator_validates_integer_type(): void
    {
        $schema = ConfigSchema::make()
            ->integer('port', null, true);

        $validator = ConfigValidator::for($schema);

        $this->assertTrue($validator->validate(['port' => 8080]));
        $this->assertFalse($validator->validate(['port' => '8080']));
    }

    #[Test]
    public function validator_validates_boolean_type(): void
    {
        $schema = ConfigSchema::make()
            ->boolean('debug', null, true);

        $validator = ConfigValidator::for($schema);

        $this->assertTrue($validator->validate(['debug' => true]));
        $this->assertTrue($validator->validate(['debug' => false]));
        $this->assertFalse($validator->validate(['debug' => 'yes']));
    }

    #[Test]
    public function validator_validates_array_type(): void
    {
        $schema = ConfigSchema::make()
            ->array('items', null, true);

        $validator = ConfigValidator::for($schema);

        $this->assertTrue($validator->validate(['items' => [1, 2, 3]]));
        $this->assertTrue($validator->validate(['items' => []]));
        $this->assertFalse($validator->validate(['items' => 'not-array']));
    }

    #[Test]
    public function validator_validates_float_type(): void
    {
        $schema = ConfigSchema::make()
            ->float('price', null, true);

        $validator = ConfigValidator::for($schema);

        $this->assertTrue($validator->validate(['price' => 9.99]));
        $this->assertTrue($validator->validate(['price' => 10])); // int is acceptable for float
        $this->assertFalse($validator->validate(['price' => '9.99']));
    }

    #[Test]
    public function validator_validates_enum_type(): void
    {
        $schema = ConfigSchema::make()
            ->enum('env', ['local', 'production'], null, true);

        $validator = ConfigValidator::for($schema);

        $this->assertTrue($validator->validate(['env' => 'local']));
        $this->assertTrue($validator->validate(['env' => 'production']));
        $this->assertFalse($validator->validate(['env' => 'staging']));
    }

    #[Test]
    public function validator_strict_mode_catches_unknown_keys(): void
    {
        $schema = ConfigSchema::make()
            ->string('name');

        $validator = ConfigValidator::for($schema)->strict();

        $this->assertFalse($validator->validate(['name' => 'test', 'unknown' => 'key']));
        $this->assertArrayHasKey('unknown', $validator->getErrors());
    }

    #[Test]
    public function validator_non_strict_allows_unknown_keys(): void
    {
        $schema = ConfigSchema::make()
            ->string('name');

        $validator = ConfigValidator::for($schema);

        $this->assertTrue($validator->validate(['name' => 'test', 'unknown' => 'key']));
    }

    #[Test]
    public function validator_apply_defaults(): void
    {
        $schema = ConfigSchema::make()
            ->string('name', 'default-name')
            ->integer('port', 8080);

        $validator = ConfigValidator::for($schema);
        $config = $validator->applyDefaults([]);

        $this->assertEquals('default-name', $config['name']);
        $this->assertEquals(8080, $config['port']);
    }

    #[Test]
    public function validator_apply_defaults_preserves_existing(): void
    {
        $schema = ConfigSchema::make()
            ->string('name', 'default-name')
            ->integer('port', 8080);

        $validator = ConfigValidator::for($schema);
        $config = $validator->applyDefaults(['name' => 'custom']);

        $this->assertEquals('custom', $config['name']);
        $this->assertEquals(8080, $config['port']);
    }

    #[Test]
    public function validator_validate_and_apply_defaults(): void
    {
        $schema = ConfigSchema::make()
            ->string('name', 'default-name')
            ->required('api_key', 'string');

        $validator = ConfigValidator::for($schema);
        $config = $validator->validateAndApplyDefaults(['api_key' => 'key123']);

        $this->assertEquals('default-name', $config['name']);
        $this->assertEquals('key123', $config['api_key']);
    }

    #[Test]
    public function validator_validate_or_fail_throws(): void
    {
        $schema = ConfigSchema::make()
            ->required('api_key', 'string');

        $validator = ConfigValidator::for($schema);

        $this->expectException(InvalidArgumentException::class);
        $validator->validateOrFail([]);
    }

    #[Test]
    public function validator_validate_or_fail_passes(): void
    {
        $schema = ConfigSchema::make()
            ->required('api_key', 'string');

        $validator = ConfigValidator::for($schema);

        // Should not throw
        $validator->validateOrFail(['api_key' => 'test']);
        $this->assertTrue(true);
    }

    #[Test]
    public function validator_get_first_error(): void
    {
        $schema = ConfigSchema::make()
            ->required('key1', 'string')
            ->required('key2', 'string');

        $validator = ConfigValidator::for($schema);
        $validator->validate([]);

        $error = $validator->getFirstError();
        $this->assertNotNull($error);
        $this->assertStringContainsString('key1', $error);
    }

    #[Test]
    public function validator_has_errors(): void
    {
        $schema = ConfigSchema::make()
            ->required('api_key', 'string');

        $validator = ConfigValidator::for($schema);

        $this->assertFalse($validator->hasErrors());
        $validator->validate([]);
        $this->assertTrue($validator->hasErrors());
    }

    #[Test]
    public function validator_without_schema_passes(): void
    {
        $validator = ConfigValidator::make();

        $this->assertTrue($validator->validate(['any' => 'config']));
    }

    #[Test]
    public function complex_schema_validation(): void
    {
        $schema = ConfigSchema::make('database')
            ->required('host', 'string')
            ->integer('port', 3306)
            ->string('database', null, true)
            ->string('username', null, true)
            ->string('password', '')
            ->enum('driver', ['mysql', 'pgsql', 'sqlite'], 'mysql');

        $validator = ConfigValidator::for($schema);

        $validConfig = [
            'host' => 'localhost',
            'database' => 'mydb',
            'username' => 'root',
        ];

        $this->assertTrue($validator->validate($validConfig));

        $configWithDefaults = $validator->applyDefaults($validConfig);
        $this->assertEquals(3306, $configWithDefaults['port']);
        $this->assertEquals('mysql', $configWithDefaults['driver']);
        $this->assertEquals('', $configWithDefaults['password']);
    }

    #[Test]
    public function schema_get_rules(): void
    {
        $schema = ConfigSchema::make()
            ->string('name')
            ->integer('age');

        $rules = $schema->getRules();

        $this->assertCount(2, $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('age', $rules);
    }
}
