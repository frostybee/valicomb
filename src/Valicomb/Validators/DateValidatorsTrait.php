<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

use function count;
use function date_parse_from_format;
use function explode;
use function is_string;
use function preg_match;
use function strtotime;

/**
 * Date Validators Trait
 *
 * Contains all date/time-related validation methods including:
 * - Date format validation
 * - Date range validation (before/after)
 * - Multiple format support with fallback
 *
 * @package Valicomb\Validators
 */
trait DateValidatorsTrait
{
    /**
     * Validate that a field is a valid date
     *
     * Validates that a value represents a valid date, accepting DateTimeInterface objects
     * or strings in common date formats. Uses a two-phase validation approach for reliability.
     *
     * Phase 1 - Explicit format matching (strict):
     * Tries common formats using DateTimeImmutable::createFromFormat() with exact matching:
     * - Y-m-d (2024-12-31)
     * - Y-m-d H:i:s (2024-12-31 23:59:59)
     * - d/m/Y (31/12/2024)
     * - m/d/Y (12/31/2024)
     * - Y/m/d (2024/12/31)
     * - Y-m-d\TH:i:sP (ISO 8601: 2024-12-31T23:59:59+00:00)
     *
     * Phase 2 - strtotime() fallback (permissive but filtered):
     * If no explicit format matches, falls back to strtotime() with restrictions:
     * - Rejects relative dates (next, last, ago, tomorrow, yesterday)
     * - Rejects timestamps <= 0 (before Unix epoch)
     *
     * Security note: Relative date rejection prevents potential security issues where
     * user input like "next week" could be accepted but produce unexpected results.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (DateTimeInterface or string).
     *
     * @return bool True if value is a valid date, false otherwise.
     */
    protected function validateDate(string $field, mixed $value): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        // Try common formats explicitly first
        $formats = [
            'Y-m-d',
            'Y-m-d H:i:s',
            'd/m/Y',
            'm/d/Y',
            'Y/m/d',
            'Y-m-d\TH:i:sP', // ISO 8601
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return true;
            }
        }

        // Fallback to strtotime with restrictions
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return false;
        }

        // Reject relative dates
        if (preg_match('/\b(next|last|ago|tomorrow|yesterday)\b/i', $value)) {
            return false;
        }

        return $timestamp > 0;
    }

    /**
     * Validate that a field matches a date format
     *
     * Validates that a string matches a specific date/time format using PHP's date format syntax.
     * Uses date_parse_from_format() to parse the value and checks for any errors or warnings.
     *
     * This provides strict format validation - the value must exactly match the specified format.
     * Unlike validateDate() which accepts multiple common formats, this method enforces a
     * single specific format, useful for standardizing date input.
     *
     * PHP date format syntax (common tokens):
     * - Y: 4-digit year (2024)
     * - m: 2-digit month with leading zero (01-12)
     * - d: 2-digit day with leading zero (01-31)
     * - H: 24-hour format with leading zero (00-23)
     * - i: Minutes with leading zero (00-59)
     * - s: Seconds with leading zero (00-59)
     *
     * Examples:
     * - validateDateFormat('date', '2024-12-31', ['Y-m-d']) → true
     * - validateDateFormat('date', '12/31/2024', ['Y-m-d']) → false (wrong format)
     * - validateDateFormat('time', '14:30:00', ['H:i:s']) → true
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be a string).
     * @param array $params Format specification: [0] => string format (PHP date format syntax).
     *
     * @return bool True if value matches the specified format exactly with no parsing errors, false otherwise.
     */
    protected function validateDateFormat(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_string($params[0])) {
            throw new InvalidArgumentException('Date format parameter must be a string');
        }

        if (!is_string($value)) {
            return false;
        }

        $parsed = date_parse_from_format($params[0], $value);

        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }

    /**
     * Validate the date is before a given date
     *
     * Validates that a date value is chronologically before a specified comparison date.
     * Accepts both DateTime objects and string representations of dates.
     *
     * Comparison is performed using Unix timestamps for accuracy across different formats.
     * For DateTime objects, uses getTimestamp(). For strings, uses strtotime() to parse.
     *
     * The comparison is strictly less-than (<), meaning:
     * - Equal dates will FAIL validation
     * - Only earlier dates will PASS validation
     *
     * Use cases:
     * - Birth date must be before today
     * - Event start date must be before end date
     * - Expiration date validation
     * - Historical data constraints
     *
     * Examples:
     * - validateDateBefore('start', '2024-01-01', ['2024-12-31']) → true
     * - validateDateBefore('start', '2024-12-31', ['2024-01-01']) → false
     * - validateDateBefore('date', '2024-06-15', ['2024-06-15']) → false (equal dates fail)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (DateTime or string).
     * @param array $params Comparison date: [0] => DateTime object or string representation.
     *
     * @return bool True if value is before the comparison date, false otherwise or if parsing fails.
     */
    protected function validateDateBefore(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0])) {
            throw new InvalidArgumentException('Comparison date required for dateBefore validation');
        }

        $vtime = ($value instanceof DateTime) ? $value->getTimestamp() : strtotime((string)$value);
        $ptime = ($params[0] instanceof DateTime) ? $params[0]->getTimestamp() : strtotime((string)$params[0]);

        // If either strtotime() failed, return false
        if ($vtime === false || $ptime === false) {
            return false;
        }

        return $vtime < $ptime;
    }

    /**
     * Validate the date is after a given date
     *
     * Validates that a date value is chronologically after a specified comparison date.
     * Accepts both DateTime objects and string representations of dates.
     *
     * Comparison is performed using Unix timestamps for accuracy across different formats.
     * For DateTime objects, uses getTimestamp(). For strings, uses strtotime() to parse.
     *
     * The comparison is strictly greater-than (>), meaning:
     * - Equal dates will FAIL validation
     * - Only later dates will PASS validation
     *
     * Use cases:
     * - Event end date must be after start date
     * - Future date requirements (scheduled tasks, appointments)
     * - Warranty expiration (must be after purchase date)
     * - Age verification (birth date must be before certain cutoff)
     *
     * Examples:
     * - validateDateAfter('end', '2024-12-31', ['2024-01-01']) → true
     * - validateDateAfter('end', '2024-01-01', ['2024-12-31']) → false
     * - validateDateAfter('date', '2024-06-15', ['2024-06-15']) → false (equal dates fail)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (DateTime or string).
     * @param array $params Comparison date: [0] => DateTime object or string representation.
     *
     * @return bool True if value is after the comparison date, false otherwise or if parsing fails.
     */
    protected function validateDateAfter(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0])) {
            throw new InvalidArgumentException('Comparison date required for dateAfter validation');
        }

        $vtime = ($value instanceof DateTime) ? $value->getTimestamp() : strtotime((string)$value);
        $ptime = ($params[0] instanceof DateTime) ? $params[0]->getTimestamp() : strtotime((string)$params[0]);

        // If either strtotime() failed, return false
        if ($vtime === false || $ptime === false) {
            return false;
        }

        return $vtime > $ptime;
    }
}
