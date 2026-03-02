<?php

declare(strict_types=1);

namespace Aphrodite\Config;

use InvalidArgumentException;

/**
 * Configuration validator for validating config values against schemas.
 */
class ConfigValidator
{
    protected array $errors = [];
    protected bool $strict = false;

    public function __construct(
        protected ?ConfigSchema $schema = null
    ) {}

    /**
     * Create a validator with a schema.
     */
    public static function for(ConfigSchema $schema): self
    {
        return new self($schema);
    }

    /**
     * Create a validator without a schema.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Enable strict mode (unknown keys cause errors).
     */
    public function strict(bool $strict = true): self
    {
        $this->strict = $strict;
        return $this;
    }

    /**
     * Validate configuration against the schema.
     */
    public function validate(array $config): bool
    {
        $this->errors = [];

        if ($this->schema === null) {
            return true;
        }

        $rules = $this->schema->getRules();

        // Check for required keys and validate types
        foreach ($rules as $key => $rule) {
            $this->validateKey($config, $key, $rule);
        }

        // Check for unknown keys in strict mode
        if ($this->strict) {
            foreach (array_keys($config) as $key) {
                if (!isset($rules[$key])) {
                    $this->errors[$key] = "Unknown configuration key: {$key}";
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate a single key.
     */
    protected function validateKey(array $config, string $key, array $rule): void
    {
        $exists = array_key_exists($key, $config);
        $value = $config[$key] ?? null;

        // Check required
        if ($rule['required'] && !$exists) {
            $this->errors[$key] = "Required configuration key missing: {$key}";
            return;
        }

        // Skip validation if not required and not provided
        if (!$exists && !$rule['required']) {
            return;
        }

        // Validate type
        $this->validateType($key, $value, $rule);
    }

    /**
     * Validate value type.
     */
    protected function validateType(string $key, mixed $value, array $rule): void
    {
        $type = $rule['type'];

        // Allow null if not required
        if ($value === null && !$rule['required']) {
            return;
        }

        $valid = match ($type) {
            'string' => is_string($value),
            'integer', 'int' => is_int($value),
            'float', 'double' => is_float($value) || is_int($value),
            'boolean', 'bool' => is_bool($value),
            'array' => is_array($value),
            'enum' => in_array($value, $rule['allowed'] ?? [], true),
            default => true,
        };

        if (!$valid) {
            $expected = $type === 'enum'
                ? 'one of: ' . implode(', ', $rule['allowed'] ?? [])
                : $type;

            $actual = gettype($value);
            $this->errors[$key] = "Invalid type for {$key}: expected {$expected}, got {$actual}";
        }
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get first error.
     */
    public function getFirstError(): ?string
    {
        return reset($this->errors) ?: null;
    }

    /**
     * Validate and throw on failure.
     */
    public function validateOrFail(array $config): void
    {
        if (!$this->validate($config)) {
            throw new InvalidArgumentException(
                'Configuration validation failed: ' . implode('; ', $this->errors)
            );
        }
    }

    /**
     * Apply defaults to configuration.
     */
    public function applyDefaults(array $config): array
    {
        if ($this->schema === null) {
            return $config;
        }

        $rules = $this->schema->getRules();

        foreach ($rules as $key => $rule) {
            if (!array_key_exists($key, $config) && array_key_exists('default', $rule)) {
                $config[$key] = $rule['default'];
            }
        }

        return $config;
    }

    /**
     * Validate and return config with defaults applied.
     */
    public function validateAndApplyDefaults(array $config): array
    {
        if (!$this->validate($config)) {
            throw new InvalidArgumentException(
                'Configuration validation failed: ' . implode('; ', $this->errors)
            );
        }

        return $this->applyDefaults($config);
    }
}
