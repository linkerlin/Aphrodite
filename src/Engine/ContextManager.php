<?php

declare(strict_types=1);

namespace Aphrodite\Engine;

/**
 * Maintains project-wide context: entities, business rules, permission models,
 * and performance constraints.
 */
class ContextManager
{
    private array $entities    = [];
    private array $rules       = [];
    private array $constraints = [];

    public function addEntity(string $name, array $definition): void
    {
        $this->entities[$name] = $definition;
    }

    public function getEntity(string $name): ?array
    {
        return $this->entities[$name] ?? null;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function addRule(string $name, array $rule): void
    {
        $this->rules[$name] = $rule;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function setConstraint(string $key, mixed $value): void
    {
        $this->constraints[$key] = $value;
    }

    public function getConstraint(string $key): mixed
    {
        return $this->constraints[$key] ?? null;
    }

    public function reset(): void
    {
        $this->entities    = [];
        $this->rules       = [];
        $this->constraints = [];
    }
}
