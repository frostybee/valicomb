<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use InvalidArgumentException;

use function explode;
use function in_array;
use function is_null;
use function is_string;
use function trim;

/**
 * Comparison Validators Trait
 *
 * Contains all comparison and equality validation methods including:
 * - Required field validation
 * - Equality comparison validation
 * - Difference validation
 * - Accepted (checkbox/agreement) validation
 *
 * @package Valicomb\Validators
 */
trait ComparisonValidatorsTrait
{
    /**
     * Required field validator
     *
     * Validates that a field is present and not empty. A field is considered empty if it is:
     * - null
     * - An empty string
     * - A string containing only whitespace
     *
     * Optional first parameter can enable strict key existence check (field must exist in data).
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Optional parameters: [0] => bool $checkKeyExists (default: false).
     *
     * @return bool True if field has a value, false if empty or missing.
     */
    protected function validateRequired(string $field, mixed $value, array $params = []): bool
    {
        if (isset($params[0]) && (bool)$params[0]) {
            $find = $this->fieldAccessor->getPart($this->fields, explode('.', $field), true);
            return $find[1];
        }
        return !is_null($value) && !(is_string($value) && trim($value) === '');
    }

    /**
     * Validate that two values match
     *
     * Compares the value of one field with another field using strict comparison (===).
     * This prevents type juggling attacks and ensures both value and type match.
     * Supports nested fields using dot notation.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string $fieldToCompare (field name to compare against).
     *
     * @return bool True if values match exactly (value and type), false otherwise.
     */
    protected function validateEquals(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_string($params[0])) {
            throw new InvalidArgumentException('Field name required for equals validation');
        }

        // Extract the second field value, this accounts for nested array values
        [$field2Value, $multiple] = $this->fieldAccessor->getPart($this->fields, explode('.', $params[0]));

        // Use strict comparison to prevent type juggling attacks
        return isset($field2Value) && $value === $field2Value;
    }

    /**
     * Validate that a field is different from another field
     *
     * Ensures two fields have different values using strict comparison (!==).
     * Supports nested fields using dot notation.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string $fieldToCompare.
     *
     * @return bool True if values are different, false if they match.
     */
    protected function validateDifferent(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_string($params[0])) {
            throw new InvalidArgumentException('Field name required for different validation');
        }

        // Extract the second field value, this accounts for nested array values
        [$field2Value, $multiple] = $this->fieldAccessor->getPart($this->fields, explode('.', $params[0]));

        // Use strict comparison to prevent type juggling attacks
        return isset($field2Value) && $value !== $field2Value;
    }

    /**
     * Validate that a field was "accepted"
     *
     * Validates that a field value represents user acceptance (e.g., checkbox, terms agreement).
     * This validation rule implies the field is "required".
     * Acceptable values: 'yes', 'on', 1, '1', true
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value represents acceptance, false otherwise.
     */
    protected function validateAccepted(string $field, mixed $value): bool
    {
        $acceptable = ['yes', 'on', 1, '1', true];

        return $this->validateRequired($field, $value) && in_array($value, $acceptable, true);
    }
}
