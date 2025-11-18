<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use InvalidArgumentException;

use function function_exists;
use function is_int;
use function is_string;
use function mb_strlen;
use function strlen;

/**
 * Length Validators Trait
 *
 * Contains all string length-related validation methods including:
 * - Length validation (exact and range)
 * - Min/Max length validation
 * - Between length validation
 * - Character counting helper with multibyte support
 *
 * @package Valicomb\Validators
 */
trait LengthValidatorsTrait
{
    /**
     * Validate the length of a string
     *
     * Validates string length constraints with support for both exact length matching
     * and range validation. Uses multibyte-safe character counting via stringLength() helper
     * if the mbstring extension is available.
     *
     * This method has dual behavior based on the number of parameters:
     * - One parameter: Validates exact length match
     * - Two parameters: Validates length is between min and max (inclusive)
     *
     * Important: This counts characters, not bytes. For multibyte encodings like UTF-8,
     * "café" has 4 characters but may be 5 bytes. Non-string values return false.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be a string).
     * @param array $params Length constraint(s): [0] => exact length OR min length, [1] => max length (optional).
     *
     * @return bool True if string length matches constraint(s), false otherwise or if value is not a string.
     */
    protected function validateLength(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_int($params[0])) {
            throw new InvalidArgumentException('Length parameter must be an integer');
        }

        if (isset($params[1]) && !is_int($params[1])) {
            throw new InvalidArgumentException('Maximum length parameter must be an integer');
        }

        $length = $this->stringLength($value);

        // Length between
        if (isset($params[1])) {
            return $length !== false && $length >= $params[0] && $length <= $params[1];
        }

        // Length equals
        return $length === $params[0];
    }

    /**
     * Validate the length of a string is between min and max
     *
     * Validates that a string's character length falls within a specified range (inclusive on both ends).
     * Uses multibyte-safe character counting via stringLength() helper.
     *
     * Both bounds are inclusive, meaning strings with length equal to either the minimum or maximum
     * will pass validation. For example, with params [3, 5]:
     * - "ab" (length 2) fails
     * - "abc" (length 3) passes
     * - "abcd" (length 4) passes
     * - "abcde" (length 5) passes
     * - "abcdef" (length 6) fails
     *
     * Important: Counts characters, not bytes. Non-string values return false.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be a string).
     * @param array $params Length bounds: [0] => minimum length, [1] => maximum length.
     *
     * @return bool True if string length is between min and max (inclusive), false otherwise or if value is not a string.
     */
    protected function validateLengthBetween(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_int($params[0]) || !isset($params[1]) || !is_int($params[1])) {
            throw new InvalidArgumentException('Minimum and maximum length parameters must be integers');
        }

        $length = $this->stringLength($value);

        return $length !== false && $length >= $params[0] && $length <= $params[1];
    }

    /**
     * Validate the length of a string (min)
     *
     * Validates that a string's character length meets or exceeds a minimum threshold.
     * Uses multibyte-safe character counting via stringLength() helper.
     *
     * The minimum bound is inclusive. For example, with param [5]:
     * - "test" (length 4) fails
     * - "tests" (length 5) passes
     * - "testing" (length 7) passes
     *
     * This is useful for password length requirements, ensuring adequate input length,
     * or preventing empty/too-short submissions.
     *
     * Important: Counts characters, not bytes. Non-string values return false.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be a string).
     * @param array $params Minimum length constraint: [0] => minimum character length.
     *
     * @return bool True if string length is at least the minimum, false otherwise or if value is not a string.
     */
    protected function validateLengthMin(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_int($params[0])) {
            throw new InvalidArgumentException('Minimum length parameter must be an integer');
        }

        $length = $this->stringLength($value);

        return $length !== false && $length >= $params[0];
    }

    /**
     * Validate the length of a string (max)
     *
     * Validates that a string's character length does not exceed a maximum threshold.
     * Uses multibyte-safe character counting via stringLength() helper.
     *
     * The maximum bound is inclusive. For example, with param [5]:
     * - "test" (length 4) passes
     * - "tests" (length 5) passes
     * - "testing" (length 7) fails
     *
     * This is useful for database field constraints, preventing buffer overflows,
     * or enforcing UI/UX limits on text input.
     *
     * Important: Counts characters, not bytes. Non-string values return false.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be a string).
     * @param array $params Maximum length constraint: [0] => maximum character length.
     *
     * @return bool True if string length does not exceed the maximum, false otherwise or if value is not a string.
     */
    protected function validateLengthMax(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_int($params[0])) {
            throw new InvalidArgumentException('Maximum length parameter must be an integer');
        }

        $length = $this->stringLength($value);

        return $length !== false && $length <= $params[0];
    }

    /**
     * Get the length of a string
     *
     * Returns the character count of a string, using multibyte-safe mb_strlen() if available,
     * otherwise falls back to strlen(). Returns false if the value is not a string.
     *
     * @param mixed $value The value to measure.
     *
     * @return int|false The character count, or false if not a string.
     */
    protected function stringLength(mixed $value): int|false
    {
        if (!is_string($value)) {
            return false;
        }

        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }
}
