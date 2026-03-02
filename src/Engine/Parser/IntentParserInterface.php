<?php

declare(strict_types=1);

namespace Aphrodite\Engine\Parser;

/**
 * Interface for intent parsers.
 */
interface IntentParserInterface
{
    /**
     * Parse a natural language description into a structured intent.
     */
    public function parse(string $description): Intent;

    /**
     * Check if the parser can handle the given description.
     */
    public function canParse(string $description): bool;

    /**
     * Get the parser name/identifier.
     */
    public function getName(): string;
}
