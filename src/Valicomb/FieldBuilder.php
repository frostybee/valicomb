<?php

declare(strict_types=1);

namespace Frostybee\Valicomb;

/**
 * Fluent field builder for validation rules.
 *
 * Provides a fluent, chainable interface for defining validation rules on individual fields.
 * This class enables IDE autocomplete support and a more intuitive API for field validation.
 *
 * @package Valicomb
 *
 * @example Basic usage:
 * ```php
 * $v = new Validator($data);
 * $v->field('email')->required()->email()->lengthMax(254)
 *   ->field('password')->required()->lengthMin(8);
 * ```
 * @example With custom messages:
 * ```php
 * $v->field('username')
 *     ->label('Username')
 *     ->required()->message('Please enter a username')
 *     ->alphaNum()->message('Only letters and numbers allowed');
 * ```
 * @example With custom callable:
 * ```php
 * $v->field('coupon')
 *     ->optional()
 *     ->rule(fn($f, $v) => in_array($v, $validCoupons), 'Invalid coupon code');
 * ```
 */
class FieldBuilder
{
    /**
     * The validator instance.
     */
    private Validator $validator;

    /**
     * The current field name being configured.
     */
    private string $fieldName;

    /**
     * Create a new FieldBuilder instance.
     *
     * @param Validator $validator The validator instance.
     * @param string $fieldName The name of the field to validate.
     */
    public function __construct(Validator $validator, string $fieldName)
    {
        $this->validator = $validator;
        $this->fieldName = $fieldName;
    }

    /**
     * Chain to another field for validation.
     *
     * Switches the builder context to a different field while maintaining
     * the same validator instance.
     *
     * @param string $fieldName The name of the next field to validate.
     *
     * @return self A new FieldBuilder instance for the specified field.
     *
     * @example Chaining fields:
     * ```php
     * $v->field('email')->required()->email()
     *   ->field('name')->required()->alpha();
     * ```
     */
    public function field(string $fieldName): self
    {
        return new self($this->validator, $fieldName);
    }

    /**
     * Add a custom validation rule.
     *
     * Allows adding custom validation rules with a string name or callable.
     *
     * @param string|callable(string, mixed, array): bool $rule The rule name or callable validation function.
     * @param mixed ...$params Optional parameters for the rule.
     *
     * @return self Returns $this for method chaining.
     *
     * @example With custom callable:
     * ```php
     * $v->field('code')->rule(fn($f, $v) => strlen($v) === 6, 'Must be 6 characters');
     * ```
     * @example With named rule:
     * ```php
     * $v->field('age')->rule('min', 18);
     * ```
     */
    public function rule(string|callable $rule, mixed ...$params): self
    {
        $this->validator->rule($rule, $this->fieldName, ...$params);

        return $this;
    }

    /**
     * Set a custom error message for the last added rule.
     *
     * @param string $message The custom error message.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Custom error message:
     * ```php
     * $v->field('email')
     *     ->required()->message('Email is required')
     *     ->email()->message('Please enter a valid email');
     * ```
     */
    public function message(string $message): self
    {
        $this->validator->message($message);

        return $this;
    }

    /**
     * Set a human-readable label for the field.
     *
     * Labels are used in error messages instead of raw field names.
     *
     * @param string $label The human-readable label for the field.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Setting a label:
     * ```php
     * $v->field('email_address')
     *     ->label('Email Address')
     *     ->required();
     * // Error: "Email Address is required" instead of "Email address is required"
     * ```
     */
    public function label(string $label): self
    {
        $this->validator->labels([$this->fieldName => $label]);

        return $this;
    }

    /**
     * Return to the validator instance.
     *
     * Ends the field builder chain and returns to the validator
     * for further configuration or validation.
     *
     * @return Validator The parent validator instance.
     *
     * @example Returning to validator:
     * ```php
     * $v->field('email')->required()->email()->end()
     *   ->validate();
     * ```
     */
    public function end(): Validator
    {
        return $this->validator;
    }

