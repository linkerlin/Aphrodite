<?php

declare(strict_types=1);

namespace Aphrodite\Engine;

/**
 * Parses natural language descriptions into structured intent arrays.
 */
class IntentParser
{
    private const KNOWN_ENTITIES = [
        'user', 'product', 'order', 'post', 'comment', 'category',
        'invoice', 'payment', 'customer', 'employee', 'article', 'tag',
    ];

    private const FEATURE_PATTERNS = [
        'authentication' => '/auth(?:entication)?|login|logout/i',
        'registration'   => '/register|signup|sign[-_ ]?up/i',
        'email'          => '/email/i',
        'password'       => '/password/i',
        'search'         => '/search|filter/i',
        'pagination'     => '/paginat|page/i',
        'file_upload'    => '/upload|file|image|photo/i',
        'api'            => '/api|rest|endpoint/i',
    ];

    private const OPERATION_PATTERNS = [
        'create' => '/creat|add|insert|register|new/i',
        'list'   => '/list|index|show all|get all|fetch all/i',
        'read'   => '/read|get|fetch|find|show|view|display/i',
        'update' => '/updat|edit|modif|chang/i',
        'delete' => '/delet|remov|destroy/i',
    ];

    private const CONSTRAINT_PATTERNS = [
        'required'  => '/required|mandatory|must have/i',
        'unique'    => '/unique|distinct/i',
        'min_length' => '/min(?:imum)?\s*length\s*(?:of\s*)?(\d+)/i',
        'max_length' => '/max(?:imum)?\s*length\s*(?:of\s*)?(\d+)/i',
        'email_format' => '/valid\s*email/i',
    ];

    /**
     * Parse a natural language description into a structured intent array.
     */
    public function parse(string $description): array
    {
        if ($description === '') {
            return [
                'entity'      => null,
                'features'    => [],
                'constraints' => [],
                'operations'  => [],
            ];
        }

        return [
            'entity'      => $this->extractEntity($description),
            'features'    => $this->extractFeatures($description),
            'constraints' => $this->extractConstraints($description),
            'operations'  => $this->extractOperations($description),
        ];
    }

    private function extractEntity(string $description): ?string
    {
        foreach (self::KNOWN_ENTITIES as $entity) {
            if (preg_match('/\b' . preg_quote($entity, '/') . '\b/i', $description)) {
                return ucfirst(strtolower($entity));
            }
        }

        // Try to extract a capitalised noun following "a", "an", "the", or "for"
        if (preg_match('/\b(?:a|an|the|for)\s+([A-Z][a-z]+)\b/', $description, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extractFeatures(string $description): array
    {
        $features = [];
        foreach (self::FEATURE_PATTERNS as $feature => $pattern) {
            if (preg_match($pattern, $description)) {
                $features[] = $feature;
            }
        }
        return $features;
    }

    private function extractConstraints(string $description): array
    {
        $constraints = [];
        foreach (self::CONSTRAINT_PATTERNS as $constraint => $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                if (isset($matches[1])) {
                    $constraints[$constraint] = (int) $matches[1];
                } else {
                    $constraints[$constraint] = true;
                }
            }
        }
        return $constraints;
    }

    private function extractOperations(string $description): array
    {
        $operations = [];
        foreach (self::OPERATION_PATTERNS as $operation => $pattern) {
            if (preg_match($pattern, $description)) {
                $operations[] = $operation;
            }
        }
        return $operations;
    }
}
