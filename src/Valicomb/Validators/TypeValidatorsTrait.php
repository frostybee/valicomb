<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use function array_keys;
use function in_array;

use InvalidArgumentException;

use function is_array;
use function is_object;
use function is_string;
use function preg_match;
use function preg_replace;
use function strlen;
use function substr;

/**
 * Type Validators Trait
 *
 * Contains all type-checking validation methods including:
 * - Instance of validation
 * - Credit card validation with Luhn algorithm
 *
 * @package Valicomb\Validators
 */
trait TypeValidatorsTrait
{
    /**
     * Validate instance of a class
     *
     * Validates that a value is an object and is an instance of a specified class or interface.
     * Uses PHP's instanceof operator for inheritance-aware checking.
     *
     * Validation checks:
     * 1. Value must be an object (not string, array, scalar, etc.)
     * 2. Value must be instance of the specified class/interface
     *
     * The instanceof check respects inheritance and interface implementation:
     * - If class B extends class A, B is instanceof A
     * - If class C implements interface I, C is instanceof I
     *
     * Parameter format:
     * - params[0] can be a class name string (e.g., 'DateTime') OR
     * - params[0] can be an object instance (class extracted via ::class)
     *
     * Use cases:
     * - Ensuring dependency injection receives correct types
     * - Validating polymorphic data structures
     * - Type-checking in configuration or factory patterns
     * - Verifying interface implementation
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be an object).
     * @param array $params Class specification: [0] => class name string OR object instance.
     *
     * @throws InvalidArgumentException If params[0] is not provided or is not a valid class/object
     *
     * @return bool True if value is an instance of the specified class, false otherwise.
     */
    protected function validateInstanceOf(string $field, mixed $value, array $params): bool
    {
        if (!is_object($value)) {
            return false;
        }

        if (!isset($params[0])) {
            throw new InvalidArgumentException('Class name or object required for instanceOf validation');
        }

        $expectedClass = is_object($params[0]) ? $params[0]::class : $params[0];

        if (!is_string($expectedClass)) {
            throw new InvalidArgumentException('Expected class name must be a string');
        }

        // The instanceof operator already handles exact class matches and inheritance
        return $value instanceof $expectedClass;
    }

    /**
     * Validate that a field contains a valid credit card
     *
     * Validates credit card numbers using the Luhn algorithm (mod 10 check) and optionally
     * validates against specific card types using regex patterns. Supports major card brands.
     *
     * Validation process:
     * 1. Strips all non-numeric characters from input
     * 2. Checks length is between 13-19 digits (industry standard)
     * 3. Applies Luhn algorithm to verify checksum
     * 4. If card type(s) specified, validates against brand-specific regex patterns
     *
     * Supported card types:
     * - visa: Starts with 4, 13 or 16 digits
     * - mastercard: Starts with 51-55 or 22-27, 16 digits
     * - amex: Starts with 34 or 37, 15 digits
     * - dinersclub: Starts with 300-305 or 36/38, 14 digits
     * - discover: Starts with 6011 or 65, 16 digits
     *
     * Parameter formats:
     * - No params: Validates using Luhn only (any valid card number)
     * - [cardType]: Validates Luhn + specific card type (e.g., ['visa'])
     * - [[cardTypes]]: Validates Luhn + any of the specified types (e.g., [['visa', 'mastercard']])
     * - [cardType, [allowedTypes]]: Validates cardType is in allowedTypes, then validates
     *
     * Security notes:
     * - This validates FORMAT only, not if card is active/funded
     * - Never store raw credit card numbers - use tokenization
     * - Consider PCI DSS compliance requirements
     * - Luhn algorithm prevents typos but not randomly valid numbers
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (credit card number, spaces/dashes allowed).
     * @param array $params Optional card type constraints (see description for formats).
     *
     * @return bool True if credit card number is valid and matches type constraints, false otherwise.
     */
    protected function validateCreditCard(string $field, mixed $value, array $params): bool
    {
        /** @var array|null $cards */
        $cards = null;
        /** @var string|null $cardType */
        $cardType = null;

        // Strip non-numeric characters once for consistent validation
        $strippedNumber = preg_replace('/[^0-9]+/', '', (string)$value);
        if (!is_string($strippedNumber) || $strippedNumber === '') {
            return false;
        }

        /**
         * If there has been an array of valid cards supplied, or the name of the users card
         * or the name and an array of valid cards
         */
        if ($params !== []) {
            /**
             * Array of valid cards
             */
            if (is_array($params[0])) {
                $cards = $params[0];
            } elseif (is_string($params[0])) {
                $cardType = $params[0];
                if (isset($params[1]) && is_array($params[1])) {
                    $cards = $params[1];
                    if (!in_array($cardType, $cards, true)) {
                        return false;
                    }
                }
            }
        }

        /**
         * Luhn algorithm
         */
        $numberIsValid = function () use ($strippedNumber): bool {
            $sum = 0;

            $strlen = strlen($strippedNumber);

            // Check length bounds
            if ($strlen < 13 || $strlen > 19) {
                return false;
            }

            for ($i = 0; $i < $strlen; $i++) {
                $digit = (int)substr($strippedNumber, $strlen - $i - 1, 1);
                if ($i % 2 === 1) {
                    $subTotal = $digit * 2;
                    if ($subTotal > 9) {
                        $subTotal = ($subTotal - 10) + 1;
                    }
                } else {
                    $subTotal = $digit;
                }
                $sum += $subTotal;
            }

            return $sum > 0 && $sum % 10 === 0;
        };

        if ($numberIsValid()) {
            if ($cards === null && $cardType === null) {
                return true;
            }

            $cardRegex = [
                'visa' => '#^4\d{12}(?:\d{3})?$#',
                'mastercard' => '#^(5[1-5]|2[2-7])\d{14}$#',
                'amex' => '#^3[47]\d{13}$#',
                'dinersclub' => '#^3(?:0[0-5]|[68]\d)\d{11}$#',
                'discover' => '#^6(?:011|5\d{2})\d{12}$#',
            ];

            if ($cardType !== null) {
                // If we don't have any valid cards specified and the card we've been given isn't in our regex array
                if ($cards === null && !in_array($cardType, array_keys($cardRegex), true)) {
                    return false;
                }

                // Use stripped number for type validation (consistent with Luhn check)
                return preg_match($cardRegex[$cardType], $strippedNumber) === 1;
            }

            // At this point, $cards must be non-null (from the early return check above)
            // Check our users card against only the ones we have
            foreach ($cards as $card) {
                if (in_array($card, array_keys($cardRegex), true) && preg_match($cardRegex[$card], $strippedNumber) === 1) {
                    // If the card is valid, we want to stop looping
                    return true;
                }
            }
            // None of the specified cards matched
            return false;
        }

        // If we've got this far, the card has passed no validation so it's invalid
        return false;
    }
}
