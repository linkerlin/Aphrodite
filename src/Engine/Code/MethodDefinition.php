<?php

declare(strict_types=1);

namespace Aphrodite\Engine\Code;

/**
 * Represents a PHP method definition for code generation.
 */
class MethodDefinition
{
    protected array $parameters = [];
    protected string $body = '';
    protected string $visibility = 'public';
    protected bool $static = false;
    protected bool $final = false;
    protected bool $abstract = false;
    protected array $attributes = [];
    protected ?string $docComment = null;

    public function __construct(
        protected string $name,
        protected string $returnType = 'void'
    ) {}

    /**
     * Create a new method definition.
     */
    public static function create(string $name, string $returnType = 'void'): self
    {
        return new self($name, $returnType);
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
     * Make the method static.
     */
    public function static(bool $static = true): self
    {
        $this->static = $static;
        return $this;
    }

    /**
     * Make the method final.
     */
    public function final(bool $final = true): self
    {
        $this->final = $final;
        return $this;
    }

    /**
     * Make the method abstract.
     */
    public function abstract(bool $abstract = true): self
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Set the return type.
     */
    public function returns(string $type): self
    {
        $this->returnType = $type;
        return $this;
    }

    /**
     * Add a parameter.
     */
    public function param(string $name, string $type = 'mixed', mixed $default = null): self
    {
        $this->parameters[$name] = [
            'type' => $type,
            'default' => $default,
            'byReference' => false,
        ];
        return $this;
    }

    /**
     * Add a parameter passed by reference.
     */
    public function paramByRef(string $name, string $type = 'mixed'): self
    {
        $this->parameters[$name] = [
            'type' => $type,
            'default' => null,
            'byReference' => true,
        ];
        return $this;
    }

    /**
     * Set the method body.
     */
    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Add an attribute to the method.
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
     * Get the method name.
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
        $innerIndent = str_repeat('    ', $indentLevel + 1);

        $code = '';

        // Attributes
        foreach ($this->attributes as $attr) {
            $code .= $indent . $this->generateAttribute($attr);
        }

        // Doc comment
        if ($this->docComment) {
            $code .= $indent . $this->docComment . "\n";
        }

        // Method signature
        $code .= $indent;

        if ($this->abstract) {
            $code .= 'abstract ';
        }

        if ($this->final) {
            $code .= 'final ';
        }

        $code .= $this->visibility . ' ';

        if ($this->static) {
            $code .= 'static ';
        }

        $code .= "function {$this->name}(";

        // Parameters
        $paramStrings = [];
        foreach ($this->parameters as $name => $param) {
            $paramStr = '';
            if ($param['byReference']) {
                $paramStr .= '&';
            }
            $paramStr .= "{$param['type']} \${$name}";
            if ($param['default'] !== null) {
                $paramStr .= ' = ' . $this->formatValue($param['default']);
            }
            $paramStrings[] = $paramStr;
        }
        $code .= implode(', ', $paramStrings);

        $code .= ')';

        // Return type
        if ($this->returnType !== '' && $this->returnType !== 'mixed') {
            $code .= ": {$this->returnType}";
        }

        // Abstract method ends with semicolon
        if ($this->abstract) {
            return $code . ";\n";
        }

        $code .= "\n";

        // Body
        if ($this->body === '') {
            $code .= $indent . "{\n";
            $code .= $innerIndent . "// TODO: Implement method\n";
            $code .= $indent . "}\n";
        } else {
            $code .= $indent . "{\n";
            $bodyLines = explode("\n", $this->body);
            foreach ($bodyLines as $line) {
                $code .= $innerIndent . $line . "\n";
            }
            $code .= $indent . "}\n";
        }

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
