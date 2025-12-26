<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Core;

use function array_keys;
use function count;

use DateTime;

use function implode;
use function is_array;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_match_all;
use function str_replace;
use function ucwords;
use function vsprintf;

/**
 * Error Manager
 *
 * Handles validation error collection, formatting, and message generation
 * with support for field labels and multilingual messages.
 *
 * @package Valicomb\Core
 *
 * @internal
 */
class ErrorManager
{
    /**
     * Validation errors grouped by field name.
     */
    private array $errors = [];

    /**
     * Field labels for error messages.
     */
    private array $labels = [];

    /**
     * Whether to prepend field labels to error messages.
     */
    private bool $prependLabels = true;

    /**
     * Set whether to prepend field labels to error messages.
     *
     * @param bool $prepend True to prepend labels to error messages, false to omit them.
     */
    public function setPrependLabels(bool $prepend = true): void
    {
        $this->prependLabels = $prepend;
    }

    /**
     * Get array of error messages.
     *
     * @param string|null $field Optional field name to get errors for a specific field.
     *
     * @return array|false Array of error messages, or false if field not found/no errors.
     */
    public function getErrors(?string $field = null): array|false
    {
        if ($field !== null) {
            return $this->errors[$field] ?? false;
        }

        return $this->errors;
    }

    /**
     * Check if there are any errors.
     *
     * @return bool True if no errors, false if there are errors.
     */
    public function hasNoErrors(): bool
    {
        return $this->errors === [];
    }

    /**
     * Clear all errors.
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Add an error to error messages array.
     *
     * @param string $field The field name to add the error to.
     * @param string $message The error message (supports sprintf placeholders).
     * @param array $params Optional parameters for sprintf placeholder replacement.
     */
    public function addError(string $field, string $message, array $params = []): void
    {
        $message = $this->checkAndSetLabel($field, $message, $params);

        $values = [];
        // Printed values need to be in string format
        foreach ($params as $param) {
            if (is_array($param)) {
                $param = "['" . implode("', '", $param) . "']";
            } elseif ($param instanceof DateTime) {
                $param = $param->format('Y-m-d');
            } elseif (is_object($param)) {
                $param = $param::class;
                // Add leading backslash for fully qualified class names
                if ($param[0] !== '\\') {
                    $param = '\\' . $param;
                }
            }

            // Use custom label instead of field name if set
            if (is_string($params[0] ?? null) && isset($this->labels[$param])) {
                $param = $this->labels[$param];
            }

            $values[] = $param;
        }

        $this->errors[$field][] = $this->safeVsprintf($message, $values);
    }

    /**
     * Safely format a string with vsprintf, handling potential format string issues.
     *
     * @param string $format The format string.
     * @param array $values The values to substitute.
     *
     * @return string The formatted string, or the original format if vsprintf fails.
     */
    private function safeVsprintf(string $format, array $values): string
    {
        // Count format specifiers in the message (e.g., %s, %d, %1$s)
        // This prevents issues with mismatched placeholder counts
        $specifierCount = preg_match_all('/%(?:\d+\$)?[-+]?(?:\d+)?(?:\.\d+)?[sdfFeEgGoxXbcuU%]/', $format);

        // If no values provided, or format has no specifiers, return as-is
        if ($values === [] || $specifierCount === 0) {
            return $format;
        }

        // Ensure we have the right number of values
        // Pad with empty strings if fewer values than specifiers
        while (count($values) < $specifierCount) {
            $values[] = '';
        }

        // Use error suppression and fallback for safety
        $result = @vsprintf($format, $values);

        // If vsprintf fails (returns false), return the original format
        return $result !== false ? $result : $format;
    }

    /**
     * Set a label for a field.
     *
     * @param string $field The field name.
     * @param string $label The human-readable label.
     */
    public function setLabel(string $field, string $label): void
    {
        $this->labels[$field] = $label;
    }

    /**
     * Set multiple labels at once.
     *
     * @param array $labels Associative array where keys are field names and values are labels.
     */
    public function setLabels(array $labels): void
    {
        $this->labels = [...$this->labels, ...$labels];
    }

    /**
     * Get all labels.
     *
     * @return array The labels array.
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Clear all labels.
     */
    public function clearLabels(): void
    {
        $this->labels = [];
    }

    /**
     * Check and replace field label in message.
     *
     * Processes error messages by replacing placeholder tokens ({field}, {field1}, {field2}, etc.)
     * with actual field labels or auto-generated labels from field names.
     *
     * @param string $field The field name being validated.
     * @param string $message The error message template with placeholders.
     * @param array $params Parameters passed to the validation rule.
     *
     * @return string The processed error message with labels substituted.
     */
    private function checkAndSetLabel(string $field, string $message, array $params): string
    {
        if (isset($this->labels[$field])) {
            $message = str_replace('{field}', $this->labels[$field], $message);

            $i = 1;
            foreach (array_keys($params) as $k) {
                $tag = '{field' . $i . '}';
                $label = isset($params[$k]) && (is_numeric($params[$k]) || is_string($params[$k])) && isset($this->labels[$params[$k]])
                    ? $this->labels[$params[$k]]
                    : $tag;
                $message = str_replace($tag, $label, $message);
                $i++;
            }
        } else {
            $message = $this->prependLabels
                ? str_replace('{field}', ucwords(str_replace('_', ' ', $field)), $message)
                : str_replace('{field} ', '', $message);
        }

        return $message;
    }
}
