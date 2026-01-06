<?php

declare(strict_types=1);

namespace Frostybee\Valicomb;

use function array_diff;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_shift;
use function array_unshift;
use function array_values;
use function call_user_func;
use function count;

use DateTime;

use function defined;
use function explode;

use Frostybee\Valicomb\Core\ErrorManager;
use Frostybee\Valicomb\Core\FieldAccessor;
use Frostybee\Valicomb\Core\LanguageManager;
use Frostybee\Valicomb\Core\RuleRegistry;

use function implode;
use function in_array;

use InvalidArgumentException;

use function is_array;
use function is_callable;
use function is_null;
use function is_string;
use function method_exists;
use function preg_match;
use function str_contains;
use function ucfirst;

/**
 * Validation Class.
 *
 * Modern, type-safe validation library for PHP 8.1+ that provides a fluent interface
 * for validating data with built-in validation rules and support for custom rules.
 *
 * This class provides comprehensive data validation with:
 * - 40+ built-in validation rules (email, URL, date, numeric, string, etc.).
 * - Fluent, chainable interface for defining validation rules.
 * - Support for nested array validation with dot notation.
 * - Custom rule registration (global and instance-specific).
 * - Multilingual error messages with customizable field labels.
 * - Optional and conditional validation rules.
 * - Type-safe implementation with strict types.
 *
 * @package Valicomb
 *
 * @author  Vance Lucas <vance@vancelucas.com> (original author)
 * @author  frostybee (current maintainer)
 *
 * @link    http://www.vancelucas.com/
 *
 * @example Basic validation usage:
 * ```php
 * $v = new Validator(['email' => 'test@example.com', 'age' => 25]);
 * $v->rule('required', ['email', 'age'])
 *   ->rule('email', 'email')
 *   ->rule('min', 'age', 18);
 *
 * if ($v->validate()) {
 *     // Validation passed
 *     $data = $v->data();
 * } else {
 *     // Validation failed
 *     $errors = $v->errors();
 * }
 * ```
 * @example Custom error messages and labels:
 * ```php
 * $v = new Validator($data);
 * $v->rule('required', 'username')
 *   ->message('Please provide a username')
 *   ->label('Username');
 * ```
 * @example Nested array validation:
 * ```php
 * $data = ['user' => ['email' => 'test@example.com']];
 * $v = new Validator($data);
 * $v->rule('email', 'user.email');
 * ```
 */
class Validator
{
    use Validators\StringValidatorsTrait;
    use Validators\NumericValidatorsTrait;
    use Validators\LengthValidatorsTrait;
    use Validators\DateValidatorsTrait;
    use Validators\ArrayValidatorsTrait;
    use Validators\NetworkValidatorsTrait;
    use Validators\TypeValidatorsTrait;
    use Validators\ComparisonValidatorsTrait;
    use Validators\ConditionalValidatorsTrait;

    /**
     * Default error message.
     */
    public const ERROR_DEFAULT = 'Invalid';

    /**
     * Error manager instance.
     */
    private ErrorManager $errorManager;

    /**
     * Field accessor instance.
     */
    private FieldAccessor $fieldAccessor;

    /**
     * Rule registry instance.
     */
    private RuleRegistry $ruleRegistry;

    /**
     * Field data to validate.
     */
    protected array $fields = [];

    /**
     * Validation rules to apply.
     */
    protected array $validations = [];

    /**
     * Valid URL prefixes for URL validation.
     */
    protected array $validUrlPrefixes = ['http://', 'https://', 'ftp://'];

    /**
     * Whether to stop validation on first failure.
     */
    protected bool $stopOnFirstFail = false;

    /**
     * Whether to enable strict mode (fail if extra fields are present).
     */
    protected bool $strictMode = false;

    /**
     * Setup validation.
     *
     * Creates a new Validator instance with the provided data and optional configuration.
     * If a field whitelist is provided, only those fields will be validated.
     *
     * Language and language directory can be specified to load error messages from
     * custom language files. If not provided, defaults to English ('en') and the
     * built-in language directory.
     *
     * @param array $data Data to validate (key-value pairs).
     * @param array $fields Optional field whitelist (only these fields will be validated).
     * @param string|null $lang Language code for error messages (e.g., 'en', 'fr', 'es').
     * @param string|null $langDir Custom language file directory path.
     *
     * @throws InvalidArgumentException If language code is not in the allowed list or language file cannot be loaded
     *
     * @return void
     *
     * @example Basic usage:
     * ```php
     * $data = ['email' => 'test@example.com', 'name' => 'John'];
     * $v = new Validator($data);
     * ```
     * @example With field whitelist:
     * ```php
     * $data = ['email' => 'test@example.com', 'password' => 'secret', 'extra' => 'ignored'];
     * $v = new Validator($data, ['email', 'password']); // Only email and password will be validated
     * ```
     */
    public function __construct(
        array $data = [],
        array $fields = [],
        ?string $lang = null,
        ?string $langDir = null,
    ) {
        // Initialize Core services
        $this->errorManager = new ErrorManager();
        $this->fieldAccessor = new FieldAccessor();
        $this->ruleRegistry = new RuleRegistry($this);

        // Filter fields if whitelist provided.
        $this->fields = $fields === []
            ? $data
            : array_intersect_key($data, array_flip($fields));

        // Initialize language files.
        LanguageManager::loadLanguage($lang, $langDir);
    }

