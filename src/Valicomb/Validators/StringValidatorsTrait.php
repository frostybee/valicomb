<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use InvalidArgumentException;
use RuntimeException;

use function function_exists;
use function ini_get;
use function ini_set;
use function is_array;
use function is_string;
use function mb_check_encoding;
use function mb_detect_encoding;
use function preg_last_error;
use function preg_match;
use function str_contains;
use function stripos;

use const PREG_NO_ERROR;
use const PREG_INTERNAL_ERROR;
use const PREG_BACKTRACK_LIMIT_ERROR;
use const PREG_RECURSION_LIMIT_ERROR;
use const PREG_BAD_UTF8_ERROR;
use const PREG_BAD_UTF8_OFFSET_ERROR;
use const PREG_JIT_STACKLIMIT_ERROR;

/**
 * String Validators Trait
 *
 * Contains all string-related validation methods including:
 * - Alpha/AlphaNum validation with Unicode support
 * - ASCII validation
 * - Slug validation
 * - Substring contains validation
 * - Custom regex validation with ReDoS protection
 *
 * @package Valicomb\Validators
 */
trait StringValidatorsTrait
{
    /**
     * Validate that a field contains only alphabetic characters
     *
     * Validates that a string contains only letters with Unicode support for international characters.
     * If mbstring extension is available, validates against Unicode letter property (\p{L}).
     * Falls back to ASCII-only validation (a-zA-Z) if mbstring is not available.
     *
     * Supported: Letters from any language (Latin, Cyrillic, Chinese, Arabic, etc.)
     * Not supported: Numbers, spaces, punctuation, special characters
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value contains only alphabetic characters, false otherwise.
     */
    protected function validateAlpha(string $field, mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Support Unicode letters (includes international characters)
        if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
            return preg_match('/^[\p{L}]+$/u', $value) === 1;
        }

