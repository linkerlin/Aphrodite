<?php

declare(strict_types=1);

namespace Aphrodite\Validation;

use Aphrodite\Http\Request;

// Load rules from Rules.php
require_once __DIR__ . '/Rules.php';

/**
 * Validator for validating input data against rules.
 */
class Validator
{
    protected array $data;
    protected array $rules;
    protected array $messages = [];
    protected array $errors = [];
    protected array $validators = [];

    public function __construct(array $data = [], array $rules = [], array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->registerDefaultValidators();
    }

    /**
     * Create a new validator instance.
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * Create validator from request.
     */
    public static function fromRequest(Request $request, array $rules, array $messages = []): self
    {
        return new self($request->all(), $rules, $messages);
    }

    /**
     * Register default validation rules.
     */
    protected function registerDefaultValidators(): void
    {
        $this->validators = [
            'required' => RequiredRule::class,
            'email' => EmailRule::class,
            'min_length' => MinLengthRule::class,
            'max_length' => MaxLengthRule::class,
            'min' => MinRule::class,
            'max' => MaxRule::class,
            'numeric' => NumericRule::class,
            'integer' => IntegerRule::class,
            'alpha' => AlphaRule::class,
            'alpha_num' => AlphaNumRule::class,
            'regex' => RegexRule::class,
            'in' => InRule::class,
            'url' => UrlRule::class,
            'date_format' => DateFormatRule::class,
            'confirmed' => ConfirmedRule::class,
        ];
    }

    /**
     * Register a custom validator.
     */
    public function addValidator(string $name, string $class): self
    {
        $this->validators[$name] = $class;
        return $this;
    }

    /**
     * Validate the data.
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $rules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($rules as $rule) {
                $this->validateField($field, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate a single field.
     */
    protected function validateField(string $field, string|array $rule): void
    {
        if (is_array($rule)) {
            // Handle format: ['rule_name', param1, param2, ...]
            if (count($rule) === 0) {
                return;
            }
            
            $ruleName = array_shift($rule);
            $ruleParams = $rule;
            
            // Convert string params to appropriate types
            $ruleParams = array_map(function($param) {
                if (is_numeric($param)) {
                    return strpos((string)$param, '.') !== false ? (float)$param : (int)$param;
                }
                return $param;
            }, $ruleParams);
        } else {
            $parts = explode(':', $rule, 2);
            $ruleName = $parts[0];
            $ruleParams = isset($parts[1]) ? explode(',', $parts[1]) : [];
            
            // Convert string params to appropriate types
            $ruleParams = array_map(function($param) {
                if (is_numeric($param)) {
                    return strpos($param, '.') !== false ? (float)$param : (int)$param;
                }
                return $param;
            }, $ruleParams);
        }

        $value = $this->data[$field] ?? null;

        if (!$this->isValidatable($value, $ruleName)) {
            return;
        }

        $validator = $this->resolveValidator($ruleName, $ruleParams);

        if (!$validator->validate($value, $this->data)) {
            $this->addError($field, $validator->message(), $ruleName);
        }
    }

    /**
     * Check if field should be validated.
     */
    protected function isValidatable(mixed $value, string $ruleName): bool
    {
        if ($ruleName === 'required') {
            return true;
        }

        return !is_null($value);
    }

    /**
     * Resolve validator instance.
     */
    protected function resolveValidator(string $name, array $params): RuleInterface
    {
        if (isset($this->validators[$name])) {
            $class = $this->validators[$name];
            return new $class(...$params);
        }

        if (class_exists($name) && is_subclass_of($name, RuleInterface::class)) {
            return new $name(...$params);
        }

        throw new \InvalidArgumentException("Validator [{$name}] not found.");
    }

    /**
     * Add validation error.
     */
    protected function addError(string $field, string $message, string $rule): void
    {
        $key = $this->getMessageKey($field, $rule);

        if (isset($this->messages[$key])) {
            $message = $this->messages[$key];
        } elseif (isset($this->messages[$field])) {
            $message = $this->messages[$field];
        }

        $message = str_replace(':field', $field, $message);

        $this->errors[$field][] = $message;
    }

    /**
     * Get message key.
     */
    protected function getMessageKey(string $field, string $rule): string
    {
        return "{$field}.{$rule}";
    }

    /**
     * Check if validation passes.
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * Check if validation fails.
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
     * Get validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message.
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get first error from all fields.
     */
    public function firstErrors(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    /**
     * Check if field has errors.
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Get validated data (only fields with rules).
     */
    public function validated(): array
    {
        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }

    /**
     * Add validation rule.
     */
    public function addRule(string $field, string|array $rule): self
    {
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }

        $this->rules[$field][] = $rule;
        return $this;
    }

    /**
     * Sometimes validation (conditional rules).
     */
    public function sometimes(string $field, string|array $rules, callable $condition): self
    {
        if ($condition($this->data)) {
            $this->addRule($field, $rules);
        }

        return $this;
    }
}