    /**
     * Get/set language to use for validation messages.
     *
     * When called with a parameter, sets the global language code for all future Validator instances.
     * When called without a parameter, returns the current language code (defaults to 'en').
     *
     * This is a static method that affects all Validator instances globally.
     *
     * @param string|null $lang The language code to set (e.g., 'en', 'fr', 'es'), or null to get current language.
     *
     * @return string The current language code.
     *
     * @example Setting global language:
     * ```php
     * Validator::lang('fr'); // Set French as default language
     * $v = new Validator($data); // Will use French messages
     * ```
     * @example Getting current language:
     * ```php
     * $currentLang = Validator::lang(); // Returns 'en' by default
     * ```
     */
    public static function lang(?string $lang = null): string
    {
        return LanguageManager::lang($lang);
    }

    /**
     * Get/set language file path.
     *
     * When called with a parameter, sets the global language directory for all future Validator instances.
     * When called without a parameter, returns the current language directory path (defaults to '../lang').
     *
     * This is a static method that affects all Validator instances globally.
     *
     * @param string|null $dir The directory path containing language files, or null to get current directory.
     *
     * @return string The current language directory path.
     *
     * @example Setting custom language directory:
     * ```php
     * Validator::langDir('/path/to/custom/lang');
     * $v = new Validator($data); // Will load language files from custom directory
     * ```
     * @example Getting current language directory:
     * ```php
     * $langDir = Validator::langDir(); // Returns default lang directory
     * ```
     */
    public static function langDir(?string $dir = null): string
    {
        return LanguageManager::langDir($dir);
    }

    /**
     * Set whether to prepend field labels to error messages.
     *
     * When enabled (default), error messages will include the field label/name.
     * When disabled, error messages will omit the field name prefix.
     *
     * @param bool $prepend True to prepend labels to error messages, false to omit them.
     *
     * @example With labels prepended (default):
     * ```php
     * $v = new Validator(['email' => 'invalid']);
     * $v->rule('email', 'email');
     * $v->validate();
     * // Error: "Email is not a valid email address"
     * ```
     * @example With labels disabled:
     * ```php
     * $v = new Validator(['email' => 'invalid']);
     * $v->setPrependLabels(false);
     * $v->rule('email', 'email');
     * $v->validate();
     * // Error: "is not a valid email address"
     * ```
     */
    public function setPrependLabels(bool $prepend = true): void
    {
        $this->errorManager->setPrependLabels($prepend);
    }

    /**
     * Get array of fields and data.
     *
     * Returns the data array that is being validated. This is useful for retrieving
     * the validated data after validation succeeds.
     *
     * @return array The data array (key-value pairs of field names and values).
     *
     * @example Retrieving validated data:
     * ```php
     * $v = new Validator(['email' => 'test@example.com', 'name' => 'John']);
     * $v->rule('required', ['email', 'name']);
     * if ($v->validate()) {
     *     $validatedData = $v->data();
     *     // Use $validatedData safely
     * }
     * ```
     */
    public function data(): array
    {
        return $this->fields;
    }

    /**
     * Get array of error messages.
     *
     * Retrieves validation error messages after validation has been performed.
     * Can return all errors or errors for a specific field.
     *
     * When called without parameters, returns all errors grouped by field name.
     * When called with a field name, returns errors for that specific field only.
     * Returns false if the field has no errors.
     *
     * @param string|null $field Optional field name to get errors for a specific field.
     *
     * @return array|false Array of error messages, or false if field not found/no errors.
     *
     * @example Get all errors:
     * ```php
     * $v = new Validator($data);
     * $v->rule('required', ['email', 'name']);
     * if (!$v->validate()) {
     *     $errors = $v->errors();
     *     // ['email' => ['Email is required'], 'name' => ['Name is required']]
     * }
     * ```
     * @example Get errors for specific field:
     * ```php
     * $emailErrors = $v->errors('email');
     * if ($emailErrors !== false) {
     *     foreach ($emailErrors as $error) {
     *         echo $error;
     *     }
     * }
     * ```
     */
    public function errors(?string $field = null): array|false
    {
        return $this->errorManager->getErrors($field);
    }

    /**
     * Get array of error messages (alias for errors()).
     *
     * Provides a PSR-style getter alternative to errors(). Both methods
     * are functionally identical.
     *
     * @param string|null $field Optional field name to get errors for a specific field.
     *
     * @return array|false Array of error messages, or false if field not found/no errors.
     *
     * @see errors()
     */
    public function getErrors(?string $field = null): array|false
    {
        return $this->errorManager->getErrors($field);
    }

