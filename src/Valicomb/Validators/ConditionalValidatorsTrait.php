<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use function count;
use function explode;
use function is_array;
use function is_null;
use function is_string;
use function trim;

/**
 * Conditional Validators Trait
 *
 * Contains all conditional validation methods including:
 * - Optional field validation
 * - Required with (field required if others present)
 * - Required without (field required if others absent)
 *
 * @package Valicomb\Validators
 */
trait ConditionalValidatorsTrait
{
    /**
     * Validate optional field
     *
     * A special validation rule that always returns true, effectively marking a field as optional.
     * This is a no-op validator used internally by the validation framework to indicate that
     * a field's presence is not required.
     *
     * This method exists to support the validation rule registration system, where rules can be
     * added to fields to control their validation behavior. When 'optional' is added as a rule,
     * it signals to the validator that the field should not fail validation if absent or empty.
     *
     * The actual "optional" logic is typically handled earlier in the validation flow (checking
     * if field exists before running validators), but this method provides the rule registration
     * point for explicitly marking fields as optional.
     *
     * Usage pattern in validation rules:
     * - By default, fields with rules are considered required
     * - Adding 'optional' rule changes this behavior
     * - If field is missing/empty, other rules are skipped
     * - If field is present, other rules are evaluated normally
     *
     * Note: This always returns true regardless of value, as the actual optional behavior
     * is implemented in the validation flow logic, not in this specific method.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (ignored).
     * @param array $params Parameters (ignored).
     *
     * @return bool Always returns true.
     */
    protected function validateOptional(string $field, mixed $value, array $params): bool
    {
        // Always return true
        return true;
    }

    /**
     * Validates whether or not a field is required based on whether or not other fields are present
     *
     * Makes a field required if ANY (default) or ALL (with second parameter true) of the specified
     * fields are present and not empty. Supports nested field validation with dot notation.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string|array $fieldsToCheck, [1] => bool $allRequired (default: false).
     * @param array $fields The full list of data to be validated.
     *
     * @return bool True if validation passes, false if required but empty.
     */
    protected function validateRequiredWith(string $field, mixed $value, array $params, array $fields): bool
    {
        $conditionallyReq = false;

        // If we actually have conditionally required with fields to check against
        if (isset($params[0])) {
            // Convert single value to array if it isn't already
            $reqParams = is_array($params[0]) ? $params[0] : [$params[0]];
            // Check for the flag indicating if all fields are required
            $allRequired = isset($params[1]) && (bool)$params[1];
            $filledFieldsCount = 0;

            foreach ($reqParams as $requiredField) {
                // Check the field is set, not null, and not the empty string
                [$requiredFieldValue, $multiple] = $this->fieldAccessor->getPart($fields, explode('.', (string) $requiredField));
                if (isset($requiredFieldValue) && (!is_string($requiredFieldValue) || trim($requiredFieldValue) !== '')) {
                    if (!$allRequired) {
                        $conditionallyReq = true;
                        break;
                    }
                    $filledFieldsCount++;
                }
            }

            // If all required fields are present in strict mode, we're requiring it
            if ($allRequired && $filledFieldsCount === count($reqParams)) {
                $conditionallyReq = true;
            }
        }
        // If we have conditionally required fields
        return !($conditionallyReq && (is_null($value) || (is_string($value) && trim($value) === '')));
    }

    /**
     * Validates whether or not a field is required based on whether or not other fields are absent
     *
     * Makes a field required if ANY (default) or ALL (with second parameter true) of the specified
     * fields are absent or empty. This is the inverse of requiredWith.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string|array $fieldsToCheck, [1] => bool $allEmpty (default: false).
     * @param array $fields The full list of data to be validated.
     *
     * @return bool True if validation passes, false if required but empty.
     */
    protected function validateRequiredWithout(string $field, mixed $value, array $params, array $fields): bool
    {
        $conditionallyReq = false;

        // If we actually have conditionally required with fields to check against
        if (isset($params[0])) {
            // Convert single value to array if it isn't already
            $reqParams = is_array($params[0]) ? $params[0] : [$params[0]];
            // Check for the flag indicating if all fields are required
            $allEmpty = isset($params[1]) && (bool)$params[1];
            $emptyFieldsCount = 0;

            foreach ($reqParams as $requiredField) {
                // Check the field is NOT set, null, or the empty string, in which case we are requiring this value be present
                [$requiredFieldValue, $multiple] = $this->fieldAccessor->getPart($fields, explode('.', (string) $requiredField));
                if (!isset($requiredFieldValue) || (is_string($requiredFieldValue) && trim($requiredFieldValue) === '')) {
                    if (!$allEmpty) {
                        $conditionallyReq = true;
                        break;
                    }
                    $emptyFieldsCount++;
                }
            }

            // If all fields were empty, then we're requiring this in strict mode
            if ($allEmpty && $emptyFieldsCount === count($reqParams)) {
                $conditionallyReq = true;
            }
        }
        // If we have conditionally required fields
        return !($conditionallyReq && (is_null($value) || (is_string($value) && trim($value) === '')));
    }
}