    /**
     * Get the current field name.
     *
     * @return string The field name being configured.
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Get the validator instance.
     *
     * @return Validator The parent validator instance.
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    // ===========================================
    // Conditional Rules
    // ===========================================

    /**
     * Mark the field as required.
     *
     * A required field must be present and not empty (unless allowEmpty is true).
     *
     * @param bool $allowEmpty If true, empty strings are allowed (but field must still be present).
     *
     * @return self Returns $this for method chaining.
     *
     * @example Basic required:
     * ```php
     * $v->field('email')->required();
     * ```
     * @example Allow empty values:
     * ```php
     * $v->field('nickname')->required(true); // Must be present but can be empty
     * ```
     */
    public function required(bool $allowEmpty = false): self
    {
        $this->validator->rule('required', $this->fieldName, $allowEmpty);

        return $this;
    }

    /**
     * Mark the field as optional.
     *
     * Optional fields are only validated if they are present in the input.
     * If the field is not present, all subsequent rules are skipped.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Optional field:
     * ```php
     * $v->field('nickname')->optional()->alphaNum();
     * // Only validates alphaNum if nickname is present
     * ```
     */
    public function optional(): self
    {
        $this->validator->rule('optional', $this->fieldName);

        return $this;
    }

    /**
     * Mark the field as nullable.
     *
     * Nullable fields can have a null value. If the value is null,
     * subsequent validation rules are skipped.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Nullable field:
     * ```php
     * $v->field('middle_name')->nullable()->alpha();
     * // Only validates alpha if middle_name is not null
     * ```
     */
    public function nullable(): self
    {
        $this->validator->rule('nullable', $this->fieldName);

        return $this;
    }

    /**
     * Require this field when other field(s) are present.
     *
     * @param string|array $fields Field name(s) that trigger this requirement.
     * @param bool $strict If true, all specified fields must be present.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Required with another field:
     * ```php
     * $v->field('city')->requiredWith('address');
     * // city is required if address is present
     * ```
     * @example Required with multiple fields (any):
     * ```php
     * $v->field('zip')->requiredWith(['address', 'city']);
     * // zip is required if address OR city is present
     * ```
     * @example Required with multiple fields (all):
     * ```php
     * $v->field('country')->requiredWith(['address', 'city'], true);
     * // country is required only if BOTH address AND city are present
     * ```
     */
    public function requiredWith(string|array $fields, bool $strict = false): self
    {
        $this->validator->rule('requiredWith', $this->fieldName, $fields, $strict);

        return $this;
    }

    /**
     * Require this field when other field(s) are NOT present.
     *
     * @param string|array $fields Field name(s) that trigger this requirement when absent.
     * @param bool $strict If true, all specified fields must be absent.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Required without another field:
     * ```php
     * $v->field('phone')->requiredWithout('email');
     * // phone is required if email is NOT present
     * ```
     * @example Required without any of multiple fields:
     * ```php
     * $v->field('contact')->requiredWithout(['phone', 'email']);
     * // contact is required if phone OR email is missing
     * ```
     * @example Required without all fields:
     * ```php
     * $v->field('contact')->requiredWithout(['phone', 'email'], true);
     * // contact is required only if BOTH phone AND email are missing
     * ```
     */
    public function requiredWithout(string|array $fields, bool $strict = false): self
    {
        $this->validator->rule('requiredWithout', $this->fieldName, $fields, $strict);

        return $this;
    }

    // ===========================================
    // Comparison Rules
    // ===========================================

    /**
     * Validate that the field equals another field.
     *
     * @param string $otherField The name of the field to compare against.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Password confirmation:
     * ```php
     * $v->field('password_confirm')->equals('password');
     * ```
     */
    public function equals(string $otherField): self
    {
        $this->validator->rule('equals', $this->fieldName, $otherField);

        return $this;
    }

    /**
     * Validate that the field is different from another field.
     *
     * @param string $otherField The name of the field to compare against.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Different values:
     * ```php
     * $v->field('new_password')->different('old_password');
     * ```
     */
    public function different(string $otherField): self
    {
        $this->validator->rule('different', $this->fieldName, $otherField);

        return $this;
    }

    /**
     * Validate that the field is accepted (true, "yes", "on", "1", 1).
     *
     * Commonly used for terms of service checkboxes.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Terms acceptance:
     * ```php
     * $v->field('terms')->accepted();
     * ```
     */
    public function accepted(): self
    {
        $this->validator->rule('accepted', $this->fieldName);

        return $this;
    }

