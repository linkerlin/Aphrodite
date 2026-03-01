<?php

declare(strict_types=1);

namespace Aphrodite\DSL;

/**
 * Parses the Aphrodite DSL syntax into structured arrays.
 *
 * Example DSL:
 *   entity User {
 *       email: string! @unique @verify
 *       password: string! @hash @min(8)
 *   }
 */
class Parser
{
    /**
     * Parse a full DSL string and return a structured representation.
     *
     * @return array{entities: array, apis: array, rules: array, workflows: array}
     */
    public function parse(string $dsl): array
    {
        $result = [
            'entities'  => [],
            'apis'      => [],
            'rules'     => [],
            'workflows' => [],
        ];

        // Parse entity blocks
        if (preg_match_all('/entity\s+(\w+)\s*\{([^}]*)\}/s', $dsl, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result['entities'][] = $this->parseEntity($match[1], trim($match[2]));
            }
        }

        // Parse api blocks
        if (preg_match_all('/api\s+(\w+)\s*\{([^}]*)\}/s', $dsl, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result['apis'][] = ['name' => $match[1], 'body' => trim($match[2])];
            }
        }

        // Parse rule blocks
        if (preg_match_all('/rule\s+(\w+)\s*\{([^}]*)\}/s', $dsl, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result['rules'][] = ['name' => $match[1], 'body' => trim($match[2])];
            }
        }

        // Parse workflow blocks
        if (preg_match_all('/workflow\s+(\w+)\s*\{([^}]*)\}/s', $dsl, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result['workflows'][] = ['name' => $match[1], 'body' => trim($match[2])];
            }
        }

        return $result;
    }

    /**
     * Parse a single entity block into a structured array.
     *
     * @return array{name: string, fields: array}
     */
    public function parseEntity(string $name, string $body): array
    {
        $fields = [];
        $lines  = preg_split('/\r?\n/', $body);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $fields[] = $this->parseField($line);
        }

        return ['name' => $name, 'fields' => $fields];
    }

    /**
     * Parse a single field definition line.
     *
     * Format: `fieldName: type[!] [@directive[@directive(arg)...]]`
     *
     * @return array{name: string, type: string, required: bool, directives: string[]}
     */
    public function parseField(string $fieldDef): array
    {
        $directives = [];
        $name       = '';
        $type       = 'string';
        $required   = false;

        // Extract directives (@something or @something(arg))
        if (preg_match_all('/@(\w+)(?:\(([^)]*)\))?/', $fieldDef, $dMatches, PREG_SET_ORDER)) {
            foreach ($dMatches as $d) {
                $directives[] = isset($d[2]) && $d[2] !== ''
                    ? $d[1] . '(' . $d[2] . ')'
                    : $d[1];
            }
        }

        // Remove directives from the definition before parsing name/type
        $clean = trim(preg_replace('/@\w+(?:\([^)]*\))?/', '', $fieldDef) ?? $fieldDef);

        // Parse "name: type[!]"
        if (preg_match('/^(\w+)\s*:\s*(\w+)(!?)$/', $clean, $m)) {
            $name     = $m[1];
            $type     = $m[2];
            $required = $m[3] === '!';
        } elseif (preg_match('/^(\w+)(!?)$/', $clean, $m)) {
            // bare field name without explicit type
            $name     = $m[1];
            $required = $m[2] === '!';
        }

        return [
            'name'       => $name,
            'type'       => $type,
            'required'   => $required,
            'directives' => $directives,
        ];
    }
}