    /**
     * Add an error to error messages array.
     *
     * Manually adds an error message for a specific field. This is useful for adding
     * custom validation errors or errors from external validation processes.
     *
     * The message can contain sprintf-style placeholders that will be replaced with
     * values from the $params array. DateTime objects, arrays, and other objects
     * are automatically converted to appropriate string representations.
     *
     * The message can also contain {value} placeholder which will be replaced with
     * the formatted value that failed validation.
     *
     * @param string $field The field name to add the error to.
     * @param string $message The error message (supports sprintf placeholders and {value}).
     * @param array $params Optional parameters for sprintf placeholder replacement.
     * @param mixed $value Optional value that failed validation (for {value} placeholder).
     *
     * @example Adding a custom error:
     * ```php
     * $v = new Validator($data);
     * if ($someCustomCondition) {
     *     $v->error('field_name', 'This field failed custom validation');
     * }
     * ```
     * @example With parameters:
     * ```php
     * $v->error('age', 'Must be between %d and %d', [18, 65]);
     * // Results in: "Must be between 18 and 65"
     * ```
     * @example With {value} placeholder:
     * ```php
     * $v->error('email', '{field} "{value}" is not valid', [], 'invalid-email');
     * // Results in: "Email "invalid-email" is not valid"
     * ```
     */
    public function error(string $field, string $message, array $params = [], mixed $value = null): void
    {
        $this->errorManager->addError($field, $message, $params, $value);
    }

    /**
     * Specify validation message to use for error for the last validation rule.
     *
     * Overrides the default error message for the most recently added validation rule.
     * This method should be called immediately after rule() to customize its error message.
     *
     * Supports the fluent interface pattern for chaining.
     *
     * @param string $message The custom error message to use.
     *
     * @throws InvalidArgumentException If called before any rule has been added.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Custom error message:
     * ```php
     * $v = new Validator($data);
     * $v->rule('required', 'email')
     *   ->message('Please provide your email address');
     * ```
     * @example Chaining with multiple rules:
     * ```php
     * $v->rule('required', 'password')
     *   ->message('Password cannot be empty')
     *   ->rule('lengthMin', 'password', 8)
     *   ->message('Password must be at least 8 characters long');
     * ```
     */
    public function message(string $message): self
    {
        if ($this->validations === []) {
            throw new InvalidArgumentException(
                'Cannot set message: no validation rule has been added yet. Call rule() first.',
            );
        }

        $this->validations[count($this->validations) - 1]['message'] = $message;

        return $this;
    }

    /**
     * Reset object properties.
     *
     * Clears all validation data, errors, rules, and labels from the validator instance.
     * This allows you to reuse the same Validator instance for validating different data.
     *
     *
     * @example Reusing validator instance:
     * ```php
     * $v = new Validator(['email' => 'test@example.com']);
     * $v->rule('required', 'email');
     * $v->validate();
     *
     * // Reset and validate new data
     * $v->reset();
     * // Note: You'll need to use withData() to set new data and redefine rules
     * ```
     */
    public function reset(): void
    {
        $this->fields = [];
        $this->errorManager->clearErrors();
        $this->validations = [];
        $this->errorManager->clearLabels();
    }

    /**
     * Add label to rule.
     *
     * Sets a human-readable label for the field in the most recently added validation rule.
     * Labels are used in error messages instead of the raw field name, making errors more user-friendly.
     *
     * This method should be called immediately after rule() to set its label.
     * Supports the fluent interface pattern for chaining.
     *
     * @param string $value The human-readable label to use in error messages.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Setting a field label:
     * ```php
     * $v = new Validator($data);
     * $v->rule('required', 'email_address')
     *   ->label('Email Address');
     * // Error message: "Email Address is required" instead of "Email address is required"
     * ```
     * @example Chaining labels:
     * ```php
     * $v->rule('required', 'first_name')
     *   ->label('First Name')
     *   ->rule('required', 'last_name')
     *   ->label('Last Name');
     * ```
     */
    public function label(string $value): self
    {
        $lastRules = $this->validations[count($this->validations) - 1]['fields'];
        $this->labels([$lastRules[0] => $value]);

        return $this;
    }

    /**
     * Add labels to rules.
     *
     * Sets human-readable labels for multiple fields at once. Labels are used in error
     * messages instead of raw field names, making errors more user-friendly.
     *
     * This method can be called independently or chained after rule().
     *
     * @param array $labels Associative array where keys are field names and values are labels.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Setting multiple labels:
     * ```php
     * $v = new Validator($data);
     * $v->labels([
     *     'email_address' => 'Email Address',
     *     'first_name' => 'First Name',
     *     'last_name' => 'Last Name',
     *     'phone_number' => 'Phone Number'
     * ]);
     * ```
     * @example Chaining with rules:
     * ```php
     * $v->rule('required', ['email', 'name'])
     *   ->labels(['email' => 'Email Address', 'name' => 'Full Name']);
     * ```
     */
    public function labels(array $labels = []): self
    {
        $this->errorManager->setLabels($labels);

        return $this;
    }

