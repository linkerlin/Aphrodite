<?php

declare(strict_types=1);

namespace Aphrodite\Config;

/**
 * Configuration schema definition for validation.
 */
class ConfigSchema
{
    protected array $rules = [];

    public function __construct(
        protected string $name = 'config'
    ) {}

    /**
     * Create a new schema instance.
     */
    public static function make(string $name = 'config'): self
    {
        return new self($name);
    }

    /**
     * Define a required string configuration.
     */
    public function string(string $key, ?string $default = null, bool $required = false): self
    {
        $this->rules[$key] = [
            'type' => 'string',
            'default' => $default,
            'required' => $required,
        ];
        return $this;
    }

    /**
     * Define a required configuration.
     */
    public function required(string $key, string $type = 'string'): self
    {
        $this->rules[$key] = [
            'type' => $type,
            'default' => null,
            'required' => true,
        ];
        return $this;
    }

    /**
     * Define an optional string configuration.
     */
    public function optional(string $key, string $type = 'string', mixed $default = null): self
    {
        $this->rules[$key] = [
            'type' => $type,
            'default' => $default,
            'required' => false,
        ];
        return $this;
    }

    /**
     * Define an integer configuration.
     */
    public function integer(string $key, ?int $default = null, bool $required = false): self
    {
        $this->rules[$key] = [
            'type' => 'integer',
            'default' => $default,
            'required' => $required,
        ];
        return $this;
    }

    /**
     * Define a float configuration.
     */
    public function float(string $key, ?float $default = null, bool $required = false): self
    {
        $this->rules[$key] = [
            'type' => 'float',
            'default' => $default,
            'required' => $required,
        ];
        return $this;
    }

    /**
     * Define a boolean configuration.
     */
    public function boolean(string $key, ?bool $default = null, bool $required = false): self
    {
        $this->rules[$key] = [
            'type' => 'boolean',
            'default' => $default,
            'required' => $required,
        ];
        return $this;
    }

    /**
     * Define an array configuration.
     */
    public function array(string $key, ?array $default = null, bool $required = false): self
    {
        $this->rules[$key] = [
            'type' => 'array',
            'default' => $default,
            'required' => $required,
        ];
        return $this;
    }

    /**
     * Define an enum configuration.
     */
    public function enum(string $key, array $allowed, mixed $default = null, bool $required = false): self
    {
        $this->rules[$key] = [
            'type' => 'enum',
            'allowed' => $allowed,
            'default' => $default,
            'required' => $required,
        ];
        return $this;
    }

    /**
     * Get all rules.
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Get a specific rule.
     */
    public function getRule(string $key): ?array
    {
        return $this->rules[$key] ?? null;
    }

    /**
     * Check if a key is defined.
     */
    public function has(string $key): bool
    {
        return isset($this->rules[$key]);
    }

    /**
     * Get the schema name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
