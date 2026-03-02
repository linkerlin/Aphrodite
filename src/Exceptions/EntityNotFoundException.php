<?php

declare(strict_types=1);

namespace Aphrodite\Exceptions;

/**
 * Exception for entity not found errors.
 */
class EntityNotFoundException extends AphroditeException
{
    protected ?string $entityType = null;
    protected mixed $identifier = null;

    public function __construct(
        string $entityType = '',
        mixed $identifier = null,
        string $message = '',
        int $code = 404,
        array $context = []
    ) {
        $this->entityType = $entityType ?: null;
        $this->identifier = $identifier;

        if ($message === '' && $entityType !== '') {
            $idStr = is_scalar($identifier) ? (string) $identifier : json_encode($identifier);
            $message = "{$entityType} not found" . ($idStr ? " with identifier: {$idStr}" : '');
        }

        parent::__construct($message ?: 'Entity not found', $code, null, $context);
    }

    /**
     * Get the entity type.
     */
    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    /**
     * Get the identifier that was not found.
     */
    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }

    /**
     * Create for a specific entity type and ID.
     */
    public static function forEntity(string $entityType, mixed $identifier): self
    {
        return new self($entityType, $identifier);
    }

    /**
     * Create for a model class.
     */
    public static function forModel(string $modelClass, mixed $identifier): self
    {
        $entityType = basename(str_replace('\\', '/', $modelClass));
        return new self($entityType, $identifier);
    }
}