    /**
     * Set whether to stop validation on first failure
     *
     * When enabled, validation will stop as soon as the first validation rule fails.
     * When disabled (default), all rules will be evaluated and all errors collected.
     *
     * @param bool $stop True to stop on first failure, false to collect all errors (default).
     *
     * @example Stop on first failure:
     * ```php
     * $v = new Validator(['email' => '', 'name' => '']);
     * $v->stopOnFirstFail(true);
     * $v->rule('required', ['email', 'name']);
     * $v->validate();
     * // Only one error will be collected (email)
     * ```
     * @example Collect all errors (default):
     * ```php
     * $v = new Validator(['email' => '', 'name' => '']);
     * $v->rule('required', ['email', 'name']);
     * $v->validate();
     * // Both errors will be collected
     * ```
     */
    public function stopOnFirstFail(bool $stop = true): void
    {
        $this->stopOnFirstFail = $stop;
    }

    /**
     * Enable strict mode to fail validation if extra/unexpected fields are present.
     *
     * When strict mode is enabled, the validate() method will add an error for
     * each field in the input data that does not have any validation rules defined.
     * This is useful for security-conscious applications that want to ensure no
     * unexpected data is passed through.
     *
     * @param bool $enable True to enable strict mode (default), false to disable.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Enabling strict mode:
     * ```php
     * $data = ['name' => 'John', 'email' => 'john@example.com', 'hack' => 'malicious'];
     * $v = new Validator($data);
     * $v->rule('required', ['name', 'email']);
     * $v->strict();
     *
     * if (!$v->validate()) {
     *     // Will fail because 'hack' is an unexpected field
     *     $extraFields = $v->getExtraFields(); // ['hack']
     * }
     * ```
     */
    public function strict(bool $enable = true): self
    {
        $this->strictMode = $enable;

        return $this;
    }

    /**
     * Get all field names that have validation rules defined.
     *
     * Returns a unique list of all field names for which at least one
     * validation rule has been registered.
     *
     * @return array List of field names with rules defined.
     *
     * @example Get defined fields:
     * ```php
     * $v = new Validator($data);
     * $v->rule('required', ['email', 'name']);
     * $v->rule('email', 'email');
     *
     * $v->getDefinedFields(); // Returns ['email', 'name']
     * ```
     */
    public function getDefinedFields(): array
    {
        $definedFields = [];
        foreach ($this->validations as $validation) {
            foreach ($validation['fields'] as $field) {
                // Handle dot notation - only use the root field name
                $rootField = explode('.', (string) $field)[0];
                $definedFields[$rootField] = true;
            }
        }

        return array_keys($definedFields);
    }

    /**
     * Check if there are any extra/unexpected fields in the input data.
     *
     * An "extra" field is one that exists in the input data but has no
     * validation rules defined for it.
     *
     * @return bool True if extra fields exist, false otherwise.
     *
     * @example Checking for extra fields:
     * ```php
     * $data = ['name' => 'John', 'age' => 25, 'hack' => 'value'];
     * $v = new Validator($data);
     * $v->rule('required', 'name');
     * $v->rule('integer', 'age');
     *
     * $v->hasExtraFields(); // Returns true (because of 'hack')
     * ```
     */
    public function hasExtraFields(): bool
    {
        return $this->getExtraFields() !== [];
    }

    /**
     * Get all extra/unexpected field names in the input data.
     *
     * Returns a list of field names that exist in the input data but
     * have no validation rules defined for them.
     *
     * @return array List of extra field names.
     *
     * @example Get extra field names:
     * ```php
     * $data = ['name' => 'John', 'email' => 'john@example.com', 'foo' => 'bar', 'baz' => 123];
     * $v = new Validator($data);
     * $v->rule('required', 'name');
     * $v->rule('email', 'email');
     *
     * $v->getExtraFields(); // Returns ['foo', 'baz']
     * ```
     */
    public function getExtraFields(): array
    {
        $definedFields = $this->getDefinedFields();
        $inputFields = array_keys($this->fields);

        return array_values(array_diff($inputFields, $definedFields));
    }

    /**
     * Returns all rule callbacks, the static and instance ones
     *
     * Merges instance-specific rules with global rules, with instance rules taking precedence.
     * Instance rules are placed first in the array, so they override global rules with the same name.
     *
     * @return array Associative array of rule names to callbacks.
     */
    protected function getRules(): array
    {
        return $this->ruleRegistry->getAllRules();
    }

    /**
     * Returns all rule messages, the static and instance ones
     *
     * Merges instance-specific error messages with global messages, with instance messages taking precedence.
     * Instance messages are placed first in the array, so they override global messages with the same rule name.
     *
     * @return array Associative array of rule names to error messages.
     */
    protected function getRuleMessages(): array
    {
        return $this->ruleRegistry->getAllRuleMessages();
    }

