<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use function bccomp;
use function count;
use function explode;

use const FILTER_VALIDATE_INT;

use function filter_var;
use function function_exists;
use function in_array;
use function is_array;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function number_format;
use function preg_match;
use function rtrim;
use function str_contains;
use function strlen;
use function strpos;
use function strtoupper;

/**
 * Numeric Validators Trait
 *
 * Contains all numeric-related validation methods including:
 * - Numeric/Integer validation
 * - Min/Max value validation with bcmath support
 * - Between range validation
 * - Boolean validation
 *
 * @package Valicomb\Validators
 */
trait NumericValidatorsTrait
{
    /**
     * Convert a numeric value to a bccomp-compatible string.
     *
     * bccomp() does NOT support scientific notation (e.g., "1.0E+10").
     * This method converts numeric values to plain decimal strings.
     *
     * @param mixed $value The numeric value to convert.
     *
     * @return string A plain decimal string representation.
     */
    private function toBcString(mixed $value): string
    {
        $str = (string) $value;

        // Check if the string contains scientific notation (e.g., "1.5E+10", "1e-5")
        if (str_contains(strtoupper($str), 'E')) {
            // Use number_format to convert to plain decimal
            // We use 14 decimal places to match bccomp precision
            // This handles both very large and very small numbers
            $floatVal = (float) $value;

            // Determine if we need decimal places
            if ($floatVal == (int) $floatVal && abs($floatVal) < PHP_INT_MAX) {
                // It's effectively an integer
                return number_format($floatVal, 0, '.', '');
            }

            // Format with enough precision, then trim trailing zeros
            $formatted = number_format($floatVal, 14, '.', '');
            return rtrim(rtrim($formatted, '0'), '.');
        }

        return $str;
    }

