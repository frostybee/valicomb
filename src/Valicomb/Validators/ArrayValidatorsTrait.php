<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_unique;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function is_null;
use function is_scalar;
use function is_string;

use const SORT_REGULAR;

/**
 * Array Validators Trait
 *
 * Contains all array-related validation methods including:
 * - Array type validation
 * - In/Not In validation
 * - List contains validation
 * - Subset validation
 * - Unique values validation
 * - Array keys validation
 *
 * @package Valicomb\Validators
 */
trait ArrayValidatorsTrait
{
    /**
     * Validate that a field is an array
     *
     * Checks if the value is of array type. This is useful for validating
     * structured data, lists, or nested objects that should be arrays.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is an array, false otherwise.
     */
    protected function validateArray(string $field, mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Validate a field is contained within a list of values
     *
     * Validates that a value exists in a specified list of allowed values. Supports both
     * indexed and associative arrays, with options for strict type comparison.
     *
     * When validating against associative arrays (or forced with param [2] = true), only
     * the array keys are checked against, not the values. This is useful for validating
     * enum-like structures where keys represent valid options.
     *
     * Strict mode (param [1] = true): Uses === comparison, requiring exact type match
     * Non-strict mode (default): Uses == comparison, allowing type coercion (e.g., "1" == 1)
     *
     * Examples:
     * - validateIn('status', 'active', [['active', 'pending'], false]) → true
     * - validateIn('status', 'deleted', [['active', 'pending'], false]) → false
     * - validateIn('id', '5', [[1, 2, 5], true]) → false (strict: string !== int)
     * - validateIn('id', '5', [[1, 2, 5], false]) → true (non-strict: "5" == 5)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => array of allowed values, [1] => bool strict (default: false), [2] => bool forceAsAssociative (default: false).
     *
     * @return bool True if value exists in the allowed list, false otherwise.
     */
    protected function validateIn(string $field, mixed $value, array $params): bool
    {
        $forceAsAssociative = false;
        if (isset($params[2])) {
            $forceAsAssociative = (bool) $params[2];
        }

        if ($forceAsAssociative || $this->fieldAccessor->isAssociativeArray($params[0])) {
            $params[0] = array_keys($params[0]);
        }

        $strict = $params[1] ?? false;

        return in_array($value, $params[0], $strict);
    }

    /**
     * Validate a list contains a value
     *
     * Validates that an array field contains a specific value. This is the inverse of validateIn() -
     * instead of checking if a value is in a predefined list, it checks if a predefined value
     * exists in the field's array.
     *
     * When the field value is an associative array (or forced with param [2] = true), only
     * the array keys are checked, not the values. This allows validating that specific keys
     * exist in a submitted data structure.
     *
     * Strict mode (param [1] = true): Uses === comparison, requiring exact type match
     * Non-strict mode (default): Uses == comparison, allowing type coercion
     *
     * Examples:
     * - validateListContains('tags', ['php', 'mysql'], ['php', false]) → true
     * - validateListContains('tags', ['javascript', 'python'], ['php', false]) → false
     * - validateListContains('ids', [1, 2, 3], ['3', true]) → false (strict: int !== string)
     * - validateListContains('ids', [1, 2, 3], ['3', false]) → true (non-strict: 3 == "3")
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be an array).
     * @param array $params Parameters: [0] => value to search for, [1] => bool strict (default: false), [2] => bool forceAsAssociative (default: false).
     *
     * @return bool True if the list contains the specified value, false otherwise.
     */
    protected function validateListContains(string $field, mixed $value, array $params): bool
    {
        $forceAsAssociative = false;
        if (isset($params[2])) {
            $forceAsAssociative = (bool) $params[2];
        }

        if ($forceAsAssociative || $this->fieldAccessor->isAssociativeArray($value)) {
            $value = array_keys($value);
        }

        $strict = $params[1] ?? false;

        return in_array($params[0], $value, $strict);
    }

    /**
     * Validate a field is not contained within a list of values
     *
     * Validates that a value does NOT exist in a specified list of disallowed values.
     * This is the inverse of validateIn(), delegating to it and negating the result.
     *
     * Inherits all behavior from validateIn() including:
     * - Associative array handling (checks keys only)
     * - Strict/non-strict comparison modes
     * - Force associative parameter
     *
     * This is useful for blacklisting values, preventing reserved keywords, or excluding
     * specific inputs that should not be accepted.
     *
     * Examples:
     * - validateNotIn('username', 'guest', [['admin', 'root', 'guest'], false]) → false
     * - validateNotIn('username', 'john', [['admin', 'root', 'guest'], false]) → true
     * - validateNotIn('id', 0, [[0, -1], true]) → false (strict match)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => array of disallowed values, [1] => bool strict (default: false), [2] => bool forceAsAssociative (default: false).
     *
     * @return bool True if value does NOT exist in the disallowed list, false if it does.
     */
    protected function validateNotIn(string $field, mixed $value, array $params): bool
    {
        return !$this->validateIn($field, $value, $params);
    }

    /**
     * Validate that all field values are contained in a given array
     *
     * Validates that all elements in the field's array are contained within a list of allowed values,
     * or that a scalar value exists in the allowed list. This ensures submitted data only contains
     * whitelisted values.
     *
     * Behavior varies based on the field value type:
     * - Scalar or null: Validates the value exists in the allowed list (non-strict comparison)
     * - Array: Validates ALL elements exist in the allowed list using array_diff()
     * - Other types (objects, resources): Returns false
     *
     * Parameter handling is flexible:
     * - If params[0] is an array, it's used as the allowed values list
     * - Otherwise, the entire params array is treated as the allowed values list
     *
     * Examples:
     * - validateSubset('tags', ['php', 'mysql'], [['php', 'mysql', 'js']]) → true
     * - validateSubset('tags', ['php', 'rust'], [['php', 'mysql', 'js']]) → false ('rust' not allowed)
     * - validateSubset('color', 'red', [['red', 'blue', 'green']]) → true
     * - validateSubset('color', 'purple', [['red', 'blue', 'green']]) → false
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (scalar, null, or array).
     * @param array $params Allowed values: [0] => array of allowed values, OR entire params is the allowed list.
     *
     * @return bool True if all values are in the allowed list, false otherwise.
     */
    protected function validateSubset(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0])) {
            return false;
        }

        // Handle scalar parameter - wrap in array
        $allowedValues = is_array($params[0]) ? $params[0] : $params;

        if (is_scalar($value) || is_null($value)) {
            // For scalar values, check if it's in the allowed list
            return in_array($value, $allowedValues, false);
        }

        if (!is_array($value)) {
            return false;
        }

        // Check if all elements in $value exist in $allowedValues
        return array_diff($value, $allowedValues) === [];
    }

    /**
     * Validate that field array has only unique values
     *
     * Validates that an array contains no duplicate values by comparing the original array
     * with its deduplicated version via array_unique(). Uses SORT_REGULAR flag for comparison,
     * which performs standard comparisons (similar to == operator).
     *
     * This validation is useful for ensuring data integrity in scenarios like:
     * - Preventing duplicate tags, categories, or options
     * - Validating unique identifiers in a list
     * - Ensuring one-to-many relationships don't have duplicates
     *
     * Comparison behavior with SORT_REGULAR:
     * - Numbers: 1 == "1" (type coercion applies)
     * - Strings: Case-sensitive comparison
     * - Arrays/Objects: Compared by reference and structure
     *
     * Examples:
     * - validateContainsUnique('tags', ['php', 'mysql', 'javascript']) → true
     * - validateContainsUnique('tags', ['php', 'mysql', 'php']) → false (duplicate 'php')
     * - validateContainsUnique('ids', [1, 2, 3]) → true
     * - validateContainsUnique('ids', [1, 2, 1]) → false (duplicate 1)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be an array).
     *
     * @return bool True if array contains only unique values, false otherwise or if not an array.
     */
    protected function validateContainsUnique(string $field, mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Compare counts instead of arrays to avoid key ordering issues
        return count($value) === count(array_unique($value, SORT_REGULAR));
    }

    /**
     * Validate array has specified keys
     *
     * Validates that an array contains all required keys specified in the parameters.
     * Uses array_key_exists() to check for key presence, which returns true even if
     * the key's value is null.
     *
     * This validation is useful for:
     * - Ensuring API request payloads have required fields
     * - Validating configuration arrays have necessary keys
     * - Checking structured data integrity
     * - Enforcing data contracts for associative arrays
     *
     * Validation checks:
     * 1. Value must be an array
     * 2. params[0] must be provided and be a non-empty array
     * 3. Each required key name must be a string or integer
     * 4. All specified keys must exist in the value array
     *
     * Important: This checks for KEY existence, not value presence:
     * - ['name' => null] HAS key 'name' (passes validation)
     * - ['name' => ''] HAS key 'name' (passes validation)
     * - [] does NOT have key 'name' (fails validation)
     *
     * To validate both key existence AND non-empty values, combine with other rules.
     *
     * Examples:
     * - validateArrayHasKeys('data', ['name' => 'John'], [['name']]) → true
     * - validateArrayHasKeys('data', ['name' => null], [['name']]) → true (key exists)
     * - validateArrayHasKeys('data', ['email' => 'a@b.c'], [['name']]) → false (missing key)
     * - validateArrayHasKeys('data', ['a' => 1, 'b' => 2], [['a', 'b', 'c']]) → false (missing 'c')
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be an array).
     * @param array $params Required keys: [0] => array of key names (strings or integers).
     *
     * @return bool True if value is an array containing all required keys, false otherwise.
     */
    protected function validateArrayHasKeys(string $field, mixed $value, array $params): bool
    {
        if (!is_array($value) || !isset($params[0])) {
            return false;
        }

        $requiredFields = $params[0];
        if (!is_array($requiredFields) || $requiredFields === []) {
            return false;
        }

        foreach ($requiredFields as $fieldName) {
            // Only check valid array keys (string or int)
            if (!is_string($fieldName) && !is_int($fieldName)) {
                return false;
            }

            if (!array_key_exists($fieldName, $value)) {
                return false;
            }
        }

        return true;
    }
}
