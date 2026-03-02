<?php

declare(strict_types=1);

namespace Aphrodite\Engine\Code;

/**
 * Represents a PHP property definition for code generation.
 */
class PropertyDefinition
{
    protected string $visibility = 'private';
    protected bool $static = false;
    protected bool $readonly = false;
    protected bool $hasGetter = false;
    protected bool $hasSetter = false;
    protected ?string $docComment = null;
    protected array $attributes = [];

    public function __construct(
        protected string $name,
        protected string $type = 'mixed',
        protected mixed $defaultValue = null,
        protected bool $hasDefault = false
    ) {}

    /**
     * Create a new property definition.
     */
    public static function create(string $name, string $type = 'mixed'): self
    {
        return new self($name, $type);
    }

    /**
     * Set visibility to public.
     */
    public function public(): self
    {
        $this->visibility = 'public';
        return $this;
    }

    /**
     * Set visibility to protected.
     */
    public function protected(): self
    {
        $this->visibility = 'protected';
        return $this;
    }

    /**
     * Set visibility to private.
     */
    public function private(): self
    {
        $this->visibility = 'private';
        return $this;
    }

    /**
     * Make the property static.
     */
    public function static(bool $static = true): self
    {
        $this->static = $static;
        return $this;
    }

    /**
     * Make the property readonly (PHP 8.1+).
     */
    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Set the type.
     */
    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set the default value.
     */
    public function default(mixed $value): self
    {
        $this->defaultValue = $value;
        $this->hasDefault = true;
        return $this;
    }

    /**
     * Add getter method.
     */
    public function withGetter(bool $getter = true): self
    {
        $this->hasGetter = $getter;
        return $this;
    }

    /**
     * Add setter method.
     */
    public function withSetter(bool $setter = true): self
    {
        $this->hasSetter = $setter;
        return $this;
    }

    /**
     * Add an attribute to the property.
     */
    public function attribute(string $name, array $args = []): self
    {
        $this->attributes[] = ['name' => $name, 'args' => $args];
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
     * Get the property name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Generate the PHP code.
     */
    public function generate(int $indentLevel = 0): string
    {
        $indent = str_repeat('    ', $indentLevel);
        $code = '';

        // Attributes
        foreach ($this->attributes as $attr) {
            $code .= $indent . $this->generateAttribute($attr);
        }

        // Doc comment
        if ($this->docComment) {
            $code .= $indent . $this->docComment . "\n";
        }

        $code .= $indent . $this->visibility . ' ';

        if ($this->readonly) {
            $code .= 'readonly ';
        }

        if ($this->static) {
            $code .= 'static ';
        }

        $code .= "{$this->type} \${$this->name}";

        if ($this->hasDefault) {
            $code .= ' = ' . $this->formatValue($this->defaultValue);
        }

        $code .= ";\n";

        return $code;
    }

    /**
     * Generate getter method if requested.
     */
    public function generateGetter(): ?MethodDefinition
    {
        if (!$this->hasGetter) {
            return null;
        }

        return MethodDefinition::create('get' . ucfirst($this->name), $this->type)
            ->public()
            ->body("return \$this->{$this->name};");
    }

    /**
     * Generate setter method if requested.
     */
    public function generateSetter(): ?MethodDefinition
    {
        if (!$this->hasSetter) {
            return null;
        }

        return MethodDefinition::create('set' . ucfirst($this->name), 'void')
            ->public()
            ->param($this->name, $this->type)
            ->body("\$this->{$this->name} = \${$this->name};");
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
