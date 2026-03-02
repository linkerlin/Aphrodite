<?php

declare(strict_types=1);

namespace Aphrodite\Validation;

use Aphrodite\ORM\Entity;

/**
 * Validates entity based on PHP attributes.
 */
class EntityValidator
{
    /**
     * Validate an entity against its PHP attribute rules.
     */
    public static function validate(Entity $entity): array
    {
        $errors = [];
        $reflection = new \ReflectionClass($entity);

        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();
            $value = $entity->$propertyName ?? null;

            foreach ($property->getAttributes() as $attribute) {
                $rule = $attribute->newInstance();
                $ruleName = (new \ReflectionClass($rule))->getShortName();

                if (!self::validateAttribute($rule, $value)) {
                    $errors[$propertyName][] = "Invalid value for {$propertyName}";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate a single attribute.
     */
    protected static function validateAttribute(object $rule, mixed $value): bool
    {
        $class = get_class($rule);

        return match ($class) {
            \Aphrodite\Validation\Attributes\Required::class => !is_null($value) && $value !== '',
            \Aphrodite\Validation\Attributes\Email::class => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            \Aphrodite\Validation\Attributes\MinLength::class => mb_strlen($value) >= $rule->length,
            \Aphrodite\Validation\Attributes\MaxLength::class => mb_strlen($value) <= $rule->length,
            default => true,
        };
    }
}
