<?php

declare(strict_types=1);

namespace Tests\Engine\Code;

use Aphrodite\Engine\Code\ClassDefinition;
use Aphrodite\Engine\Code\MethodDefinition;
use Aphrodite\Engine\Code\PropertyDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for code generation classes.
 */
class CodeDefinitionTest extends TestCase
{
    #[Test]
    public function class_definition_can_be_created(): void
    {
        $class = ClassDefinition::create('User');

        $this->assertEquals('User', $class->getName());
        $this->assertEquals('', $class->getNamespace());
    }

    #[Test]
    public function class_definition_can_set_namespace(): void
    {
        $class = ClassDefinition::create('User')
            ->setNamespace('App\\Entity');

        $this->assertEquals('App\\Entity', $class->getNamespace());
    }

    #[Test]
    public function class_definition_can_be_final(): void
    {
        $class = ClassDefinition::create('User')
            ->final();

        $code = $class->generate();
        $this->assertStringContainsString('final class User', $code);
    }

    #[Test]
    public function class_definition_can_extend(): void
    {
        $class = ClassDefinition::create('User')
            ->extends('BaseEntity');

        $code = $class->generate();
        $this->assertStringContainsString('class User extends BaseEntity', $code);
    }

    #[Test]
    public function class_definition_can_implement(): void
    {
        $class = ClassDefinition::create('User')
            ->implements('UserInterface')
            ->implements('JsonSerializable');

        $code = $class->generate();
        $this->assertStringContainsString('implements UserInterface, JsonSerializable', $code);
    }

    #[Test]
    public function class_definition_can_add_use_statements(): void
    {
        $class = ClassDefinition::create('User')
            ->use('App\\Contracts\\UserInterface')
            ->use('App\\Traits\\Timestampable', 'HasTimestamps');

        $code = $class->generate();
        $this->assertStringContainsString('use App\\Contracts\\UserInterface;', $code);
        $this->assertStringContainsString('use App\\Traits\\Timestampable as HasTimestamps;', $code);
    }

    #[Test]
    public function class_definition_can_add_property(): void
    {
        $property = PropertyDefinition::create('name', 'string');
        $class = ClassDefinition::create('User')
            ->property($property);

        $code = $class->generate();
        $this->assertStringContainsString('private string $name', $code);
    }

    #[Test]
    public function class_definition_can_add_method(): void
    {
        $method = MethodDefinition::create('getName', 'string')
            ->body('return $this->name;');

        $class = ClassDefinition::create('User')
            ->method($method);

        $code = $class->generate();
        $this->assertStringContainsString('public function getName(): string', $code);
        $this->assertStringContainsString('return $this->name;', $code);
    }

    #[Test]
    public function class_definition_can_add_attribute(): void
    {
        $class = ClassDefinition::create('User')
            ->attribute('Entity', ['table' => 'users']);

        $code = $class->generate();
        $this->assertStringContainsString("#[Entity(table: 'users')]", $code);
    }

    #[Test]
    public function class_definition_generates_valid_php(): void
    {
        $code = ClassDefinition::create('User', 'App\\Entity')->generate();

        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('declare(strict_types=1);', $code);
        $this->assertStringContainsString('namespace App\\Entity;', $code);
        $this->assertStringContainsString('class User', $code);
    }

    #[Test]
    public function property_definition_can_be_created(): void
    {
        $property = PropertyDefinition::create('name', 'string');

        $this->assertEquals('name', $property->getName());
    }

    #[Test]
    public function property_definition_can_be_public(): void
    {
        $property = PropertyDefinition::create('name', 'string')
            ->public();

        $code = $property->generate();
        $this->assertStringContainsString('public string $name', $code);
    }

    #[Test]
    public function property_definition_can_be_protected(): void
    {
        $property = PropertyDefinition::create('name', 'string')
            ->protected();

        $code = $property->generate();
        $this->assertStringContainsString('protected string $name', $code);
    }

    #[Test]
    public function property_definition_can_have_default(): void
    {
        $property = PropertyDefinition::create('name', 'string')
            ->default('John');

        $code = $property->generate();
        $this->assertStringContainsString("private string \$name = 'John'", $code);
    }

    #[Test]
    public function property_definition_can_be_static(): void
    {
        $property = PropertyDefinition::create('instances', 'int')
            ->static()
            ->default(0);

        $code = $property->generate();
        $this->assertStringContainsString('private static int $instances = 0', $code);
    }

    #[Test]
    public function property_definition_can_be_readonly(): void
    {
        $property = PropertyDefinition::create('id', 'int')
            ->public()
            ->readonly();

        $code = $property->generate();
        $this->assertStringContainsString('public readonly int $id', $code);
    }

    #[Test]
    public function property_definition_can_have_attribute(): void
    {
        $property = PropertyDefinition::create('id', 'int')
            ->attribute('Column', ['name' => 'id', 'type' => 'integer']);

        $code = $property->generate();
        $this->assertStringContainsString("#[Column(name: 'id', type: 'integer')]", $code);
    }

    #[Test]
    public function property_definition_can_generate_getter(): void
    {
        $property = PropertyDefinition::create('name', 'string')
            ->withGetter();

        $getter = $property->generateGetter();

        $this->assertNotNull($getter);
        $this->assertEquals('getName', $getter->getName());
    }

