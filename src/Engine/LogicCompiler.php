<?php

declare(strict_types=1);

namespace Aphrodite\Engine;

/**
 * Compiles natural language business rules into executable PHP code snippets.
 */
class LogicCompiler
{
    private const CONDITION_MAP = [
        '/\bif\s+(.+?)\s+then\s+(.+)/i'    => 'conditional',
        '/\bwhen\s+(.+?)\s+(?:then\s+)?(.+)/i' => 'conditional',
        '/\bmust\s+be\s+(unique|required|not\s+null)/i' => 'constraint',
        '/\bshould\s+have\s+(.+)/i'         => 'assertion',
    ];

    /**
     * Compile a natural language rule string into PHP code.
     */
    public function compile(string $naturalLanguageRule): string
    {
        $rule = $naturalLanguageRule;

        // Conditional: "if X then Y"
        if (preg_match('/\bif\s+(.+?)\s+then\s+(.+)/i', $rule, $m)) {
            $condition = $this->compileConditionExpr(trim($m[1]));
            $action    = $this->compileActionExpr(trim($m[2]));
            return "if ({$condition}) {\n    {$action};\n}";
        }

        // When variant
        if (preg_match('/\bwhen\s+(.+?)\s+(?:then\s+)?(.+)/i', $rule, $m)) {
            $condition = $this->compileConditionExpr(trim($m[1]));
            $action    = $this->compileActionExpr(trim($m[2]));
            return "if ({$condition}) {\n    {$action};\n}";
        }

        // Constraint: "X must be unique"
        if (preg_match('/\b(\w+)\s+must\s+be\s+(.+)/i', $rule, $m)) {
            $field      = $m[1];
            $constraint = trim($m[2]);
            return "assert(\$this->validate_{$field}_{$constraint}(\${$field}), '{$field} must be {$constraint}');";
        }

        // Fallback: wrap in a comment with a throw
        $escaped = addslashes($naturalLanguageRule);
        return "// Rule: {$escaped}\nthrow new \\LogicException('Rule not yet implemented: {$escaped}');";
    }

    /**
     * Compile a structured rule array to PHP code.
     *
     * Expected keys: 'condition' (string), 'action' (string), optionally 'else' (string).
     */
    public function compileRule(array $rule): string
    {
        if (isset($rule['condition'], $rule['action'])) {
            $condition = $this->compileConditionExpr($rule['condition']);
            $action    = $this->compileActionExpr($rule['action']);
            $php       = "if ({$condition}) {\n    {$action};\n}";

            if (isset($rule['else'])) {
                $elseAction = $this->compileActionExpr($rule['else']);
                $php .= " else {\n    {$elseAction};\n}";
            }

            return $php;
        }

        if (isset($rule['constraint'], $rule['field'])) {
            $field      = $rule['field'];
            $constraint = $rule['constraint'];
            return "assert(\$this->validate_{$field}(\${$field}), '{$field} constraint violated: {$constraint}');";
        }

        return '// Unknown rule structure';
    }

    private function compileConditionExpr(string $expr): string
    {
        // Simple mapping of common natural language patterns
        $expr = preg_replace('/\bis\s+greater\s+than\s+(\w+)/i', '> $1', $expr) ?? $expr;
        $expr = preg_replace('/\bis\s+less\s+than\s+(\w+)/i', '< $1', $expr) ?? $expr;
        $expr = preg_replace('/\bis\s+equal\s+to\s+(\w+)/i', '=== $1', $expr) ?? $expr;
        $expr = preg_replace('/\bis\s+not\s+null/i', '!== null', $expr) ?? $expr;
        $expr = preg_replace('/\bis\s+null/i', '=== null', $expr) ?? $expr;
        $expr = preg_replace('/\b(\w+)\b(?!\s*[><=!])/i', '$$1', $expr) ?? $expr;

        return $expr;
    }

    private function compileActionExpr(string $expr): string
    {
        // "throw error X" → throw new \RuntimeException(X)
        if (preg_match('/throw\s+(?:error|exception)?\s*["\']?(.+)["\']?/i', $expr, $m)) {
            $msg = addslashes(trim($m[1], '\'"'));
            return "throw new \\RuntimeException('{$msg}')";
        }

        // "set X to Y"
        if (preg_match('/set\s+(\w+)\s+to\s+(.+)/i', $expr, $m)) {
            return "\${$m[1]} = {$m[2]}";
        }

        // "return X"
        if (preg_match('/return\s+(.+)/i', $expr, $m)) {
            return "return {$m[1]}";
        }

        return $expr;
    }
}
