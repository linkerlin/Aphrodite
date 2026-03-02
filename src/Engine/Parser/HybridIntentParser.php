<?php

declare(strict_types=1);

namespace Aphrodite\Engine\Parser;

use Aphrodite\Engine\LLM\LLMClientInterface;

/**
 * Hybrid intent parser combining rule-based and LLM parsing.
 */
class HybridIntentParser implements IntentParserInterface
{
    protected RuleBasedParser $ruleParser;
    protected ?LLMClientInterface $llmClient = null;
    protected float $llmThreshold = 0.7;
    protected bool $preferLlm = false;

    public function __construct(?LLMClientInterface $llmClient = null)
    {
        $this->ruleParser = new RuleBasedParser();
        $this->llmClient = $llmClient;
    }

    /**
     * Set the LLM client.
     */
    public function setLlmClient(LLMClientInterface $client): self
    {
        $this->llmClient = $client;
        return $this;
    }

    /**
     * Set the confidence threshold for using LLM results.
     */
    public function setLlmThreshold(float $threshold): self
    {
        $this->llmThreshold = $threshold;
        return $this;
    }

    /**
     * Prefer LLM results when available.
     */
    public function preferLlm(bool $prefer = true): self
    {
        $this->preferLlm = $prefer;
        return $this;
    }

    /**
     * Parse using both rule-based and LLM (if available).
     */
    public function parse(string $description): Intent
    {
        $ruleIntent = $this->ruleParser->parse($description);

        // If no LLM client, return rule-based result
        if ($this->llmClient === null) {
            return $ruleIntent;
        }

        // If rule-based result is empty or low confidence, try LLM
        $ruleConfidence = $this->calculateConfidence($ruleIntent);

        if ($ruleConfidence < $this->llmThreshold || $this->preferLlm) {
            $llmIntent = $this->parseWithLlm($description);

            if ($this->preferLlm && !$llmIntent->isEmpty()) {
                return $ruleIntent->merge($llmIntent); // LLM takes precedence
            }

            if (!$llmIntent->isEmpty()) {
                return $ruleIntent->merge($llmIntent);
            }
        }

        return $ruleIntent;
    }

    /**
     * Check if the parser can handle the description.
     */
    public function canParse(string $description): bool
    {
        return $this->ruleParser->canParse($description);
    }

    /**
     * Get the parser name.
     */
    public function getName(): string
    {
        return 'hybrid';
    }

    /**
     * Parse using LLM client.
     */
    protected function parseWithLlm(string $description): Intent
    {
        if ($this->llmClient === null) {
            return Intent::empty();
        }

        try {
            $result = $this->llmClient->parseIntent($description);

            if (empty($result) || !is_array($result)) {
                return Intent::empty();
            }

            return Intent::fromArray([
                'entity' => $result['entity'] ?? null,
                'features' => $result['features'] ?? [],
                'constraints' => $result['constraints'] ?? [],
                'operations' => $result['operations'] ?? [],
                'metadata' => [
                    'parser' => 'llm',
                    'model' => $this->llmClient->getModelName(),
                ],
            ]);
        } catch (\Throwable) {
            return Intent::empty();
        }
    }

    /**
     * Calculate confidence score for rule-based result.
     */
    protected function calculateConfidence(Intent $intent): float
    {
        if ($intent->isEmpty()) {
            return 0.0;
        }

        $score = 0.0;

        // Entity detected adds significant confidence
        if ($intent->hasEntity()) {
            $score += 0.4;
        }

        // Each feature adds confidence
        $featureCount = count($intent->getFeatures());
        $score += min($featureCount * 0.1, 0.3);

        // Each operation adds confidence
        $operationCount = count($intent->getOperations());
        $score += min($operationCount * 0.1, 0.2);

        // Constraints add some confidence
        if (!empty($intent->getConstraints())) {
            $score += 0.1;
        }

        return min($score, 1.0);
    }
}
