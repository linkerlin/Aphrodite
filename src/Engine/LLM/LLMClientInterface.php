<?php

declare(strict_types=1);

namespace Aphrodite\Engine\LLM;

/**
 * Interface for LLM clients used for intent parsing and code generation.
 */
interface LLMClientInterface
{
    /**
     * Parse natural language into structured intent.
     *
     * @return array{
     *     entity?: string|null,
     *     features?: array<string>,
     *     constraints?: array<string, mixed>,
     *     operations?: array<string>,
     * }
     */
    public function parseIntent(string $description): array;

    /**
     * Generate code from a prompt.
     */
    public function generateCode(string $prompt, array $context = []): string;

    /**
     * Complete a partial code snippet.
     */
    public function completeCode(string $code, array $options = []): string;

    /**
     * Get the model name/identifier.
     */
    public function getModelName(): string;

    /**
     * Check if the client is available.
     */
    public function isAvailable(): bool;

    /**
     * Get the last error message.
     */
    public function getLastError(): ?string;
}
