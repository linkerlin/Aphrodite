<?php

declare(strict_types=1);

namespace Aphrodite\Engine\Code;

/**
 * Represents a PHP class definition for code generation.
 */
class ClassDefinition
{
    protected array $methods = [];
    protected array $properties = [];
    protected array $useStatements = [];
    protected bool $final = false;
    protected bool $readonly = false;
    protected ?string $extends = null;
    protected array $implements = [];
    protected array $attributes = [];
    protected ?string $docComment = null;

    public function __construct(
        protected string $name,
        protected string $namespace = ''
    ) {}

    /**
     * Create a new class definition.
     */
    public static function create(string $name, string $namespace = ''): self
    {
        return new self($name, $namespace);
    }

    /**
     * Set the namespace.
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Set the class to be final.
     */
    public function final(bool $final = true): self
    {
        $this->final = $final;
        return $this;
    }

    /**
     * Set the class to be readonly.
     */
    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Set the parent class.
     */
    public function extends(?string $extends): self
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * Add an interface to implement.
     */
    public function implements(string $interface): self
    {
        $this->implements[] = $interface;
        return $this;
    }

    /**
     * Add a use statement.
     */
    public function use(string $class, ?string $alias = null): self
    {
        $this->useStatements[$class] = $alias;
        return $this;
    }

    /**
     * Add a property.
     */
    public function property(PropertyDefinition $property): self
    {
        $this->properties[] = $property;
        return $this;
    }

    /**
     * Add a method.
     */
    public function method(MethodDefinition $method): self
    {
        $this->methods[] = $method;
        return $this;
    }

    /**
     * Add a class attribute.
     */
    public function attribute(string $attribute, array $args = []): self
    {
        $this->attributes[] = ['name' => $attribute, 'args' => $args];
        return $this;
    }

    /**
     * Set the doc comment.
     */
    public function docComment(?string $comment): self
    {
        $this->docComment = $comment;
        return $this;
    }

    /**
     * Get the class name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the namespace.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Generate the PHP code.
     */
    public function generate(): string
    {
        $code = "<?php\n\ndeclare(strict_types=1);\n\n";

        // Namespace
        if ($this->namespace) {
            $code .= "namespace {$this->namespace};\n\n";
        }

        // Use statements
        if (!empty($this->useStatements)) {
            foreach ($this->useStatements as $class => $alias) {
                $code .= "use {$class}";
                if ($alias !== null) {
                    $code .= " as {$alias}";
                }
                $code .= ";\n";
            }
            $code .= "\n";
        }

        // Class attributes
        foreach ($this->attributes as $attr) {
            $code .= $this->generateAttribute($attr);
        }

        // Class doc comment
        if ($this->docComment) {
            $code .= $this->docComment . "\n";
        }

        // Class declaration
        $modifiers = '';
        if ($this->final) {
            $modifiers .= 'final ';
        }
        if ($this->readonly) {
            $modifiers .= 'readonly ';
        }

        $code .= "{$modifiers}class {$this->name}";

        if ($this->extends) {
            $code .= " extends {$this->extends}";
        }

        if (!empty($this->implements)) {
            $code .= ' implements ' . implode(', ', $this->implements);
        }

        $code .= "\n{\n";

        // Properties
        foreach ($this->properties as $property) {
            $code .= $property->generate(1) . "\n";
        }

        // Methods
        foreach ($this->methods as $method) {
            $code .= $method->generate(1) . "\n";
        }

        $code .= "}\n";

        return $code;
    }

    /**
     * Generate attribute string.
     */
    protected function generateAttribute(array $attr): string
    {
        $args = '';
        if (!empty($attr['args'])) {
            $argStrings = [];
            foreach ($attr['args'] as $key => $value) {
                if (is_string($key)) {
                    $argStrings[] = "{$key}: " . $this->formatValue($value);
                } else {
                    $argStrings[] = $this->formatValue($value);
                }
            }
            $args = '(' . implode(', ', $argStrings) . ')';
        }

        return "#[{$attr['name']}{$args}]\n";
    }

    /**
     * Format a PHP value for code output.
     */
    protected function formatValue(mixed $value): string
    {
        return match (true) {
            is_string($value) => "'" . addslashes($value) . "'",
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            is_array($value) => '[' . implode(', ', array_map([$this, 'formatValue'], $value)) . ']',
            default => (string) $value,
        };
    }
}
