<?php

declare(strict_types=1);

namespace Aphrodite\Engine\Parser;

/**
 * Rule-based intent parser using pattern matching.
 */
class RuleBasedParser implements IntentParserInterface
{
    protected const KNOWN_ENTITIES = [
        'user', 'product', 'order', 'post', 'comment', 'category',
        'invoice', 'payment', 'customer', 'employee', 'article', 'tag',
        'role', 'permission', 'setting', 'log', 'file', 'document',
    ];

    protected const FEATURE_PATTERNS = [
        'authentication' => '/auth(?:entication)?|login|logout/i',
        'registration'   => '/register|signup|sign[-_ ]?up/i',
        'email'          => '/email/i',
        'password'       => '/password/i',
        'search'         => '/search|filter/i',
        'pagination'     => '/paginat|page/i',
        'file_upload'    => '/upload|file|image|photo|attachment/i',
        'api'            => '/api|rest|endpoint/i',
        'validation'     => '/validat|verify|check/i',
        'notification'   => '/notif|alert|message/i',
        'cache'          => '/cache/i',
        'rate_limit'     => '/rate\s*limit|throttle/i',
    ];

    protected const OPERATION_PATTERNS = [
        'create' => '/creat|add|insert|register|new|store/i',
        'list'   => '/list|index|show all|get all|fetch all|browse/i',
        'read'   => '/read|get|fetch|find|show|view|display|detail/i',
        'update' => '/updat|edit|modif|chang|patch/i',
        'delete' => '/delet|remov|destroy|erase/i',
    ];

    protected const CONSTRAINT_PATTERNS = [
        'required'     => '/required|mandatory|must have|cannot be empty/i',
        'unique'       => '/unique|distinct|one of a kind/i',
        'min_length'   => '/min(?:imum)?\s*(?:length|len|chars?)\s*(?:of\s*)?(\d+)/i',
        'max_length'   => '/max(?:imum)?\s*(?:length|len|chars?)\s*(?:of\s*)?(\d+)/i',
        'email_format' => '/valid\s*email|email\s*format/i',
        'min_value'    => '/min(?:imum)?\s*(?:value|val)?\s*(?:of\s*)?(\d+)/i',
        'max_value'    => '/max(?:imum)?\s*(?:value|val)?\s*(?:of\s*)?(\d+)/i',
        'regex'        => '/(?:match|pattern|regex)\s*[:=]?\s*["\']?([^"\']+)["\']?/i',
    ];

    /**
     * Parse a natural language description.
     */
    public function parse(string $description): Intent
    {
        if (trim($description) === '') {
            return Intent::empty();
        }

        return new Intent(
            entity: $this->extractEntity($description),
            features: $this->extractFeatures($description),
            constraints: $this->extractConstraints($description),
            operations: $this->extractOperations($description),
            metadata: [
                'parser' => $this->getName(),
                'original' => $description,
            ]
        );
    }

    /**
     * Check if the parser can handle the description.
     */
    public function canParse(string $description): bool
    {
        return trim($description) !== '';
    }

    /**
     * Get the parser name.
     */
    public function getName(): string
    {
        return 'rule-based';
    }

    /**
     * Extract entity from description.
     */
    protected function extractEntity(string $description): ?string
    {
        // Check known entities first
        foreach (self::KNOWN_ENTITIES as $entity) {
            if (preg_match('/\b' . preg_quote($entity, '/') . 's?\b/i', $description)) {
                return ucfirst(strtolower($entity));
            }
        }

        // Try to extract a capitalized noun following common patterns
        $patterns = [
            '/\b(?:a|an|the|for|manage)\s+([A-Z][a-z]+)\b/',
            '/\b([A-Z][a-z]+)\s+(?:entity|model|resource)\b/i',
            '/\bcreate\s+(?:a\s+)?(?:new\s+)?([A-Z][a-z]+)\b/i',
            '/\bmanage\s+([A-Z][a-z]+)s?\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return ucfirst(strtolower($matches[1]));
            }
        }

        return null;
    }

    /**
     * Extract features from description.
     */
    protected function extractFeatures(string $description): array
    {
        $features = [];
        foreach (self::FEATURE_PATTERNS as $feature => $pattern) {
            if (preg_match($pattern, $description)) {
                $features[] = $feature;
            }
        }
        return $features;
    }

    /**
     * Extract constraints from description.
     */
    protected function extractConstraints(string $description): array
    {
        $constraints = [];
        foreach (self::CONSTRAINT_PATTERNS as $constraint => $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                if (isset($matches[1])) {
                    $constraints[$constraint] = $matches[1];
                } else {
                    $constraints[$constraint] = true;
                }
            }
        }
        return $constraints;
    }

    /**
     * Extract operations from description.
     */
    protected function extractOperations(string $description): array
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
