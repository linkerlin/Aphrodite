<?php

declare(strict_types=1);

namespace Aphrodite\Engine\Parser;

/**
 * Immutable value object representing a parsed intent from natural language.
 */
class Intent
{
    protected array $fields = [];

    public function __construct(
        protected ?string $entity = null,
        protected array $features = [],
        protected array $constraints = [],
        protected array $operations = [],
        protected array $metadata = []
    ) {
        $this->validate();
    }

    /**
     * Create an intent from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entity: $data['entity'] ?? null,
            features: $data['features'] ?? [],
            constraints: $data['constraints'] ?? [],
            operations: $data['operations'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Create an empty intent.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Get the target entity name.
     */
    public function getEntity(): ?string
    {
        return $this->entity;
    }

    /**
     * Check if an entity is specified.
     */
    public function hasEntity(): bool
    {
        return $this->entity !== null;
    }

    /**
     * Get detected features.
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * Check if a feature is present.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    /**
     * Get detected constraints.
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Check if a constraint exists.
     */
    public function hasConstraint(string $constraint): bool
    {
        return array_key_exists($constraint, $this->constraints);
    }

    /**
     * Get a constraint value.
     */
    public function getConstraint(string $constraint, mixed $default = null): mixed
    {
        return $this->constraints[$constraint] ?? $default;
    }

    /**
     * Get detected operations.
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Check if an operation is present.
     */
    public function hasOperation(string $operation): bool
    {
        return in_array($operation, $this->operations, true);
    }

    /**
     * Get metadata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get a metadata value.
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if the intent is empty.
     */
    public function isEmpty(): bool
    {
        return $this->entity === null
            && empty($this->features)
            && empty($this->constraints)
            && empty($this->operations);
    }

    /**
     * Create a new intent with merged data.
     */
    public function merge(self $other): self
    {
        return new self(
            entity: $other->entity ?? $this->entity,
            features: array_unique(array_merge($this->features, $other->features)),
            constraints: array_merge($this->constraints, $other->constraints),
            operations: array_unique(array_merge($this->operations, $other->operations)),
            metadata: array_merge($this->metadata, $other->metadata)
        );
    }

    /**
     * Create a new intent with added feature.
     */
    public function withFeature(string $feature): self
    {
        if ($this->hasFeature($feature)) {
            return $this;
        }

        return new self(
            entity: $this->entity,
            features: [...$this->features, $feature],
            constraints: $this->constraints,
            operations: $this->operations,
            metadata: $this->metadata
        );
    }

    /**
     * Create a new intent with added operation.
     */
    public function withOperation(string $operation): self
    {
        if ($this->hasOperation($operation)) {
            return $this;
        }

        return new self(
            entity: $this->entity,
            features: $this->features,
            constraints: $this->constraints,
            operations: [...$this->operations, $operation],
            metadata: $this->metadata
        );
    }

    /**
     * Create a new intent with added constraint.
     */
    public function withConstraint(string $name, mixed $value): self
    {
        return new self(
            entity: $this->entity,
            features: $this->features,
            constraints: [...$this->constraints, $name => $value],
            operations: $this->operations,
            metadata: $this->metadata
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'entity' => $this->entity,
            'features' => $this->features,
            'constraints' => $this->constraints,
            'operations' => $this->operations,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Validate the intent data.
     */
    protected function validate(): void
    {
        if ($this->entity !== null) {
            $this->entity = ucfirst(trim($this->entity));
        }

        $this->features = array_values(array_unique(array_filter($this->features, 'is_string')));
        $this->operations = array_values(array_unique(array_filter($this->operations, 'is_string')));
    }
}
