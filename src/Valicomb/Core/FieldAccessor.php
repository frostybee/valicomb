<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Core;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_shift;
use function is_array;

/**
 * Field Accessor
 *
 * Handles nested field access using dot notation and wildcard matching
 * for validating complex array structures.
 *
 * @package Valicomb\Core
 *
 * @internal
 */
class FieldAccessor
{
    /**
     * Get part of the data array (supports nested arrays with dot notation).
     *
     * Navigates nested array structures using an array of identifiers (from dot notation parsing).
     * Supports wildcard matching (*) for array iteration.
     *
     * @param mixed $data The data array to navigate.
     * @param array $identifiers Array of keys to navigate through (e.g., ['user', 'email']).
     * @param bool $allowEmpty Whether to check for key existence even if value is empty.
     *
     * @return array Tuple: [0] => mixed $value (the found value or null), [1] => bool $isMultiple (true if wildcard used).
     */
    public function getPart(mixed $data, array $identifiers, bool $allowEmpty = false): array
    {
        // Catches the case where the field is an array of discrete values
        if ($identifiers === []) {
            return [$data, false];
        }

        // Catches the case where the data isn't an array or object
        if (!is_array($data)) {
            return [null, false];
        }

        $identifier = array_shift($identifiers);

        // Glob match
        if ($identifier === '*') {
            $values = [];
            foreach ($data as $row) {
                [$value, $multiple] = $this->getPart($row, $identifiers, $allowEmpty);
                if ($multiple) {
                    $values = [...$values, ...$value];
                } else {
                    $values[] = $value;
                }
            }
            return [$values, true];
        }

        // Dead end, abort
        if ($identifier === null || !isset($data[$identifier])) {
            if ($allowEmpty) {
                // When empty values are allowed, we only care if the key exists
                return [null, array_key_exists($identifier, $data)];
            }
            return [null, false];
        }

        // Match array element
        if ($identifiers === []) {
            if ($allowEmpty) {
                // When empty values are allowed, we only care if the key exists
                return [null, array_key_exists($identifier, $data)];
            }
            return [$data[$identifier], $allowEmpty];
        }

        // We need to go deeper
        return $this->getPart($data[$identifier], $identifiers, $allowEmpty);
    }

    /**
     * Check if an array is associative.
     *
     * Determines if an array has at least one string key (associative array) or only integer keys (indexed array).
     *
     * @param array $input The array to check.
     *
     * @return bool True if array has at least one string key, false if all keys are integers.
     */
    public function isAssociativeArray(array $input): bool
    {
        // Array contains at least one key that's not an integer or can't be cast to an integer
        return array_filter(array_keys($input), 'is_string') !== [];
    }
}