    #[Test]
    public function property_definition_can_generate_setter(): void
    {
        $property = PropertyDefinition::create('name', 'string')
            ->withSetter();

        $setter = $property->generateSetter();

        $this->assertNotNull($setter);
        $this->assertEquals('setName', $setter->getName());
    }

    #[Test]
    public function method_definition_can_be_created(): void
    {
        $method = MethodDefinition::create('getName');

        $this->assertEquals('getName', $method->getName());
    }

    #[Test]
    public function method_definition_can_be_private(): void
    {
        $method = MethodDefinition::create('validate')
            ->private();

        $code = $method->generate();
        $this->assertStringContainsString('private function validate()', $code);
    }

    #[Test]
    public function method_definition_can_be_static(): void
    {
        $method = MethodDefinition::create('create')
            ->static();

        $code = $method->generate();
        $this->assertStringContainsString('public static function create()', $code);
    }

    #[Test]
    public function method_definition_can_have_parameters(): void
    {
        $method = MethodDefinition::create('setName', 'void')
            ->param('name', 'string')
            ->param('uppercase', 'bool', false);

        $code = $method->generate();
        $this->assertStringContainsString('string $name', $code);
        $this->assertStringContainsString('bool $uppercase = false', $code);
    }

    #[Test]
    public function method_definition_can_have_body(): void
    {
        $method = MethodDefinition::create('greet', 'string')
            ->body('return "Hello, " . $this->name;');

        $code = $method->generate();
        $this->assertStringContainsString('return "Hello, " . $this->name;', $code);
    }

    #[Test]
    public function method_definition_can_have_return_type(): void
    {
        $method = MethodDefinition::create('getName')
            ->returns('string');

        $code = $method->generate();
        $this->assertStringContainsString('): string', $code);
    }

    #[Test]
    public function method_definition_can_be_abstract(): void
    {
        $method = MethodDefinition::create('process')
            ->abstract();

        $code = $method->generate();
        $this->assertStringContainsString('abstract public function process()', $code);
        $this->assertStringNotContainsString('{', $code);
    }

    #[Test]
    public function method_definition_can_be_final(): void
    {
        $method = MethodDefinition::create('getId')
            ->final();

        $code = $method->generate();
        $this->assertStringContainsString('final public function getId()', $code);
    }

    #[Test]
    public function method_definition_can_have_attribute(): void
    {
        $method = MethodDefinition::create('index')
            ->attribute('Route', ['path' => '/users', 'methods' => ['GET']]);

        $code = $method->generate();
        $this->assertStringContainsString("#[Route(path: '/users', methods: ['GET'])]", $code);
    }

    #[Test]
    public function complete_entity_generation(): void
    {
        $class = ClassDefinition::create('User', 'App\\Entity')
            ->final()
            ->use('Aphrodite\\ORM\\Entity')
            ->extends('Entity')
            ->property(
                PropertyDefinition::create('id', 'int')
                    ->private()
            )
            ->property(
                PropertyDefinition::create('name', 'string')
                    ->private()
                    ->withGetter()
                    ->withSetter()
            )
            ->property(
                PropertyDefinition::create('email', 'string')
                    ->private()
                    ->withGetter()
                    ->withSetter()
            );

        $code = $class->generate();

        $this->assertStringContainsString('final class User extends Entity', $code);
        $this->assertStringContainsString('private int $id', $code);
        $this->assertStringContainsString('private string $name', $code);
        $this->assertStringContainsString('private string $email', $code);
    }

    #[Test]
    public function complex_class_generation(): void
    {
        $class = ClassDefinition::create('UserController', 'App\\Controller')
            ->use('Aphrodite\\Http\\Request')
            ->use('Aphrodite\\Http\\Response')
            ->use('App\\Entity\\User')
            ->attribute('Controller')
            ->method(
                MethodDefinition::create('index', 'array')
                    ->body('return User::all();')
            )
            ->method(
                MethodDefinition::create('show', '?User')
                    ->param('id', 'int')
                    ->body('return User::find($id);')
            )
            ->method(
                MethodDefinition::create('store', 'User')
                    ->param('request', 'Request')
                    ->body('$user = new User();' . "\n" . '$user->fill($request->all());' . "\n" . '$user->save();' . "\n" . 'return $user;')
            );

        $code = $class->generate();

        $this->assertStringContainsString('class UserController', $code);
        $this->assertStringContainsString('#[Controller]', $code);
        $this->assertStringContainsString('public function index(): array', $code);
        $this->assertStringContainsString('public function show(int $id): ?User', $code);
        $this->assertStringContainsString('public function store(Request $request): User', $code);
    }

    #[Test]
    public function property_formats_bool_value(): void
    {
        $property = PropertyDefinition::create('active', 'bool')
            ->default(true);

        $code = $property->generate();
        $this->assertStringContainsString('private bool $active = true', $code);
    }

    #[Test]
    public function property_formats_null_value(): void
    {
        $property = PropertyDefinition::create('deletedAt', '?string')
            ->type('?string')
            ->default(null);

        $code = $property->generate();
        $this->assertStringContainsString('private ?string $deletedAt = null', $code);
    }

    #[Test]
    public function property_formats_array_value(): void
    {
        $property = PropertyDefinition::create('tags', 'array')
            ->default(['default']);

        $code = $property->generate();
        $this->assertStringContainsString("private array \$tags = ['default']", $code);
    }
}