    /**
     * Determine whether a field is being validated by the given rule
     *
     * Checks if a specific validation rule has been applied to a particular field.
     * Useful for conditional validation logic (e.g., checking if 'required' or 'optional' is set).
     *
     * @param string $name The name of the rule to check for.
     * @param string $field The name of the field to check.
     *
     * @return bool True if the field has the specified rule, false otherwise.
     */
    protected function hasRule(string $name, string $field): bool
    {
        foreach ($this->validations as $validation) {
            if ($validation['rule'] === $name && in_array($field, $validation['fields'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds a new validation rule callback that is tied to the current instance only
     *
     * Registers a validation rule that is only available to this specific Validator instance.
     * Unlike addRule(), this rule won't be available to other Validator instances.
     *
     * Instance rules take precedence over global rules if they have the same name.
     *
     * @param string $name The name of the validation rule.
     * @param callable(string, mixed, array): bool $callback The validation function that returns true if valid, false otherwise.
     * @param string|null $message Optional custom error message.
     *
     * @throws InvalidArgumentException If the callback is not valid
     *
     * @example Adding instance-specific rule:
     * ```php
     * $v = new Validator($data);
     * $v->addInstanceRule('customCheck', function($field, $value) {
     *     return $value === 'expected';
     * }, 'Value must be "expected"');
     *
     * $v->rule('customCheck', 'field_name');
     * // This rule is only available to this $v instance
     * ```
     */
    public function addInstanceRule(string $name, callable $callback, ?string $message = null): void
    {
        $this->ruleRegistry->addInstanceRule($name, $callback, $message);
    }

    /**
     * Register new validation rule callback
     *
     * Registers a new global validation rule that can be used across all Validator instances.
     * The rule will be available to all validators created after registration.
     *
     * The callback signature should be: function($field, $value, $params, $fields): bool
     *
     * @param string $name The name of the validation rule (will be used with rule() method).
     * @param callable(string, mixed, array): bool $callback The validation function that returns true if valid, false otherwise.
     * @param string|null $message Optional custom error message (defaults to 'Invalid').
     *
     * @throws InvalidArgumentException If the callback is not valid
     *
     * @example Registering a custom global rule:
     * ```php
     * Validator::addRule('telephone', function($field, $value, $params) {
     *     return preg_match('/^[0-9]{10}$/', $value) === 1;
     * }, 'Must be a valid 10-digit phone number');
     *
     * // Now use it in any validator
     * $v = new Validator(['phone' => '1234567890']);
     * $v->rule('telephone', 'phone');
     * ```
     */
    public static function addRule(string $name, callable $callback, ?string $message = null): void
    {
        RuleRegistry::addGlobalRule($name, $callback, $message);
    }

    /**
     * Get a unique rule name for custom rules
     *
     * Generates a unique name for anonymous/callable validation rules to avoid name collisions.
     * Uses random integers if a name collision is detected.
     *
     * @param string|array $fields The field name(s) to base the rule name on.
     *
     * @return string A unique rule name (e.g., 'email_rule' or 'email_rule_12345').
     */
    public function getUniqueRuleName(string|array $fields): string
    {
        return $this->ruleRegistry->getUniqueRuleName($fields);
    }

    /**
     * Check if a validator exists
     *
     * Checks if a validation rule exists either as a registered rule (global or instance)
     * or as a built-in validation method on the Validator class.
     *
     * @param string $name The name of the validation rule to check.
     *
     * @return bool True if the validator exists, false otherwise.
     *
     * @example Checking for built-in rule:
     * ```php
     * $v = new Validator([]);
     * if ($v->hasValidator('email')) {
     *     // 'email' rule exists
     * }
     * ```
     */
    public function hasValidator(string $name): bool
    {
        return $this->ruleRegistry->hasValidator($name);
    }

    /**
     * Convenience method to add a single validation rule
     *
     * Adds a validation rule to one or more fields. This is the primary method for
     * defining validation rules. Supports both built-in rules and custom callable rules.
     *
     * The method supports:
     * - Built-in validation rules (e.g., 'required', 'email', 'min', 'max')
     * - Custom callable rules (closures or callbacks)
     * - Multiple fields with the same rule
     * - Nested field validation using dot notation (e.g., 'user.email')
     * - Fluent/chainable interface
     *
     * @param string|callable(string, mixed, array): bool $rule The name of the validation rule or a callable for custom validation.
     * @param string|array $fields Single field name or array of field names to validate.
     * @param mixed ...$params Optional parameters to pass to the validation rule.
     *
     * @throws InvalidArgumentException If the rule name is not registered and not a method
     *
     * @return self Returns $this for method chaining.
     *
     * @example Basic usage with chaining:
     * ```php
     * $v = new Validator($data);
     * $v->rule('required', 'email')
     *   ->rule('email', 'email')
     *   ->rule('lengthMax', 'email', 254);
     * ```
     * @example Custom callable rule:
     * ```php
     * $v->rule(function($field, $value, $params) {
     *     return $value === 'custom_value';
     * }, 'field_name');
     * ```
     */
    public function rule(string|callable $rule, string|array $fields, mixed ...$params): self
    {
        // If rule is a callable and not a named validator, add as instance rule
        if (is_callable($rule) && !(is_string($rule) && $this->hasValidator($rule))) {
            $name = $this->getUniqueRuleName($fields);
            $message = $params[0] ?? null;
            $this->addInstanceRule($name, $rule, is_string($message) ? $message : null);
            $rule = $name;
        }

        $errors = $this->getRules();
        if (!isset($errors[$rule])) {
            $ruleMethod = 'validate' . ucfirst($rule);
            if (!method_exists($this, $ruleMethod)) {
                throw new InvalidArgumentException(
                    "Rule '$rule' has not been registered with " . static::class . "::addRule().",
                );
            }
        }

        // Ensure rule has an accompanying message
        $messages = $this->getRuleMessages();
        $message = $messages[$rule] ?? self::ERROR_DEFAULT;

        // Ensure message contains field label
        if (!str_contains((string) $message, '{field}')) {
            $message = '{field} ' . $message;
        }

        $this->validations[] = [
            'rule' => $rule,
            'fields' => (array)$fields,
            'params' => $params,
            'message' => $message,
        ];

        return $this;
    }

    /**
     * Convenience method to add multiple validation rules with an array
     *
     * Allows you to define multiple validation rules at once using an array structure.
     * This is useful for defining all validation rules in a single, organized array.
     *
     * The array structure should be:
     * - Keys: Rule names (e.g., 'required', 'email', 'min')
     * - Values: Field names or arrays of field names and parameters
     *
     * @param array $rules Associative array of rules where keys are rule names and values are field configurations.
     *
     * @example Basic usage:
     * ```php
     * $v = new Validator($data);
     * $v->rules([
     *     'required' => ['email', 'password'],
     *     'email' => 'email'
     * ]);
     * ```
     * @example With parameters:
     * ```php
     * $v->rules([
     *     'required' => [['email'], ['password']],
     *     'email' => ['email'],
     *     'lengthMin' => [['password', 8]]
     * ]);
     * ```
     * @example Complex example:
     * ```php
     * $v->rules([
     *     'required' => [['username'], ['email'], ['password']],
     *     'lengthBetween' => [['username', 3, 20]],
     *     'email' => ['email'],
     *     'lengthMin' => [['password', 8]]
     * ]);
     * ```
     * @example With custom messages:
     * ```php
     * $v->rules([
     *     'required' => [
     *         ['email', 'message' => 'Email is required'],
     *         ['password', 'message' => 'Password is required']
     *     ],
     *     'email' => [
     *         ['email', 'message' => 'Please enter a valid email address']
     *     ]
     * ]);
     * ```
     */
    public function rules(array $rules): void
    {
        foreach ($rules as $ruleType => $params) {
            if (is_array($params)) {
                foreach ($params as $innerParams) {
                    if (!is_array($innerParams)) {
                        $innerParams = [$innerParams];
                    }

                    // Extract custom message if provided
                    $message = $innerParams['message'] ?? null;
                    unset($innerParams['message']);

                    array_unshift($innerParams, $ruleType);
                    $added = $this->rule(...$innerParams);

                    if ($message !== null) {
                        $added->message($message);
                    }
                }
            } else {
                $this->rule($ruleType, $params);
            }
        }
    }

    /**
     * Replace data on cloned instance
     *
     * Creates a new Validator instance (clone) with different data but the same validation rules.
     * This is useful for validating multiple datasets with the same validation rules.
     * The original validator instance remains unchanged (immutable pattern).
     *
     * @param array $data The new data to validate.
     * @param array $fields Optional field whitelist for the new data.
     *
     * @return self A new Validator instance with the new data and cloned validation rules.
     *
     * @example Validating multiple datasets with same rules:
     * ```php
     * $v = new Validator([]);
     * $v->rule('required', ['email', 'name'])
     *   ->rule('email', 'email');
     *
     * $user1 = $v->withData(['email' => 'user1@example.com', 'name' => 'User 1']);
     * $user2 = $v->withData(['email' => 'user2@example.com', 'name' => 'User 2']);
     *
     * $user1->validate(); // Validates user1 data
     * $user2->validate(); // Validates user2 data
     * ```
     */
    public function withData(array $data, array $fields = []): self
    {
        $clone = clone $this;
        $clone->fields = $fields === [] ? $data : array_intersect_key($data, array_flip($fields));
        $clone->errorManager->clearErrors();
        return $clone;
    }

    /**
     * Clone method to ensure Core services are properly cloned.
     */
    public function __clone()
    {
        // Clone the Core service instances to prevent shared state
        $this->errorManager = clone $this->errorManager;
        $this->fieldAccessor = clone $this->fieldAccessor;
        // Clone the rule registry with its instance rules preserved
        $this->ruleRegistry = $this->ruleRegistry->cloneForValidator($this);
    }

    /**
     * Map multiple validation rules to a single field
     *
     * Allows you to define multiple validation rules for a single field using an array structure.
     * Each rule can include parameters and custom error messages.
     *
     * The array structure for rules:
     * - Each element is a rule configuration
     * - First element: rule name
     * - Additional elements: rule parameters
     * - Optional 'message' key: custom error message
     *
     * @param string $field The field name to apply rules to.
     * @param array $rules Array of rule configurations.
     *
     * @example Basic field rules:
     * ```php
     * $v = new Validator($data);
     * $v->mapOneFieldToRules('email', [
     *     ['required'],
     *     ['email'],
     *     ['lengthMax', 254]
     * ]);
     * ```
     * @example With custom messages:
     * ```php
     * $v->mapOneFieldToRules('password', [
     *     ['required', 'message' => 'Password is required'],
     *     ['lengthMin', 8, 'message' => 'Password must be at least 8 characters']
     * ]);
     * ```
     */
    public function mapOneFieldToRules(string $field, array $rules): void
    {
        foreach ($rules as $rule) {
            // Rule must be an array
            $rule = (array)$rule;

            // First element is the name of the rule
            $ruleName = array_shift($rule);

            // Find a custom message, if any
            $message = null;
            if (isset($rule['message'])) {
                $message = $rule['message'];
                unset($rule['message']);
            }

            // Add the field and additional parameters to the rule
            $added = $this->rule($ruleName, $field, ...$rule);

            if ($message !== null) {
                $added->message($message);
            }
        }
    }

    /**
     * Define validation rules for multiple fields at once
     *
     * Allows you to define validation rules for multiple fields at once using a structured array.
     * This is the recommended way to define all validation rules in one place.
     *
     * The array structure:
     * - Keys: field names
     * - Values: arrays of rule configurations (see mapOneFieldToRules for format)
     *
     * @param array $rules Associative array where keys are field names and values are rule configurations.
     *
     * @return self Returns $this for method chaining.
     *
     * @example Defining rules for multiple fields:
     * ```php
     * $v = new Validator($data);
     * $v->forFields([
     *     'email' => [
     *         ['required'],
     *         ['email'],
     *         ['lengthMax', 254]
     *     ],
     *     'password' => [
     *         ['required'],
     *         ['lengthMin', 8]
     *     ],
     *     'age' => [
     *         ['numeric'],
     *         ['min', 18]
     *     ]
     * ]);
     * ```
     */
    public function forFields(array $rules): self
    {
        foreach (array_keys($rules) as $field) {
            $this->mapOneFieldToRules($field, $rules[$field]);
        }

        return $this;
    }

    /**
     * @deprecated Use forFields() instead. This method will be removed in a future version.
     * @see forFields()
     *
     * @param array $rules Associative array where keys are field names and values are rule configurations.
     */
    public function mapManyFieldsToRules(array $rules): void
    {
        $this->forFields($rules);
    }

    /**
     * Determine if validation must be executed for a specific rule
     *
     * Decides whether a validation rule should run based on:
     * - Whether the field is marked as 'optional'
     * - Whether the field is 'required'
     * - Whether the value is empty
     * - Special handling for 'requiredWith'/'requiredWithout' rules (always execute)
     *
     * @param array $validation The validation rule configuration.
     * @param string $field The field name being validated.
     * @param mixed $values The value(s) to validate.
     * @param bool $multiple Whether this is a multiple value validation.
     *
     * @return bool True if validation should execute, false to skip.
     */
    private function validationMustBeExecuted(array $validation, string $field, mixed $values, bool $multiple): bool
    {
        // Always execute requiredWith(out) rules
        if (in_array($validation['rule'], ['requiredWith', 'requiredWithout'], true)) {
            return true;
        }

        // Skip validation if field is nullable and value is null
        // (but still run the nullable and required rules themselves)
        if (!in_array($validation['rule'], ['nullable', 'required', 'accepted'], true)
            && $this->hasRule('nullable', $field)
            && is_null($values)
        ) {
            return false;
        }

        // Do not execute if the field is optional and not set
        if ($this->hasRule('optional', $field) && !isset($values)) {
            return false;
        }

        // Ignore empty input, except for required and accepted rule
        if (!$this->hasRule('required', $field) && !in_array($validation['rule'], ['required', 'accepted'], true)) {
            if ($multiple) {
                return count($values) !== 0;
            }
            return isset($values) && $values !== '';
        }

        return true;
    }

    /**
     * Run validations and return boolean result
     *
     * Executes all validation rules that have been added to the validator.
     * This is the main method that triggers the validation process.
     *
     * The method:
     * - Iterates through all defined validation rules
     * - Checks each field against its validation rules
     * - Collects all validation errors
     * - Returns true if all validations pass, false otherwise
     * - Respects the stopOnFirstFail setting
     *
     * After calling this method, use errors() to retrieve any validation errors.
     *
     * @return bool True if all validations pass, false if any validation fails.
     *
     * @example Basic validation:
     * ```php
     * $v = new Validator(['email' => 'test@example.com']);
     * $v->rule('required', 'email')->rule('email', 'email');
     *
     * if ($v->validate()) {
     *     // Validation passed
     *     $data = $v->data();
     * } else {
     *     // Validation failed
     *     $errors = $v->errors();
     * }
     * ```
     * @example With error handling:
     * ```php
     * $v = new Validator($userData);
     * $v->rules([
     *     'required' => [['email'], ['password']],
     *     'email' => ['email']
     * ]);
     *
     * if (!$v->validate()) {
     *     foreach ($v->errors() as $field => $errors) {
     *         echo "$field: " . implode(', ', $errors) . "\n";
     *     }
     * }
     * ```
     */
    public function validate(): bool
    {
        $setToBreak = false;

        foreach ($this->validations as $v) {
            foreach ($v['fields'] as $field) {
                [$values, $multiple] = $this->fieldAccessor->getPart($this->fields, explode('.', (string) $field), false);

                if (!$this->validationMustBeExecuted($v, $field, $values, $multiple)) {
                    continue;
                }

                // Callback is user-specified or assumed method on class
                $errors = $this->getRules();
                $callback = $errors[$v['rule']] ?? [$this, 'validate' . ucfirst((string) $v['rule'])];

                if (!$multiple) {
                    $values = [$values];
                } elseif (!$this->hasRule('required', $field)) {
                    $values = array_filter($values);
                }

                $result = true;
                $failedValue = null;
                $customMessage = null;
                foreach ($values as $value) {
                    $callbackResult = call_user_func($callback, $field, $value, $v['params'], $this->fields);

                    // Support custom rules returning [bool, ?string] for dynamic messages
                    if (is_array($callbackResult)) {
                        $valid = (bool) ($callbackResult[0] ?? false);
                        if (!$valid && $customMessage === null && isset($callbackResult[1]) && is_string($callbackResult[1])) {
                            $customMessage = $callbackResult[1];
                        }
                    } else {
                        $valid = (bool) $callbackResult;
                    }

                    if (!$valid && $failedValue === null) {
                        $failedValue = $value;
                    }
                    $result = $result && $valid;
                }

                if (!$result) {
                    $message = $customMessage ?? $v['message'];
                    $this->error($field, $message, $v['params'], $failedValue);
                    if ($this->stopOnFirstFail) {
                        $setToBreak = true;
                        break;
                    }
                }
            }

            if ($setToBreak) {
                break;
            }
        }

        // In strict mode, add errors for any extra/unexpected fields
        if ($this->strictMode && !$setToBreak) {
            $extraFields = $this->getExtraFields();
            foreach ($extraFields as $extraField) {
                $this->error($extraField, '{field} is not an allowed field');
                if ($this->stopOnFirstFail) {
                    break;
                }
            }
        }

        return $this->errorManager->hasNoErrors();
    }

    // ===========================================
    // Validation Methods
    // ===========================================
    // All validation methods have been extracted to traits in src/Valicomb/Validators/
    // See the following traits for validator implementations:
    // - StringValidatorsTrait: Alpha, AlphaNum, ASCII, Slug, Contains, Regex
    // - NumericValidatorsTrait: Numeric, Integer, Min, Max, Between, Boolean
    // - LengthValidatorsTrait: Length, LengthBetween, LengthMin, LengthMax, stringLength helper
    // - DateValidatorsTrait: Date, DateFormat, DateBefore, DateAfter
    // - ArrayValidatorsTrait: Array, In, NotIn, ListContains, Subset, ContainsUnique, ArrayHasKeys
    // - NetworkValidatorsTrait: IP, IPv4, IPv6, Email, EmailDNS, URL, URLActive
    // - TypeValidatorsTrait: InstanceOf, CreditCard
    // - ComparisonValidatorsTrait: Required, Equals, Different, Accepted
    // - ConditionalValidatorsTrait: Optional, RequiredWith, RequiredWithout

    // ===========================================
    // Deprecated Methods - For Backward Compatibility
    // ===========================================

    /**
     * @deprecated Use mapOneFieldToRules() instead. This method will be removed in a future version.
     * @see mapOneFieldToRules()
     *
     * @param string $field The field name to apply rules to.
     * @param array $rules Array of rule configurations.
     */
    public function mapFieldRules(string $field, array $rules): void
    {
        $this->mapOneFieldToRules($field, $rules);
    }

    /**
     * @deprecated Use mapManyFieldsToRules() instead. This method will be removed in a future version.
     * @see mapManyFieldsToRules()
     *
     * @param array $rules Associative array where keys are field names and values are rule configurations.
     */
    public function mapFieldsRules(array $rules): void
    {
        $this->mapManyFieldsToRules($rules);
    }
}
