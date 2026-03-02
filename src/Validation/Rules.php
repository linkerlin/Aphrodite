<?php

declare(strict_types=1);

namespace Aphrodite\Validation;

use Aphrodite\Http\Request;

/**
 * Validation rule interface.
 */
interface RuleInterface
{
    /**
     * Validate the rule.
     */
    public function validate(mixed $value, array $data): bool;

    /**
     * Get error message.
     */
    public function message(): string;
}

/**
 * Base rule class.
 */
abstract class Rule implements RuleInterface
{
    protected string $message;

    public function message(): string
    {
        return $this->message;
    }
}

/**
 * Required field validation.
 */
class RequiredRule extends Rule
{
    public function __construct()
    {
        $this->message = 'The field is required.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }
}

/**
 * Email validation.
 */
class EmailRule extends Rule
{
    public function __construct()
    {
        $this->message = 'The field must be a valid email address.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Minimum length validation.
 */
class MinLengthRule extends Rule
{
    protected int $length;

    public function __construct(int $length)
    {
        $this->length = $length;
        $this->message = "The field must be at least {$length} characters.";
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return mb_strlen($value) >= $this->length;
    }
}

/**
 * Maximum length validation.
 */
class MaxLengthRule extends Rule
{
    protected int $length;

    public function __construct(int $length)
    {
        $this->length = $length;
        $this->message = "The field must not exceed {$length} characters.";
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return mb_strlen($value) <= $this->length;
    }
}

/**
 * Minimum value validation.
 */
class MinRule extends Rule
{
    protected int|float $min;

    public function __construct(int|float $min)
    {
        $this->min = $min;
        $this->message = "The field must be at least {$min}.";
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return is_numeric($value) && $value >= $this->min;
    }
}

/**
 * Maximum value validation.
 */
class MaxRule extends Rule
{
    protected int|float $max;

    public function __construct(int|float $max)
    {
        $this->max = $max;
        $this->message = "The field must not exceed {$max}.";
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return is_numeric($value) && $value <= $this->max;
    }
}

/**
 * Numeric value validation.
 */
class NumericRule extends Rule
{
    public function __construct()
    {
        $this->message = 'The field must be numeric.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return is_numeric($value);
    }
}

/**
 * Integer value validation.
 */
class IntegerRule extends Rule
{
    public function __construct()
    {
        $this->message = 'The field must be an integer.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
}

/**
 * Alpha characters only validation.
 */
class AlphaRule extends Rule
{
    public function __construct()
    {
        $this->message = 'The field may only contain letters.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }
}

/**
 * Alpha-numeric characters validation.
 */
class AlphaNumRule extends Rule
{
    public function __construct()
    {
        $this->message = 'The field may only contain letters and numbers.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }
}

/**
 * Regex pattern validation.
 */
class RegexRule extends Rule
{
    protected string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
        $this->message = 'The field format is invalid.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return preg_match($this->pattern, $value) === 1;
    }
}

/**
 * In array validation.
 */
class InRule extends Rule
{
    protected array $values;

    public function __construct(array|string ...$values)
    {
        // Handle both: new InRule(['a','b','c']) and new InRule('a','b','c')
        if (count($values) === 1 && is_array($values[0])) {
            $this->values = $values[0];
        } else {
            $this->values = $values;
        }
        $this->message = 'The selected value is invalid.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return in_array($value, $this->values, true);
    }
}

/**
 * URL validation.
 */
class UrlRule extends Rule
{
    public function __construct()
    {
        $this->message = 'The field must be a valid URL.';
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}

/**
 * Date format validation.
 */
class DateFormatRule extends Rule
{
    protected string $format;

    public function __construct(string $format)
    {
        $this->format = $format;
        $this->message = "The field must be a valid date in format {$format}.";
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $date = \DateTime::createFromFormat($this->format, $value);
        return $date && $date->format($this->format) === $value;
    }
}

/**
 * Confirmed field validation (e.g., password confirmation).
 */
class ConfirmedRule extends Rule
{
    protected string $field;

    public function __construct(string $field)
    {
        $this->field = $field;
        $this->message = "The {$this->field} confirmation does not match.";
    }

    public function validate(mixed $value, array $data): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $confirmedField = $this->field . '_confirmation';
        return isset($data[$confirmedField]) && $value === $data[$confirmedField];
    }
}