        // Fallback to ASCII only
        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }

    /**
     * Validate that a field contains only alpha-numeric characters
     *
     * Validates that a string contains only letters and numbers with Unicode support.
     * If mbstring extension is available, validates against Unicode letter (\p{L}) and number (\p{N}) properties.
     * Falls back to ASCII-only validation (a-zA-Z0-9) if mbstring is not available.
     *
     * Supported: Letters and numbers from any language
     * Not supported: Spaces, punctuation, special characters, dashes, underscores
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value contains only alpha-numeric characters, false otherwise.
     */
    protected function validateAlphaNum(string $field, mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Support Unicode letters and numbers
        if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
            return preg_match('/^[\p{L}\p{N}]+$/u', $value) === 1;
        }

        // Fallback
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }

    /**
     * Validate that a field contains only ASCII characters
     *
     * Validates that a string contains only ASCII characters (bytes 0x00-0x7F).
     * Useful for ensuring data compatibility with systems that don't support Unicode.
     * If mbstring extension is available, uses mb_detect_encoding for accurate detection.
     * Falls back to regex pattern matching if mbstring is not available.
     *
     * Valid: "Hello", "123", "test@example.com"
     * Invalid: "Héllo", "日本語", "Привет" (contains non-ASCII characters)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value contains only ASCII characters, false otherwise.
     */
    protected function validateAscii(string $field, mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Multibyte extension check
        if (function_exists('mb_detect_encoding')) {
            return mb_detect_encoding($value, 'ASCII', true) !== false;
        }

        // Fallback with regex
        return preg_match('/[^\x00-\x7F]/', $value) === 0;
    }

    /**
     * Validate that a field contains only alpha-numeric characters, dashes, and underscores
     *
     * Validates that a string is a valid "slug" format - commonly used for URLs, filenames, or identifiers.
     * Only accepts lowercase/uppercase letters (a-z, A-Z), numbers (0-9), hyphens (-), and underscores (_).
     * Case-insensitive validation.
     *
     * Valid examples: "hello-world", "my_slug_123", "product-name", "user_name"
     * Invalid examples: "hello world" (space), "slug!" (special char), "über-slug" (non-ASCII)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is a valid slug, false otherwise.
     */
    protected function validateSlug(string $field, mixed $value): bool
    {
        if (is_array($value) || !is_string($value)) {
            return false;
        }

        return preg_match('/^[a-z0-9_-]+$/i', $value) === 1;
    }

    /**
     * Validate that a field contains a given string
     *
     * Validates that a string contains a specific substring with optional case-insensitive mode.
     * By default, performs case-sensitive matching. Set second parameter to false for case-insensitive.
     *
     * Case-sensitive (default): "Hello World" contains "World" ✓, contains "world" ✗
     * Case-insensitive: "Hello World" contains "world" ✓
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string $needle (substring to find), [1] => bool $strict (default: true).
     *
     * @return bool True if value contains the substring, false otherwise.
     */
    protected function validateContains(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0])) {
            return false;
        }

        if (!is_string($params[0]) || !is_string($value)) {
            return false;
        }

        $strict = $params[1] ?? true;

        if ($strict) {
            return str_contains($value, $params[0]);
        }

        return stripos($value, $params[0]) !== false;
    }

    /**
     * Validate that a field passes a regular expression check
     *
     * Validates a value against a custom regular expression pattern with security protections:
     * - Validates the pattern itself before execution
     * - Sets backtrack/recursion limits to prevent ReDoS (Regular Expression Denial of Service)
     * - Restores original INI settings after execution
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string $pattern (regex pattern).
     *
     * @throws InvalidArgumentException If pattern is not provided or not a string
     * @throws RuntimeException If regex pattern is invalid or execution fails
     *
     * @return bool True if value matches the pattern, false otherwise.
     */
    protected function validateRegex(string $field, mixed $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (!isset($params[0]) || !is_string($params[0])) {
            throw new InvalidArgumentException('Regex pattern must be provided as string');
        }

        $pattern = $params[0];

        // Validate the regex pattern itself
        if (@preg_match($pattern, '') === false) {
            throw new RuntimeException(
                'Invalid regex pattern: ' . $this->getPcreErrorMessage(preg_last_error()),
            );
        }

        // Set limits to prevent ReDoS (lower limits for better security)
        $oldBacktrackLimit = ini_get('pcre.backtrack_limit');
        $oldRecursionLimit = ini_get('pcre.recursion_limit');

        ini_set('pcre.backtrack_limit', '10000');
        ini_set('pcre.recursion_limit', '10000');

        try {
            $result = @preg_match($pattern, $value);

            if ($result === false) {
                $error = preg_last_error();
                throw new RuntimeException(
                    'Regex execution failed: ' . $this->getPcreErrorMessage($error),
                );
            }

            return $result > 0;
        } finally {
            ini_set('pcre.backtrack_limit', (string)$oldBacktrackLimit);
            ini_set('pcre.recursion_limit', (string)$oldRecursionLimit);
        }
    }

    /**
     * Get human-readable PCRE error message
     *
     * Converts PCRE error codes to human-readable error messages using PHP 8.0+ match expression.
     * Used internally by validateRegex() to provide meaningful error messages when regex execution fails.
     *
     * @param int $error The PCRE error code from preg_last_error().
     *
     * @return string Human-readable error message describing the PCRE error.
     */
    private function getPcreErrorMessage(int $error): string
    {
        return match($error) {
            PREG_NO_ERROR => 'No error',
            PREG_INTERNAL_ERROR => 'Internal PCRE error',
            PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit exhausted',
            PREG_RECURSION_LIMIT_ERROR => 'Recursion limit exhausted',
            PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
            PREG_BAD_UTF8_OFFSET_ERROR => 'Bad UTF-8 offset',
            PREG_JIT_STACKLIMIT_ERROR => 'JIT stack limit exhausted',
            default => "Unknown PCRE error (code: $error)"
        };
    }
}
