<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use function function_exists;
use function ini_get;
use function ini_set;

use InvalidArgumentException;

use function is_array;
use function is_int;
use function is_string;
use function mb_check_encoding;
use function mb_detect_encoding;

use const PREG_BACKTRACK_LIMIT_ERROR;
use const PREG_BAD_UTF8_ERROR;
use const PREG_BAD_UTF8_OFFSET_ERROR;
use const PREG_INTERNAL_ERROR;
use const PREG_JIT_STACKLIMIT_ERROR;

use function preg_last_error;
use function preg_match;

use const PREG_NO_ERROR;
use const PREG_RECURSION_LIMIT_ERROR;

use RuntimeException;

use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function stripos;
use function strtolower;

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
     * Validate that a field starts with a given string
     *
     * Validates that a string starts with a specific prefix or one of multiple prefixes.
     * By default, performs case-sensitive matching. Set the second parameter to false for case-insensitive.
     *
     * Common use cases:
     * - URL protocol validation (https://, http://)
     * - Phone number country codes (+1, +44, +61)
     * - File path validation (/var/www/, /home/)
     * - SKU/Code prefixes (PROD-, DEV-, TEST-)
     * - Reference numbers (INV-, ORD-, CUST-)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string|array $prefix, [1] => bool $caseSensitive (default: true).
     *
     * @return bool True if value starts with the prefix, false otherwise.
     *
     * @example Basic usage (single prefix):
     * ```php
     * $v = new Validator(['url' => 'https://example.com']);
     * $v->rule('startsWith', 'url', 'https://'); // passes
     * ```
     * @example Multiple prefixes:
     * ```php
     * $v = new Validator(['phone' => '+44123456789']);
     * $v->rule('startsWith', 'phone', ['+1', '+44', '+61']); // passes
     * ```
     * @example Case-insensitive:
     * ```php
     * $v = new Validator(['code' => 'prod-12345']);
     * $v->rule('startsWith', 'code', 'PROD-', false); // passes (case-insensitive)
     * ```
     */
    protected function validateStartsWith(string $field, mixed $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (!isset($params[0])) {
            return false;
        }

        // Convert single prefix to array for uniform handling
        $prefixes = is_array($params[0]) ? $params[0] : [$params[0]];
        $caseSensitive = $params[1] ?? true;

        // Validate all prefixes are strings
        foreach ($prefixes as $prefix) {
            if (!is_string($prefix)) {
                continue;
            }

            // Case-sensitive check
            if ($caseSensitive) {
                if (str_starts_with($value, $prefix)) {
                    return true;
                }
            } else {
                // Case-insensitive check
                if (str_starts_with(strtolower($value), strtolower($prefix))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate that a field ends with a given string
     *
     * Validates that a string ends with a specific suffix or one of multiple suffixes.
     * By default, performs case-sensitive matching. Set the second parameter to false for case-insensitive.
     *
     * Common use cases:
     * - Domain validation (.com, .org, .net)
     * - File extension validation (.jpg, .png, .pdf)
     * - Email domain validation (@company.com)
     * - Formatted codes/IDs with suffixes
     * - URL path validation (/api, /admin)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string|array $suffix, [1] => bool $caseSensitive (default: true).
     *
     * @return bool True if value ends with the suffix, false otherwise.
     *
     * @example Basic usage (single suffix):
     * ```php
     * $v = new Validator(['email' => 'user@company.com']);
     * $v->rule('endsWith', 'email', '@company.com'); // passes
     * ```
     * @example Multiple suffixes:
     * ```php
     * $v = new Validator(['domain' => 'example.org']);
     * $v->rule('endsWith', 'domain', ['.com', '.org', '.net']); // passes
     * ```
     * @example Case-insensitive:
     * ```php
     * $v = new Validator(['file' => 'image.JPG']);
     * $v->rule('endsWith', 'file', '.jpg', false); // passes (case-insensitive)
     * ```
     */
    protected function validateEndsWith(string $field, mixed $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (!isset($params[0])) {
            return false;
        }

        // Convert single suffix to array for uniform handling
        $suffixes = is_array($params[0]) ? $params[0] : [$params[0]];
        $caseSensitive = $params[1] ?? true;

        // Validate all suffixes are strings
        foreach ($suffixes as $suffix) {
            if (!is_string($suffix)) {
                continue;
            }

            // Case-sensitive check
            if ($caseSensitive) {
                if (str_ends_with($value, $suffix)) {
                    return true;
                }
            } else {
                // Case-insensitive check
                if (str_ends_with(strtolower($value), strtolower($suffix))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate that a field is a valid UUID
     *
     * Validates that a string is a valid UUID (Universally Unique Identifier) format.
     * By default, validates against all UUID versions (1-5). Optionally validates a specific version.
     *
     * UUID Format: 8-4-4-4-12 hexadecimal digits (xxxxxxxx-xxxx-Mxxx-Nxxx-xxxxxxxxxxxx)
     * where M is the version digit (1-5) and N is the variant digit (8, 9, a, or b).
     *
     * Supported UUID versions:
     * - Version 1: Time-based UUID (uses timestamp and MAC address)
     * - Version 2: DCE Security UUID (rarely used)
     * - Version 3: Name-based UUID using MD5 hashing
     * - Version 4: Random UUID (most common, uses random numbers)
     * - Version 5: Name-based UUID using SHA-1 hashing
     *
     * Common use cases:
     * - API resource identifiers
     * - Database primary keys (especially in distributed systems)
     * - Session tokens
     * - Request tracking IDs
     * - Unique document identifiers
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => int|null $version (optional: specific UUID version 1-5).
     *
     * @return bool True if value is a valid UUID, false otherwise.
     *
     * @example Basic usage (any version):
     * ```php
     * $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
     * $v->rule('uuid', 'id'); // passes (valid UUIDv4)
     * ```
     * @example Specific version:
     * ```php
     * $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
     * $v->rule('uuid', 'id', 4); // passes (valid UUIDv4)
     *
     * $v = new Validator(['id' => '550e8400-e29b-11d4-a716-446655440000']);
     * $v->rule('uuid', 'id', 4); // fails (this is UUIDv1, not v4)
     * ```
     */
    protected function validateUuid(string $field, mixed $value, array $params = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Normalize to lowercase for comparison
        $value = strtolower($value);

        // Optional: specific version to validate
        $version = $params[0] ?? null;

        // Validate version parameter if provided
        if ($version !== null && (!is_int($version) || $version < 1 || $version > 5)) {
            return false;
        }

        // UUID regex pattern:
        // 8 hex digits - 4 hex - 4 hex - 4 hex - 12 hex
        // Version digit is at position 14 (M in the pattern)
        // Variant digit is at position 19 (N in the pattern, must be 8, 9, a, or b)

        if ($version !== null) {
            // Validate specific UUID version
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-' . $version . '[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
        } else {
            // Validate any UUID version (1-5)
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
        }

        return preg_match($pattern, $value) === 1;
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
