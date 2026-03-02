<?php

declare(strict_types=1);

namespace Aphrodite\Engine\LLM;

/**
 * Mock LLM client for testing purposes.
 */
class MockLLMClient implements LLMClientInterface
{
    protected ?string $lastError = null;
    protected array $responses = [];
    protected bool $available = true;

    /**
     * Set a predefined response for a prompt pattern.
     */
    public function setResponse(string $pattern, array $response): self
    {
        $this->responses[$pattern] = $response;
        return $this;
    }

    /**
     * Set client availability.
     */
    public function setAvailable(bool $available): self
    {
        $this->available = $available;
        return $this;
    }

    /**
     * Parse intent - returns predefined or default response.
     */
    public function parseIntent(string $description): array
    {
        if (!$this->available) {
            $this->lastError = 'Client not available';
            return [];
        }

        foreach ($this->responses as $pattern => $response) {
            if (stripos($description, $pattern) !== false) {
                return $response;
            }
        }

        // Default response based on common patterns
        return $this->generateDefaultResponse($description);
    }

    /**
     * Generate code - returns a mock response.
     */
    public function generateCode(string $prompt, array $context = []): string
    {
        if (!$this->available) {
            $this->lastError = 'Client not available';
            return '';
        }

        return "// Generated code for: {$prompt}\n// This is a mock implementation";
    }

    /**
     * Complete code - returns a mock completion.
     */
    public function completeCode(string $code, array $options = []): string
    {
        if (!$this->available) {
            $this->lastError = 'Client not available';
            return '';
        }

        return $code . "\n    // Mock completion";
    }

    /**
     * Get model name.
     */
    public function getModelName(): string
    {
        return 'mock-llm-v1';
    }

    /**
     * Check if available.
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Get last error.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Generate a default response based on patterns in the description.
     */
    protected function generateDefaultResponse(string $description): array
    {
        $entity = null;
        $features = [];
        $operations = [];
        $constraints = [];

        // Simple entity extraction (handle plurals)
        if (preg_match('/\b(user|product|order|post|comment)s?\b/i', $description, $m)) {
            $entity = ucfirst(strtolower($m[1]));
        }

        // Simple feature detection
        if (stripos($description, 'auth') !== false) {
            $features[] = 'authentication';
        }
        if (stripos($description, 'email') !== false) {
            $features[] = 'email';
        }
        if (stripos($description, 'upload') !== false) {
            $features[] = 'file_upload';
        }

        // Simple operation detection (handle partial words like remove->remov)
        if (preg_match('/\b(creat|add|new)\b/i', $description)) {
            $operations[] = 'create';
        }
        if (preg_match('/\b(list|show|get)\b/i', $description)) {
            $operations[] = 'list';
        }
        if (preg_match('/\b(delet|remov)e?s?\b/i', $description)) {
            $operations[] = 'delete';
        }

        return [
            'entity' => $entity,
            'features' => $features,
            'operations' => $operations,
            'constraints' => $constraints,
        ];
    }
}