    // ===========================================
    // String Rules
    // ===========================================

    /**
     * Validate that the field contains only alphabetic characters.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Alpha validation:
     * ```php
     * $v->field('name')->alpha();
     * ```
     */
    public function alpha(): self
    {
        $this->validator->rule('alpha', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field contains only alphanumeric characters.
     *
     * @return self Returns $this for method chaining.
     *
     * @example AlphaNum validation:
     * ```php
     * $v->field('username')->alphaNum();
     * ```
     */
    public function alphaNum(): self
    {
        $this->validator->rule('alphaNum', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field contains only ASCII characters.
     *
     * @return self Returns $this for method chaining.
     *
     * @example ASCII validation:
     * ```php
     * $v->field('code')->ascii();
     * ```
     */
    public function ascii(): self
    {
        $this->validator->rule('ascii', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid URL slug.
     *
     * A slug contains only lowercase letters, numbers, and hyphens.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Slug validation:
     * ```php
     * $v->field('url_slug')->slug();
     * ```
     */
    public function slug(): self
    {
        $this->validator->rule('slug', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field contains a specific substring.
     *
     * @param string $substring The substring to search for.
     * @param bool $strict If true, performs case-sensitive search.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Contains validation:
     * ```php
     * $v->field('email')->contains('@');
     * ```
     * @example Case-sensitive:
     * ```php
     * $v->field('code')->contains('ABC', true);
     * ```
     */
    public function contains(string $substring, bool $strict = false): self
    {
        $this->validator->rule('contains', $this->fieldName, $substring, $strict);

        return $this;
    }

    /**
     * Validate that the field matches a regular expression pattern.
     *
     * @param string $pattern The regex pattern to match against.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Regex validation:
     * ```php
     * $v->field('phone')->regex('/^[0-9]{10}$/');
     * ```
     */
    public function regex(string $pattern): self
    {
        $this->validator->rule('regex', $this->fieldName, $pattern);

        return $this;
    }

    /**
     * Validate that the field starts with a specific prefix.
     *
     * @param string|array $prefix The prefix(es) to check for.
     * @param bool $caseSensitive If true, performs case-sensitive comparison.
     *
     * @return self Returns $this for method chaining.
     *
     * @example StartsWith validation:
     * ```php
     * $v->field('url')->startsWith('http');
     * ```
     * @example Multiple prefixes:
     * ```php
     * $v->field('url')->startsWith(['http://', 'https://']);
     * ```
     */
    public function startsWith(string|array $prefix, bool $caseSensitive = false): self
    {
        $this->validator->rule('startsWith', $this->fieldName, $prefix, $caseSensitive);

        return $this;
    }

    /**
     * Validate that the field ends with a specific suffix.
     *
     * @param string|array $suffix The suffix(es) to check for.
     * @param bool $caseSensitive If true, performs case-sensitive comparison.
     *
     * @return self Returns $this for method chaining.
     *
     * @example EndsWith validation:
     * ```php
     * $v->field('filename')->endsWith('.pdf');
     * ```
     * @example Multiple suffixes:
     * ```php
     * $v->field('filename')->endsWith(['.jpg', '.png', '.gif']);
     * ```
     */
    public function endsWith(string|array $suffix, bool $caseSensitive = false): self
    {
        $this->validator->rule('endsWith', $this->fieldName, $suffix, $caseSensitive);

        return $this;
    }

    /**
     * Validate that the field is a valid UUID.
     *
     * @param int|null $version Optional specific UUID version to validate (1-5).
     *
     * @return self Returns $this for method chaining.
     *
     * @example Any UUID:
     * ```php
     * $v->field('id')->uuid();
     * ```
     * @example UUID v4:
     * ```php
     * $v->field('id')->uuid(4);
     * ```
     */
    public function uuid(?int $version = null): self
    {
        $this->validator->rule('uuid', $this->fieldName, $version);

        return $this;
    }

    /**
     * Validate password strength requirements.
     *
     * @param int|array $config Minimum score (1-4) or array of requirements.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Minimum score:
     * ```php
     * $v->field('password')->passwordStrength(3);
     * ```
     * @example Custom requirements:
     * ```php
     * $v->field('password')->passwordStrength([
     *     'min' => 8,
     *     'uppercase' => 1,
     *     'lowercase' => 1,
     *     'number' => 1,
     *     'special' => 1,
     * ]);
     * ```
     */
    public function passwordStrength(int|array $config): self
    {
        $this->validator->rule('passwordStrength', $this->fieldName, $config);

        return $this;
    }

    // ===========================================
    // Length Rules
    // ===========================================

    /**
     * Validate that the field has an exact length.
     *
     * @param int $length The exact length required.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Exact length:
     * ```php
     * $v->field('pin')->length(4);
     * ```
     */
    public function length(int $length): self
    {
        $this->validator->rule('length', $this->fieldName, $length);

        return $this;
    }

    /**
     * Validate that the field length is within a range.
     *
     * @param int $min Minimum length.
     * @param int $max Maximum length.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Length between:
     * ```php
     * $v->field('username')->lengthBetween(3, 20);
     * ```
     */
    public function lengthBetween(int $min, int $max): self
    {
        $this->validator->rule('lengthBetween', $this->fieldName, $min, $max);

        return $this;
    }

    /**
     * Validate that the field has a minimum length.
     *
     * @param int $min Minimum length.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Minimum length:
     * ```php
     * $v->field('password')->lengthMin(8);
     * ```
     */
    public function lengthMin(int $min): self
    {
        $this->validator->rule('lengthMin', $this->fieldName, $min);

        return $this;
    }

    /**
     * Validate that the field has a maximum length.
     *
     * @param int $max Maximum length.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Maximum length:
     * ```php
     * $v->field('bio')->lengthMax(500);
     * ```
     */
    public function lengthMax(int $max): self
    {
        $this->validator->rule('lengthMax', $this->fieldName, $max);

        return $this;
    }

    // ===========================================
    // Numeric Rules
    // ===========================================

    /**
     * Validate that the field is numeric.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Numeric validation:
     * ```php
     * $v->field('price')->numeric();
     * ```
     */
    public function numeric(): self
    {
        $this->validator->rule('numeric', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is an integer.
     *
     * @param bool $strict If true, only accepts actual integer types (not numeric strings).
     *
     * @return self Returns $this for method chaining.
     *
     * @example Integer validation:
     * ```php
     * $v->field('quantity')->integer();
     * ```
     * @example Strict integer validation:
     * ```php
     * $v->field('count')->integer(true); // Won't accept "123"
     * ```
     */
    public function integer(bool $strict = false): self
    {
        $this->validator->rule('integer', $this->fieldName, $strict);

        return $this;
    }

    /**
     * Validate that the field is at least a minimum value.
     *
     * @param int|float $min Minimum value.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Minimum value:
     * ```php
     * $v->field('age')->min(18);
     * ```
     */
    public function min(int|float $min): self
    {
        $this->validator->rule('min', $this->fieldName, $min);

        return $this;
    }

    /**
     * Validate that the field is at most a maximum value.
     *
     * @param int|float $max Maximum value.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Maximum value:
     * ```php
     * $v->field('age')->max(120);
     * ```
     */
    public function max(int|float $max): self
    {
        $this->validator->rule('max', $this->fieldName, $max);

        return $this;
    }

    /**
     * Validate that the field is between a minimum and maximum value.
     *
     * @param int|float $min Minimum value.
     * @param int|float $max Maximum value.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Between values:
     * ```php
     * $v->field('rating')->between(1, 5);
     * ```
     */
    public function between(int|float $min, int|float $max): self
    {
        $this->validator->rule('between', $this->fieldName, [$min, $max]);

        return $this;
    }

    /**
     * Validate that the field is a boolean value.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Boolean validation:
     * ```php
     * $v->field('is_active')->boolean();
     * ```
     */
    public function boolean(): self
    {
        $this->validator->rule('boolean', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a positive number.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Positive number:
     * ```php
     * $v->field('quantity')->positive();
     * ```
     */
    public function positive(): self
    {
        $this->validator->rule('positive', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field has a specific number of decimal places.
     *
     * @param int $places Number of decimal places.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Decimal places:
     * ```php
     * $v->field('price')->decimalPlaces(2);
     * ```
     */
    public function decimalPlaces(int $places): self
    {
        $this->validator->rule('decimalPlaces', $this->fieldName, $places);

        return $this;
    }

    // ===========================================
    // Date Rules
    // ===========================================

    /**
     * Validate that the field is a valid date.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Date validation:
     * ```php
     * $v->field('birthday')->date();
     * ```
     */
    public function date(): self
    {
        $this->validator->rule('date', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field matches a specific date format.
     *
     * @param string $format The date format (PHP date format string).
     *
     * @return self Returns $this for method chaining.
     *
     * @example Date format:
     * ```php
     * $v->field('event_date')->dateFormat('Y-m-d');
     * ```
     */
    public function dateFormat(string $format): self
    {
        $this->validator->rule('dateFormat', $this->fieldName, $format);

        return $this;
    }

    /**
     * Validate that the field is a date before a specific date.
     *
     * @param string $date The comparison date.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Date before:
     * ```php
     * $v->field('start_date')->dateBefore('2025-12-31');
     * ```
     */
    public function dateBefore(string $date): self
    {
        $this->validator->rule('dateBefore', $this->fieldName, $date);

        return $this;
    }

    /**
     * Validate that the field is a date after a specific date.
     *
     * @param string $date The comparison date.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Date after:
     * ```php
     * $v->field('start_date')->dateAfter('2024-01-01');
     * ```
     */
    public function dateAfter(string $date): self
    {
        $this->validator->rule('dateAfter', $this->fieldName, $date);

        return $this;
    }

    /**
     * Validate that the field is a date in the past.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Past date:
     * ```php
     * $v->field('birthday')->past();
     * ```
     */
    public function past(): self
    {
        $this->validator->rule('past', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a date in the future.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Future date:
     * ```php
     * $v->field('appointment_date')->future();
     * ```
     */
    public function future(): self
    {
        $this->validator->rule('future', $this->fieldName);

        return $this;
    }

    // ===========================================
    // Network Rules
    // ===========================================

    /**
     * Validate that the field is a valid IP address.
     *
     * @return self Returns $this for method chaining.
     *
     * @example IP validation:
     * ```php
     * $v->field('server_ip')->ip();
     * ```
     */
    public function ip(): self
    {
        $this->validator->rule('ip', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid IPv4 address.
     *
     * @return self Returns $this for method chaining.
     *
     * @example IPv4 validation:
     * ```php
     * $v->field('server_ip')->ipv4();
     * ```
     */
    public function ipv4(): self
    {
        $this->validator->rule('ipv4', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid IPv6 address.
     *
     * @return self Returns $this for method chaining.
     *
     * @example IPv6 validation:
     * ```php
     * $v->field('server_ip')->ipv6();
     * ```
     */
    public function ipv6(): self
    {
        $this->validator->rule('ipv6', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid email address.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Email validation:
     * ```php
     * $v->field('email')->email();
     * ```
     */
    public function email(): self
    {
        $this->validator->rule('email', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid email address with DNS verification.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Email with DNS validation:
     * ```php
     * $v->field('email')->emailDNS();
     * ```
     */
    public function emailDNS(): self
    {
        $this->validator->rule('emailDNS', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid URL.
     *
     * @return self Returns $this for method chaining.
     *
     * @example URL validation:
     * ```php
     * $v->field('website')->url();
     * ```
     */
    public function url(): self
    {
        $this->validator->rule('url', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid URL that is active/reachable.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Active URL validation:
     * ```php
     * $v->field('website')->urlActive();
     * ```
     */
    public function urlActive(): self
    {
        $this->validator->rule('urlActive', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid URL with strict checking.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Strict URL validation:
     * ```php
     * $v->field('website')->urlStrict();
     * ```
     */
    public function urlStrict(): self
    {
        $this->validator->rule('urlStrict', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field is a valid phone number.
     *
     * @param string|null $country Optional country code for country-specific validation.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Phone validation:
     * ```php
     * $v->field('phone')->phone();
     * ```
     * @example Country-specific validation:
     * ```php
     * $v->field('phone')->phone('US');
     * ```
     */
    public function phone(?string $country = null): self
    {
        $this->validator->rule('phone', $this->fieldName, $country);

        return $this;
    }

    // ===========================================
    // Array Rules
    // ===========================================

    /**
     * Validate that the field is an array.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Array validation:
     * ```php
     * $v->field('tags')->array();
     * ```
     */
    public function array(): self
    {
        $this->validator->rule('array', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field value is in a list of allowed values.
     *
     * @param array $values Array of allowed values.
     *
     * @return self Returns $this for method chaining.
     *
     * @example In validation:
     * ```php
     * $v->field('status')->in(['active', 'pending', 'inactive']);
     * ```
     */
    public function in(array $values): self
    {
        $this->validator->rule('in', $this->fieldName, $values);

        return $this;
    }

    /**
     * Validate that the field value is NOT in a list of disallowed values.
     *
     * @param array $values Array of disallowed values.
     *
     * @return self Returns $this for method chaining.
     *
     * @example NotIn validation:
     * ```php
     * $v->field('username')->notIn(['admin', 'root', 'system']);
     * ```
     */
    public function notIn(array $values): self
    {
        $this->validator->rule('notIn', $this->fieldName, $values);

        return $this;
    }

    /**
     * Validate that the field (array) contains a specific value.
     *
     * @param mixed $value The value to check for.
     * @param bool $strict If true, performs strict type comparison.
     *
     * @return self Returns $this for method chaining.
     *
     * @example ListContains validation:
     * ```php
     * $v->field('permissions')->listContains('admin');
     * ```
     */
    public function listContains(mixed $value, bool $strict = false): self
    {
        $this->validator->rule('listContains', $this->fieldName, $value, $strict);

        return $this;
    }

    /**
     * Validate that the field is a subset of allowed values.
     *
     * @param array $values Array of allowed values.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Subset validation:
     * ```php
     * $v->field('roles')->subset(['admin', 'editor', 'viewer']);
     * ```
     */
    public function subset(array $values): self
    {
        $this->validator->rule('subset', $this->fieldName, $values);

        return $this;
    }

    /**
     * Validate that the field (array) contains only unique values.
     *
     * @return self Returns $this for method chaining.
     *
     * @example ContainsUnique validation:
     * ```php
     * $v->field('emails')->containsUnique();
     * ```
     */
    public function containsUnique(): self
    {
        $this->validator->rule('containsUnique', $this->fieldName);

        return $this;
    }

    /**
     * Validate that the field (array) has specific keys.
     *
     * @param array $keys Required keys.
     *
     * @return self Returns $this for method chaining.
     *
     * @example ArrayHasKeys validation:
     * ```php
     * $v->field('address')->arrayHasKeys(['street', 'city', 'zip']);
     * ```
     */
    public function arrayHasKeys(array $keys): self
    {
        $this->validator->rule('arrayHasKeys', $this->fieldName, $keys);

        return $this;
    }

    // ===========================================
    // Type Rules
    // ===========================================

    /**
     * Validate that the field is an instance of a specific class.
     *
     * @param string $class The fully qualified class name.
     *
     * @return self Returns $this for method chaining.
     *
     * @example InstanceOf validation:
     * ```php
     * $v->field('user')->instanceOf(User::class);
     * ```
     */
    public function instanceOf(string $class): self
    {
        $this->validator->rule('instanceOf', $this->fieldName, $class);

        return $this;
    }

    /**
     * Validate that the field is a valid credit card number.
     *
     * @param string|array|null $type Card type(s) to validate (e.g., 'visa', 'mastercard').
     * @param array|null $allowedTypes Additional allowed card types.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Credit card validation:
     * ```php
     * $v->field('card_number')->creditCard();
     * ```
     * @example Specific card type:
     * ```php
     * $v->field('card_number')->creditCard('visa');
     * ```
     */
    public function creditCard(string|array|null $type = null, ?array $allowedTypes = null): self
    {
        $this->validator->rule('creditCard', $this->fieldName, $type, $allowedTypes);

        return $this;
    }
}