    /**
     * Validate that a field is numeric
     *
     * Validates that a value is numeric, accepting integers, floats, and numeric strings.
     * Uses PHP's is_numeric() function which accepts formats like: "123", "123.45", "-123", "1.23e4".
     *
     * This is more permissive than validateInteger() as it accepts decimal values.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is numeric, false otherwise.
     */
    protected function validateNumeric(string $field, mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Validate that a field is an integer
     *
     * Validates integer values with optional strict mode. In strict mode, rejects strings with
     * leading zeros (except "0" itself) to prevent octal interpretation issues.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => bool $strict (default: false).
     *
     * @return bool True if value is a valid integer, false otherwise.
     */
    protected function validateInteger(string $field, mixed $value, array $params): bool
    {
        $strict = isset($params[0]) && (bool)$params[0];

        if ($strict) {
            // Strict mode: reject strings with leading zeros (except "0" itself)
            // but accept native integers
            if (is_int($value)) {
                return true;
            }

            if (!is_string($value)) {
                return false;
            }

            // Fixed regex: matches 0, or optional negative sign followed by 1-9 then any digits
            return preg_match('/^(0|-?[1-9]\d*)$/', $value) === 1;
        }

        // Non-strict: also accept actual integers and numeric strings
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate the size of a field is greater than a minimum value
     *
     * Validates that a numeric value is greater than or equal to a specified minimum threshold.
     * Uses high-precision decimal comparison via bccomp() when available (from bcmath extension),
     * otherwise falls back to standard PHP comparison operators.
     *
     * The minimum bound is inclusive, meaning a value equal to the minimum passes validation.
     * For example, with param [5]:
     * - 4.99 fails
     * - 5 passes
     * - 5.01 passes
     *
     * The bccomp() function provides 14 decimal places of precision, making this suitable for
     * financial calculations, scientific data, or any scenario requiring precise decimal handling.
     * Non-numeric values (strings, arrays, objects) are rejected.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be numeric).
     * @param array $params Minimum threshold: [0] => minimum numeric value.
     *
     * @return bool True if value is numeric and >= minimum, false otherwise.
     */
    protected function validateMin(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (function_exists('bccomp')) {
            // Use toBcString to handle scientific notation (e.g., "1.0E+10")
            return bccomp($this->toBcString($params[0]), $this->toBcString($value), 14) !== 1;
        }

        return $params[0] <= $value;
    }

    /**
     * Validate the size of a field is less than a maximum value
     *
     * Validates that a numeric value is less than or equal to a specified maximum threshold.
     * Uses high-precision decimal comparison via bccomp() when available (from bcmath extension),
     * otherwise falls back to standard PHP comparison operators.
     *
     * The maximum bound is inclusive, meaning a value equal to the maximum passes validation.
     * For example, with param [10]:
     * - 9.99 passes
     * - 10 passes
     * - 10.01 fails
     *
     * The bccomp() function provides 14 decimal places of precision, making this suitable for
     * financial calculations, scientific data, or any scenario requiring precise decimal handling.
     * Non-numeric values (strings, arrays, objects) are rejected.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be numeric).
     * @param array $params Maximum threshold: [0] => maximum numeric value.
     *
     * @return bool True if value is numeric and <= maximum, false otherwise.
     */
    protected function validateMax(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (function_exists('bccomp')) {
            // Use toBcString to handle scientific notation (e.g., "1.0E+10")
            return bccomp($this->toBcString($value), $this->toBcString($params[0]), 14) !== 1;
        }

        return $params[0] >= $value;
    }

    /**
     * Validate the size of a field is between min and max values
     *
     * Validates that a numeric value falls within a specified range (inclusive on both ends).
     * Internally delegates to validateMin() and validateMax(), inheriting their high-precision
     * decimal comparison capabilities via bccomp() when available.
     *
     * Both bounds are inclusive, meaning values equal to either the minimum or maximum pass
     * validation. For example, with param [[5, 10]]:
     * - 4.99 fails
     * - 5 passes
     * - 7.5 passes
     * - 10 passes
     * - 10.01 fails
     *
     * Important: This method has a unique parameter structure - the first parameter must be
     * an array containing exactly two elements: [min, max]. Invalid parameter structures
     * (missing array, wrong element count) will cause validation to fail.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be numeric).
     * @param array $params Range constraint: [0] => [minimum, maximum] (must be 2-element array).
     *
     * @return bool True if value is numeric and between min/max (inclusive), false otherwise or if params invalid.
     */
    protected function validateBetween(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (!isset($params[0]) || !is_array($params[0]) || count($params[0]) !== 2) {
            return false;
        }

        [$min, $max] = $params[0];

        return $this->validateMin($field, $value, [$min]) && $this->validateMax($field, $value, [$max]);
    }

    /**
     * Validate that a field contains a boolean
     *
     * Validates that a value represents a boolean using strict type checking.
     * Only accepts actual booleans, integers 1/0, and string representations '1'/'0'.
     * This is stricter than PHP's native boolean casting to prevent unexpected type coercion.
     *
     * Accepted values: true, false, 1, 0, '1', '0'
     * Rejected values: 'true', 'false', 'yes', 'no', 2, -1, etc.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is a valid boolean representation, false otherwise.
     */
    protected function validateBoolean(string $field, mixed $value): bool
    {
        // Only accept actual booleans, integers 1/0, and strings '1'/'0'
        return in_array($value, [true, false, 1, 0, '1', '0'], true);
    }

    /**
     * Validate that a field is a positive number
     *
     * Validates that a numeric value is strictly greater than 0.
     * Uses high-precision decimal comparison via bccomp() when available (from bcmath extension),
     * otherwise falls back to standard PHP comparison operators.
     *
     * This validation is exclusive of zero, meaning:
     * - Values > 0 pass validation
     * - Zero (0) fails validation
     * - Negative values fail validation
     * - Non-numeric values fail validation
     *
     * Common use cases:
     * - Quantities that must be positive (inventory, purchases)
     * - Prices and monetary amounts
     * - Ages
     * - Counts that cannot be zero
     *
     * The bccomp() function provides 14 decimal places of precision, making this suitable for
     * financial calculations, scientific data, or any scenario requiring precise decimal handling.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be numeric).
     *
     * @return bool True if value is numeric and > 0, false otherwise.
     *
     * @example Basic usage:
     * ```php
     * $v = new Validator(['quantity' => 5]);
     * $v->rule('positive', 'quantity'); // passes
     *
     * $v = new Validator(['price' => 0]);
     * $v->rule('positive', 'price'); // fails
     *
     * $v = new Validator(['amount' => -10]);
     * $v->rule('positive', 'amount'); // fails
     * ```
     */
    protected function validatePositive(string $field, mixed $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (function_exists('bccomp')) {
            // Use toBcString to handle scientific notation (e.g., "1.0E+10")
            return bccomp($this->toBcString($value), '0', 14) === 1;
        }

        return $value > 0;
    }

    /**
     * Validate that a field has a maximum number of decimal places
     *
     * Validates that a numeric value does not exceed a specified number of decimal places.
     * This is particularly useful for financial calculations, currency formatting, percentages,
     * and scientific measurements where decimal precision matters.
     *
     * The validation counts decimal places by converting the value to a string representation
     * and examining characters after the decimal point. Trailing zeros are considered significant
     * (e.g., "10.00" has 2 decimal places, not 0).
     *
     * Integer values (numbers without a decimal point) are considered to have 0 decimal places
     * and will always pass validation.
     *
     * For example, with param [2]:
     * - 10 passes (0 decimal places)
     * - 10.5 passes (1 decimal place)
     * - 10.99 passes (2 decimal places)
     * - 10.999 fails (3 decimal places)
     * - "5.00" passes (2 decimal places with trailing zeros)
     *
     * Common use cases:
     * - Currency validation (typically 2 decimal places: $19.99)
     * - Percentage values (variable precision: 3.14%, 99.9%, 0.125%)
     * - Scientific measurements (specific precision requirements)
     * - Tax calculations (often 2-4 decimal places)
     * - Coordinate systems (latitude/longitude with controlled precision)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be numeric).
     * @param array $params Maximum decimal places: [0] => int $maxPlaces.
     *
     * @return bool True if value has <= maxPlaces decimal places, false otherwise.
     *
     * @example Basic usage:
     * ```php
     * // Currency validation (2 decimal places max)
     * $v = new Validator(['price' => 19.99]);
     * $v->rule('decimalPlaces', 'price', 2); // passes
     *
     * $v = new Validator(['price' => 19.999]);
     * $v->rule('decimalPlaces', 'price', 2); // fails
     *
     * // Integer values are always valid
     * $v = new Validator(['quantity' => 100]);
     * $v->rule('decimalPlaces', 'quantity', 2); // passes
     *
     * // Percentage with up to 4 decimal places
     * $v = new Validator(['percentage' => 3.1416]);
     * $v->rule('decimalPlaces', 'percentage', 4); // passes
     *
     * // Scientific notation is converted to decimal
     * $v = new Validator(['value' => 1.5e2]); // 150.0
     * $v->rule('decimalPlaces', 'value', 1); // passes
     * ```
     */
    protected function validateDecimalPlaces(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (!isset($params[0]) || !is_int($params[0]) || $params[0] < 0) {
            return false;
        }

        $maxPlaces = (int)$params[0];

        // Convert to string to avoid float precision issues
        // This also handles scientific notation (e.g., 1.5e2 becomes "150")
        $stringValue = (string)$value;

        // Check if the value contains a decimal point
        if (strpos($stringValue, '.') === false) {
            // No decimal point means 0 decimal places (integer)
            return true;
        }

        // Split at the decimal point and count characters after it
        // Trailing zeros ARE significant (e.g., "10.00" has 2 decimal places)
        $parts = explode('.', $stringValue);
        $decimalPart = $parts[1];

        $decimalPlaces = strlen($decimalPart);

        return $decimalPlaces <= $maxPlaces;
    }
}
