<?php

declare(strict_types=1);

namespace Valitron;

use function array_diff;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_shift;
use function array_unique;
use function array_unshift;
use function basename;
use function bccomp;
use function call_user_func;
use function checkdnsrr;
use function count;
use function date_parse_from_format;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

use function defined;
use function dirname;
use function explode;
use function filter_var;
use function function_exists;
use function get_class;
use function idn_to_ascii;
use function implode;
use function in_array;
use function ini_get;
use function ini_set;

use InvalidArgumentException;

use function is_array;
use function is_callable;
use function is_file;
use function is_int;
use function is_null;
use function is_numeric;
use function is_object;
use function is_readable;
use function is_scalar;
use function is_string;
use function ltrim;
use function mb_check_encoding;
use function mb_detect_encoding;
use function mb_strlen;
use function method_exists;
use function parse_url;
use function preg_last_error;
use function preg_match;
use function preg_replace;
use function random_int;
use function realpath;

use RuntimeException;

use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function stripos;
use function stristr;
use function strlen;
use function strtolower;
use function strtotime;
use function substr;
use function trim;
use function ucfirst;
use function ucwords;
use function vsprintf;

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
 * @package Valitron
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
    /**
     * Default error message.
     */
    public const ERROR_DEFAULT = 'Invalid';

    /**
     * Allowed language codes for validation messages.
     */
    private const ALLOWED_LANGUAGES = [
        'ar', 'cs', 'da', 'de', 'en', 'es', 'fa', 'fi', 'fr', 'hu',
        'id', 'it', 'ja', 'nl', 'no', 'pl', 'pt', 'ru', 'sv', 'tr', 'uk', 'zh',
    ];

    /**
     * Field data to validate.
     */
    protected array $fields = [];

    /**
     * Validation errors.
     */
    protected array $errors = [];

    /**
     * Validation rules to apply.
     */
    protected array $validations = [];

    /**
     * Field labels for error messages.
     */
    protected array $labels = [];

    /**
     * Instance-specific validation rules.
     */
    protected array $instanceRules = [];

    /**
     * Instance-specific rule messages.
     */
    protected array $instanceRuleMessages = [];

    /**
     * Current language for validation messages.
     */
    protected static ?string $lang = null;

    /**
     * Language file directory.
     */
    protected static ?string $langDir = null;

    /**
     * Global validation rules.
     */
    protected static array $rules = [];

    /**
     * Global rule messages.
     */
    protected static array $ruleMessages = [];

    /**
     * Valid URL prefixes for URL validation.
     */
    protected array $validUrlPrefixes = ['http://', 'https://', 'ftp://'];

    /**
     * Whether to stop validation on first failure.
     */
    protected bool $stopOnFirstFail = false;

    /**
     * Whether to prepend field labels to error messages.
     */
    protected bool $prependLabels = true;

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
        // Filter fields if whitelist provided.
        $this->fields = !empty($fields)
            ? array_intersect_key($data, array_flip($fields))
            : $data;

        // Initialize language files.
        $this->initializeLanguage($lang, $langDir);
    }

    /**
     * Initialize and load language files securely.
     *
     * Loads validation error messages from language files with security protections
     * against path traversal attacks. The language code must be in the allowed list
     * and the language file must exist and return a valid array.
     *
     * This method:
     * - Validates the language code against a whitelist.
     * - Prevents directory traversal attacks.
     * - Verifies the language directory and file exist.
     * - Loads and merges the language messages.
     *
     * @param string|null $lang The language code to load (e.g., 'en', 'fr', 'de').
     * @param string|null $langDir The directory containing language files.
     *
     * @throws InvalidArgumentException If language code is invalid, directory doesn't exist, file is not readable, or file doesn't return an array
     */
    protected function initializeLanguage(?string $lang, ?string $langDir): void
    {
        // Determine language.
        $lang = $lang ?? static::lang();
        $lang = basename($lang); // Remove any path traversal attempts

        // Validate language against whitelist.
        if (!in_array($lang, self::ALLOWED_LANGUAGES, true)) {
            throw new InvalidArgumentException(
                "Invalid language '$lang'. Allowed: " . implode(', ', self::ALLOWED_LANGUAGES),
            );
        }

        // Determine and validate language directory.
        $langDir = $langDir ?? static::langDir();
        $originalLangDir = $langDir;

        // Resolve the real path (this will normalize .. and check existence).
        $langDir = realpath($langDir);

        if ($langDir === false) {
            // Directory doesn't exist - construct expected file path for error message.
            $langFile = $originalLangDir . '/' . $lang . '.php';
            throw new InvalidArgumentException("Fail to load language file '$langFile'");
        }

        // Build and validate file path.
        $langFile = $langDir . DIRECTORY_SEPARATOR . $lang . '.php';

        if (!is_file($langFile) || !is_readable($langFile)) {
            throw new InvalidArgumentException("Fail to load language file '$langFile'");
        }

        // Load language file.
        $langMessages = include $langFile;

        if (!is_array($langMessages)) {
            throw new InvalidArgumentException("Language file must return an array");
        }

        // Merge with existing messages using array spread.
        static::$ruleMessages = [...static::$ruleMessages, ...$langMessages];
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
        if ($lang !== null) {
            static::$lang = $lang;
        }

        return static::$lang ?? 'en';
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
        if ($dir !== null) {
            static::$langDir = $dir;
        }

        return static::$langDir ?? dirname(__DIR__, 2) . '/lang';
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
        $this->prependLabels = $prepend;
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
        if ($field !== null) {
            return $this->errors[$field] ?? false;
        }

        return $this->errors;
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
     * @param string $field The field name to add the error to.
     * @param string $message The error message (supports sprintf placeholders).
     * @param array $params Optional parameters for sprintf placeholder replacement.
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
     */
    public function error(string $field, string $message, array $params = []): void
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
                $param = get_class($param);
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

        $this->errors[$field][] = vsprintf($message, $values);
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
        $this->errors = [];
        $this->validations = [];
        $this->labels = [];
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
        $this->labels = [...$this->labels, ...$labels];

        return $this;
    }

    /**
     * Check and replace field label in message.
     *
     * Processes error messages by replacing placeholder tokens ({field}, {field1}, {field2}, etc.)
     * with actual field labels or auto-generated labels from field names.
     *
     * The method handles:
     * - {field} placeholder: replaced with the field's label or formatted field name
     * - {field1}, {field2}, etc.: replaced with labels of related fields (for comparison rules)
     * - Automatic label generation: converts 'field_name' to 'Field Name'
     * - Respect for the prependLabels setting
     *
     * @param string $field The field name being validated.
     * @param string $message The error message template with placeholders.
     * @param array $params Parameters passed to the validation rule.
     *
     * @return string The processed error message with labels substituted.
     */
    protected function checkAndSetLabel(string $field, string $message, array $params): string
    {
        if (isset($this->labels[$field])) {
            $message = str_replace('{field}', $this->labels[$field], $message);

            if (is_array($params)) {
                $i = 1;
                foreach ($params as $k => $v) {
                    $tag = '{field' . $i . '}';
                    $label = isset($params[$k]) && (is_numeric($params[$k]) || is_string($params[$k])) && isset($this->labels[$params[$k]])
                        ? $this->labels[$params[$k]]
                        : $tag;
                    $message = str_replace($tag, $label, $message);
                    $i++;
                }
            }
        } else {
            $message = $this->prependLabels
                ? str_replace('{field}', ucwords(str_replace('_', ' ', $field)), $message)
                : str_replace('{field} ', '', $message);
        }

        return $message;
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
     * Returns all rule callbacks, the static and instance ones
     *
     * Merges instance-specific rules with global rules, with instance rules taking precedence.
     * Instance rules are placed first in the array, so they override global rules with the same name.
     *
     * @return array Associative array of rule names to callbacks.
     */
    protected function getRules(): array
    {
        return [...$this->instanceRules, ...static::$rules];
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
        return [...$this->instanceRuleMessages, ...static::$ruleMessages];
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
     * Validate rule callback
     *
     * Verifies that a given callback is actually callable. Used internally to validate
     * callbacks before registering them as validation rules.
     *
     * @param callable $callback The callback to validate.
     *
     * @throws InvalidArgumentException If the callback is not callable
     */
    protected static function assertRuleCallback(callable $callback): void
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException(
                'Second argument must be a valid callback. Given argument was not callable.',
            );
        }
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
     * @param callable $callback The validation function that returns true if valid, false otherwise.
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
        static::assertRuleCallback($callback);

        $this->instanceRules[$name] = $callback;
        $this->instanceRuleMessages[$name] = $message;
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
     * @param callable $callback The validation function that returns true if valid, false otherwise.
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
        if ($message === null) {
            $message = static::ERROR_DEFAULT;
        }

        static::assertRuleCallback($callback);

        static::$rules[$name] = $callback;
        static::$ruleMessages[$name] = $message;
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
        if (is_array($fields)) {
            $fields = implode("_", $fields);
        }

        $orgName = "{$fields}_rule";
        $name = $orgName;
        $rules = $this->getRules();

        while (isset($rules[$name])) {
            $name = $orgName . "_" . random_int(0, 99999);
        }

        return $name;
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
        $rules = $this->getRules();
        return method_exists($this, "validate" . ucfirst($name))
            || isset($rules[$name]);
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
     * @param string|callable $rule The name of the validation rule or a callable for custom validation.
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
        if (!str_contains($message, '{field}')) {
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
     */
    public function rules(array $rules): void
    {
        foreach ($rules as $ruleType => $params) {
            if (is_array($params)) {
                foreach ($params as $innerParams) {
                    if (!is_array($innerParams)) {
                        $innerParams = [$innerParams];
                    }
                    array_unshift($innerParams, $ruleType);
                    $this->rule(...$innerParams);
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
        $clone->fields = !empty($fields) ? array_intersect_key($data, array_flip($fields)) : $data;
        $clone->errors = [];
        return $clone;
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
     * Map validation rules to multiple fields at once
     *
     * Allows you to define validation rules for multiple fields at once using a structured array.
     * This is the most organized way to define all validation rules in one place.
     *
     * The array structure:
     * - Keys: field names
     * - Values: arrays of rule configurations (see mapOneFieldToRules for format)
     *
     * @param array $rules Associative array where keys are field names and values are rule configurations.
     *
     * @example Mapping rules for multiple fields:
     * ```php
     * $v = new Validator($data);
     * $v->mapManyFieldsToRules([
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
    public function mapManyFieldsToRules(array $rules): void
    {
        foreach (array_keys($rules) as $field) {
            $this->mapOneFieldToRules($field, $rules[$field]);
        }
    }

    /**
     * Check if an array is associative
     *
     * Determines if an array has at least one string key (associative array) or only integer keys (indexed array).
     * Used internally to handle array parameters correctly in validation rules.
     *
     * @param array $input The array to check.
     *
     * @return bool True if array has at least one string key, false if all keys are integers.
     */
    private function isAssociativeArray(array $input): bool
    {
        // Array contains at least one key that's not an integer or can't be cast to an integer
        return count(array_filter(array_keys($input), 'is_string')) > 0;
    }

    /**
     * Get part of the data array (supports nested arrays with dot notation)
     *
     * Navigates nested array structures using an array of identifiers (from dot notation parsing).
     * Supports wildcard matching (*) for array iteration. Returns a tuple of [value, isMultiple].
     *
     * The method handles:
     * - Nested field access: 'user.profile.email' => ['user', 'profile', 'email']
     * - Wildcard matching: 'users.*.email' matches all emails in users array
     * - Key existence checking when allowEmpty is true
     *
     * @param mixed $data The data array to navigate.
     * @param array $identifiers Array of keys to navigate through (e.g., ['user', 'email']).
     * @param bool $allowEmpty Whether to check for key existence even if value is empty.
     *
     * @return array Tuple: [0] => mixed $value (the found value or null), [1] => bool $isMultiple (true if wildcard used).
     */
    protected function getPart(mixed $data, array $identifiers, bool $allowEmpty = false): array
    {
        // Catches the case where the field is an array of discrete values
        if (count($identifiers) === 0) {
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
        if (count($identifiers) === 0) {
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
                [$values, $multiple] = $this->getPart($this->fields, explode('.', $field), false);

                if (!$this->validationMustBeExecuted($v, $field, $values, $multiple)) {
                    continue;
                }

                // Callback is user-specified or assumed method on class
                $errors = $this->getRules();
                if (isset($errors[$v['rule']])) {
                    $callback = $errors[$v['rule']];
                } else {
                    $callback = [$this, 'validate' . ucfirst($v['rule'])];
                }

                if (!$multiple) {
                    $values = [$values];
                } elseif (!$this->hasRule('required', $field)) {
                    $values = array_filter($values);
                }

                $result = true;
                foreach ($values as $value) {
                    $result = $result && call_user_func($callback, $field, $value, $v['params'], $this->fields);
                }

                if (!$result) {
                    $this->error($field, $v['message'], $v['params']);
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

        return count($this->errors) === 0;
    }

    // ===========================================
    // Validation Methods - Start
    // ===========================================

    /**
     * Required field validator
     *
     * Validates that a field is present and not empty. A field is considered empty if it is:
     * - null
     * - An empty string
     * - A string containing only whitespace
     *
     * Optional first parameter can enable strict key existence check (field must exist in data).
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Optional parameters: [0] => bool $checkKeyExists (default: false).
     *
     * @return bool True if field has a value, false if empty or missing.
     */
    protected function validateRequired(string $field, mixed $value, array $params = []): bool
    {
        if (isset($params[0]) && (bool)$params[0]) {
            $find = $this->getPart($this->fields, explode('.', $field), true);
            return $find[1];
        }

        if (is_null($value) || (is_string($value) && trim($value) === '')) {
            return false;
        }

        return true;
    }

    /**
     * Validate that two values match
     *
     * Compares the value of one field with another field using strict comparison (===).
     * This prevents type juggling attacks and ensures both value and type match.
     * Supports nested fields using dot notation.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string $fieldToCompare (field name to compare against).
     *
     * @return bool True if values match exactly (value and type), false otherwise.
     */
    protected function validateEquals(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_string($params[0])) {
            throw new InvalidArgumentException('Field name required for equals validation');
        }

        // Extract the second field value, this accounts for nested array values
        [$field2Value, $multiple] = $this->getPart($this->fields, explode('.', $params[0]));

        // Use strict comparison to prevent type juggling attacks
        return isset($field2Value) && $value === $field2Value;
    }

    /**
     * Validate that a field is different from another field
     *
     * Ensures two fields have different values using strict comparison (!==).
     * Supports nested fields using dot notation.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => string $fieldToCompare.
     *
     * @return bool True if values are different, false if they match.
     */
    protected function validateDifferent(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0]) || !is_string($params[0])) {
            throw new InvalidArgumentException('Field name required for different validation');
        }

        // Extract the second field value, this accounts for nested array values
        [$field2Value, $multiple] = $this->getPart($this->fields, explode('.', $params[0]));

        // Use strict comparison to prevent type juggling attacks
        return isset($field2Value) && $value !== $field2Value;
    }

    /**
     * Validate that a field was "accepted"
     *
     * Validates that a field value represents user acceptance (e.g., checkbox, terms agreement).
     * This validation rule implies the field is "required".
     * Acceptable values: 'yes', 'on', 1, '1', true
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value represents acceptance, false otherwise.
     */
    protected function validateAccepted(string $field, mixed $value): bool
    {
        $acceptable = ['yes', 'on', 1, '1', true];

        return $this->validateRequired($field, $value) && in_array($value, $acceptable, true);
    }

    /**
     * Validate that a field is an array
     *
     * Checks if the value is of array type. This is useful for validating
     * structured data, lists, or nested objects that should be arrays.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is an array, false otherwise.
     */
    protected function validateArray(string $field, mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Validate that a field is numeric
     *
     * Validates that a value is numeric, accepting integers, floats, and numeric strings.
     * Uses PHP's is_numeric() function which accepts formats like: "123", "123.45", "-123", "1.23e4".
     *
     * This is more permissive than validateInteger() as it accepts decimal values.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is numeric, false otherwise.
     */
    protected function validateNumeric(string $field, mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Validate that a field is an integer
     *
     * Validates integer values with optional strict mode. In strict mode, rejects strings with
     * leading zeros (except "0" itself) to prevent octal interpretation issues.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => bool $strict (default: false).
     *
     * @return bool True if value is a valid integer, false otherwise.
     */
    protected function validateInteger(string $field, mixed $value, array $params): bool
    {
        $strict = isset($params[0]) && (bool)$params[0];

        if ($strict) {
            // Strict mode: reject strings with leading zeros (except "0" itself)
            // but accept native integers
            if (is_int($value)) {
                return true;
            }

            if (!is_string($value)) {
                return false;
            }

            // Fixed regex: matches 0, or optional negative sign followed by 1-9 then any digits
            return preg_match('/^(0|-?[1-9][0-9]*)$/', $value) === 1;
        }

        // Non-strict: also accept actual integers and numeric strings
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate the length of a string
     *
     * Validates that a string has an exact character length or falls within a length range.
     * Uses multibyte-safe character counting via stringLength() helper, which uses mb_strlen()
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

        // Length same
        return $length !== false && $length === $params[0];
    }

    /**
     * Validate the length of a string (between)
     *
     * Validates that a string's character length falls within a specified range (inclusive).
     * Uses multibyte-safe character counting via stringLength() helper.
     *
     * Both the minimum and maximum bounds are inclusive, meaning a string with length
     * equal to either bound will pass validation. For example, with params [3, 5]:
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
     * The minimum bound is inclusive, meaning a string with length equal to the minimum
     * will pass validation. For example, with param [5]:
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
     * The maximum bound is inclusive, meaning a string with length equal to the maximum
     * will pass validation. For example, with param [5]:
     * - "test" (length 4) passes
     * - "tests" (length 5) passes
     * - "testing" (length 7) fails
     *
     * This is useful for enforcing input limits in database columns, preventing
     * excessively long submissions, or limiting display text length.
     *
     * Important: Counts characters, not bytes. Non-string values return false.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be a string).
     * @param array $params Maximum length constraint: [0] => maximum character length.
     *
     * @return bool True if string length is at most the maximum, false otherwise or if value is not a string.
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

    /**
     * Validate the size of a field is greater than a minimum value
     *
     * Validates that a numeric value is greater than or equal to a specified minimum threshold.
     * Uses high-precision decimal comparison via bccomp() when available (from bcmath extension),
     * otherwise falls back to standard PHP comparison operators.
     *
     * The minimum bound is inclusive, meaning a value equal to the minimum passes validation.
     * For example, with param [5]:
     * - 4.99 fails
     * - 5 passes
     * - 5.01 passes
     *
     * The bccomp() function provides 14 decimal places of precision, making this suitable for
     * financial calculations, scientific data, or any scenario requiring precise decimal handling.
     * Non-numeric values (strings, arrays, objects) are rejected.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be numeric).
     * @param array $params Minimum threshold: [0] => minimum numeric value.
     *
     * @return bool True if value is numeric and >= minimum, false otherwise.
     */
    protected function validateMin(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (function_exists('bccomp')) {
            return !(bccomp((string)$params[0], (string)$value, 14) === 1);
        }

        return $params[0] <= $value;
    }

    /**
     * Validate the size of a field is less than a maximum value
     *
     * Validates that a numeric value is less than or equal to a specified maximum threshold.
     * Uses high-precision decimal comparison via bccomp() when available (from bcmath extension),
     * otherwise falls back to standard PHP comparison operators.
     *
     * The maximum bound is inclusive, meaning a value equal to the maximum passes validation.
     * For example, with param [10]:
     * - 9.99 passes
     * - 10 passes
     * - 10.01 fails
     *
     * The bccomp() function provides 14 decimal places of precision, making this suitable for
     * financial calculations, scientific data, or any scenario requiring precise decimal handling.
     * Non-numeric values (strings, arrays, objects) are rejected.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be numeric).
     * @param array $params Maximum threshold: [0] => maximum numeric value.
     *
     * @return bool True if value is numeric and <= maximum, false otherwise.
     */
    protected function validateMax(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (function_exists('bccomp')) {
            return !(bccomp((string)$value, (string)$params[0], 14) === 1);
        }

        return $params[0] >= $value;
    }

    /**
     * Validate the size of a field is between min and max values
     *
     * Validates that a numeric value falls within a specified range (inclusive on both ends).
     * Internally delegates to validateMin() and validateMax(), inheriting their high-precision
     * decimal comparison capabilities via bccomp() when available.
     *
     * Both bounds are inclusive, meaning values equal to either the minimum or maximum pass
     * validation. For example, with param [[5, 10]]:
     * - 4.99 fails
     * - 5 passes
     * - 7.5 passes
     * - 10 passes
     * - 10.01 fails
     *
     * Important: This method has a unique parameter structure - the first parameter must be
     * an array containing exactly two elements: [min, max]. Invalid parameter structures
     * (missing array, wrong element count) will cause validation to fail.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be numeric).
     * @param array $params Range constraint: [0] => [minimum, maximum] (must be 2-element array).
     *
     * @return bool True if value is numeric and between min/max (inclusive), false otherwise or if params invalid.
     */
    protected function validateBetween(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (!isset($params[0]) || !is_array($params[0]) || count($params[0]) !== 2) {
            return false;
        }

        [$min, $max] = $params[0];

        return $this->validateMin($field, $value, [$min]) && $this->validateMax($field, $value, [$max]);
    }

    /**
     * Validate a field is contained within a list of values
     *
     * Validates that a value exists in a specified list of allowed values. Supports both
     * indexed and associative arrays, with options for strict type comparison.
     *
     * When validating against associative arrays (or forced with param [2] = true), only
     * the array keys are checked against, not the values. This is useful for validating
     * enum-like structures where keys represent valid options.
     *
     * Strict mode (param [1] = true): Uses === comparison, requiring exact type match
     * Non-strict mode (default): Uses == comparison, allowing type coercion (e.g., "1" == 1)
     *
     * Examples:
     * - validateIn('status', 'active', [['active', 'pending'], false]) → true
     * - validateIn('status', 'deleted', [['active', 'pending'], false]) → false
     * - validateIn('id', '5', [[1, 2, 5], true]) → false (strict: string !== int)
     * - validateIn('id', '5', [[1, 2, 5], false]) → true (non-strict: "5" == 5)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => array of allowed values, [1] => bool strict (default: false), [2] => bool forceAsAssociative (default: false).
     *
     * @return bool True if value exists in the allowed list, false otherwise.
     */
    protected function validateIn(string $field, mixed $value, array $params): bool
    {
        $forceAsAssociative = false;
        if (isset($params[2])) {
            $forceAsAssociative = (bool) $params[2];
        }

        if ($forceAsAssociative || $this->isAssociativeArray($params[0])) {
            $params[0] = array_keys($params[0]);
        }

        $strict = $params[1] ?? false;

        return in_array($value, $params[0], $strict);
    }

    /**
     * Validate a list contains a value
     *
     * Validates that an array field contains a specific value. This is the inverse of validateIn() -
     * instead of checking if a value is in a predefined list, it checks if a predefined value
     * exists in the field's array.
     *
     * When the field value is an associative array (or forced with param [2] = true), only
     * the array keys are checked, not the values. This allows validating that specific keys
     * exist in a submitted data structure.
     *
     * Strict mode (param [1] = true): Uses === comparison, requiring exact type match
     * Non-strict mode (default): Uses == comparison, allowing type coercion
     *
     * Examples:
     * - validateListContains('tags', ['php', 'mysql'], ['php', false]) → true
     * - validateListContains('tags', ['javascript', 'python'], ['php', false]) → false
     * - validateListContains('ids', [1, 2, 3], ['3', true]) → false (strict: int !== string)
     * - validateListContains('ids', [1, 2, 3], ['3', false]) → true (non-strict: 3 == "3")
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be an array).
     * @param array $params Parameters: [0] => value to search for, [1] => bool strict (default: false), [2] => bool forceAsAssociative (default: false).
     *
     * @return bool True if the list contains the specified value, false otherwise.
     */
    protected function validateListContains(string $field, mixed $value, array $params): bool
    {
        $forceAsAssociative = false;
        if (isset($params[2])) {
            $forceAsAssociative = (bool) $params[2];
        }

        if ($forceAsAssociative || $this->isAssociativeArray($value)) {
            $value = array_keys($value);
        }

        $strict = $params[1] ?? false;

        return in_array($params[0], $value, $strict);
    }

    /**
     * Validate a field is not contained within a list of values
     *
     * Validates that a value does NOT exist in a specified list of disallowed values.
     * This is the inverse of validateIn(), delegating to it and negating the result.
     *
     * Inherits all behavior from validateIn() including:
     * - Associative array handling (checks keys only)
     * - Strict/non-strict comparison modes
     * - Force associative parameter
     *
     * This is useful for blacklisting values, preventing reserved keywords, or excluding
     * specific inputs that should not be accepted.
     *
     * Examples:
     * - validateNotIn('username', 'guest', [['admin', 'root', 'guest'], false]) → false
     * - validateNotIn('username', 'john', [['admin', 'root', 'guest'], false]) → true
     * - validateNotIn('id', 0, [[0, -1], true]) → false (strict match)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Parameters: [0] => array of disallowed values, [1] => bool strict (default: false), [2] => bool forceAsAssociative (default: false).
     *
     * @return bool True if value does NOT exist in the disallowed list, false if it does.
     */
    protected function validateNotIn(string $field, mixed $value, array $params): bool
    {
        return !$this->validateIn($field, $value, $params);
    }

    /**
     * Validate a field contains a given string
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
     * Validate that all field values are contained in a given array
     *
     * Validates that all elements in the field's array are contained within a list of allowed values,
     * or that a scalar value exists in the allowed list. This ensures submitted data only contains
     * whitelisted values.
     *
     * Behavior varies based on the field value type:
     * - Scalar or null: Validates the value exists in the allowed list (non-strict comparison)
     * - Array: Validates ALL elements exist in the allowed list using array_diff()
     * - Other types (objects, resources): Returns false
     *
     * Parameter handling is flexible:
     * - If params[0] is an array, it's used as the allowed values list
     * - Otherwise, the entire params array is treated as the allowed values list
     *
     * Examples:
     * - validateSubset('tags', ['php', 'mysql'], [['php', 'mysql', 'js']]) → true
     * - validateSubset('tags', ['php', 'rust'], [['php', 'mysql', 'js']]) → false ('rust' not allowed)
     * - validateSubset('color', 'red', [['red', 'blue', 'green']]) → true
     * - validateSubset('color', 'purple', [['red', 'blue', 'green']]) → false
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (scalar, null, or array).
     * @param array $params Allowed values: [0] => array of allowed values, OR entire params is the allowed list.
     *
     * @return bool True if all values are in the allowed list, false otherwise.
     */
    protected function validateSubset(string $field, mixed $value, array $params): bool
    {
        if (!isset($params[0])) {
            return false;
        }

        // Handle scalar parameter - wrap in array
        $allowedValues = is_array($params[0]) ? $params[0] : $params;

        if (is_scalar($value) || is_null($value)) {
            // For scalar values, check if it's in the allowed list
            return in_array($value, $allowedValues, false);
        }

        if (!is_array($value)) {
            return false;
        }

        // Check if all elements in $value exist in $allowedValues
        return count(array_diff($value, $allowedValues)) === 0;
    }

    /**
     * Validate that field array has only unique values
     *
     * Validates that an array contains no duplicate values by comparing the original array
     * with its deduplicated version via array_unique(). Uses SORT_REGULAR flag for comparison,
     * which performs standard comparisons (similar to == operator).
     *
     * This validation is useful for ensuring data integrity in scenarios like:
     * - Preventing duplicate tags, categories, or options
     * - Validating unique identifiers in a list
     * - Ensuring one-to-many relationships don't have duplicates
     *
     * Comparison behavior with SORT_REGULAR:
     * - Numbers: 1 == "1" (type coercion applies)
     * - Strings: Case-sensitive comparison
     * - Arrays/Objects: Compared by reference and structure
     *
     * Examples:
     * - validateContainsUnique('tags', ['php', 'mysql', 'javascript']) → true
     * - validateContainsUnique('tags', ['php', 'mysql', 'php']) → false (duplicate 'php')
     * - validateContainsUnique('ids', [1, 2, 3]) → true
     * - validateContainsUnique('ids', [1, 2, 1]) → false (duplicate 1)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be an array).
     *
     * @return bool True if array contains only unique values, false otherwise or if not an array.
     */
    protected function validateContainsUnique(string $field, mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Compare counts instead of arrays to avoid key ordering issues
        return count($value) === count(array_unique($value, SORT_REGULAR));
    }

    /**
     * Validate that a field is a valid IP address
     *
     * Validates that a value is a well-formed IP address, accepting both IPv4 and IPv6 formats.
     * Uses PHP's FILTER_VALIDATE_IP to ensure compliance with IP address standards.
     *
     * Accepted formats:
     * - IPv4: Standard dotted decimal notation (e.g., "192.168.1.1", "127.0.0.1")
     * - IPv6: Standard hexadecimal notation (e.g., "2001:0db8:85a3::8a2e:0370:7334", "::1")
     * - IPv6 compressed: Short notation (e.g., "::1", "fe80::1")
     *
     * This validation is purely syntactic - it does NOT verify if the IP address is:
     * - Reachable or active on a network
     * - A public vs private address
     * - Within a specific subnet or range
     *
     * For version-specific validation, use validateIpv4() or validateIpv6().
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is a valid IPv4 or IPv6 address, false otherwise.
     */
    protected function validateIp(string $field, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate that a field is a valid IP v4 address
     *
     * Validates that a value is a well-formed IPv4 address in dotted decimal notation.
     * Uses PHP's FILTER_VALIDATE_IP with FILTER_FLAG_IPV4 flag to enforce IPv4-only validation.
     *
     * Accepted format: Four decimal octets separated by dots, each ranging from 0-255.
     * Examples of valid IPv4 addresses:
     * - "192.168.1.1" (private network)
     * - "127.0.0.1" (localhost)
     * - "8.8.8.8" (public DNS)
     * - "0.0.0.0" (any address)
     *
     * Rejected:
     * - IPv6 addresses (even IPv4-mapped IPv6 like "::ffff:192.168.1.1")
     * - Malformed addresses ("192.168.1", "192.168.1.256")
     * - Hostnames or domain names
     *
     * This validation is purely syntactic - it does NOT verify network reachability.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is a valid IPv4 address, false otherwise.
     */
    protected function validateIpv4(string $field, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate that a field is a valid IP v6 address
     *
     * Validates that a value is a well-formed IPv6 address in hexadecimal notation.
     * Uses PHP's FILTER_VALIDATE_IP with FILTER_FLAG_IPV6 flag to enforce IPv6-only validation.
     *
     * Accepted formats: Standard and compressed IPv6 notation
     * Examples of valid IPv6 addresses:
     * - "2001:0db8:85a3:0000:0000:8a2e:0370:7334" (full notation)
     * - "2001:db8:85a3::8a2e:370:7334" (compressed - zero groups omitted)
     * - "::1" (localhost)
     * - "fe80::1" (link-local)
     * - "::" (all zeros)
     * - "::ffff:192.168.1.1" (IPv4-mapped IPv6)
     *
     * Rejected:
     * - IPv4 addresses (use validateIpv4() instead)
     * - Malformed addresses ("gggg::1", "::::::1")
     * - Hostnames or domain names
     *
     * This validation is purely syntactic - it does NOT verify network reachability.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is a valid IPv6 address, false otherwise.
     */
    protected function validateIpv6(string $field, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Validate that a field is a valid e-mail address
     *
     * Performs comprehensive email validation including:
     * - RFC 5321 length restrictions (max 254 chars total, 64 for local, 255 for domain)
     * - PHP filter_var validation
     * - Dangerous character rejection (prevents XSS/injection)
     * - Local and domain part validation
     * - Control character and null byte rejection
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if valid email address, false otherwise.
     */
    protected function validateEmail(string $field, mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Length check (RFC 5321)
        if (strlen($value) > 254) {
            return false;
        }

        // Basic format validation
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        // Reject dangerous characters (XSS/injection prevention)
        if (preg_match('/[<>"\'\(\)\[\]\\\\]/', $value)) {
            return false;
        }

        // Reject control characters and null bytes
        if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return false;
        }

        // Validate local and domain parts separately
        $parts = explode('@', $value, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$local, $domain] = $parts;

        if (strlen($local) > 64 || strlen($domain) > 255) {
            return false;
        }

        // Reject consecutive dots in local part
        if (str_contains($local, '..')) {
            return false;
        }

        // Reject leading/trailing dots in local part
        if (str_starts_with($local, '.') || str_ends_with($local, '.')) {
            return false;
        }

        return true;
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
     * Validate that a field is a valid e-mail address and the domain name is active
     *
     * Performs comprehensive email validation including syntax checking via validateEmail()
     * and DNS verification to ensure the domain has valid MX (Mail Exchange) records.
     * This provides stronger validation than syntax-only checking by confirming the domain
     * can actually receive emails.
     *
     * Validation steps:
     * 1. Validates email syntax using validateEmail() (RFC 5321 compliance, length limits, etc.)
     * 2. Extracts the domain portion after the @ symbol
     * 3. Converts internationalized domain names (IDN) to ASCII using idn_to_ascii() if available
     * 4. Checks for MX DNS records using checkdnsrr()
     *
     * Internationalized domain support:
     * - If intl extension is available, handles domains like "user@例え.jp"
     * - Converts Unicode domains to Punycode for DNS lookup
     * - Falls back to direct lookup if intl extension is not available
     *
     * Important: This method performs network DNS queries, which may:
     * - Add latency to validation (typically 10-100ms)
     * - Fail in environments without DNS access
     * - Produce false negatives if DNS is temporarily unavailable
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if email is syntactically valid AND domain has MX records, false otherwise.
     */
    protected function validateEmailDNS(string $field, mixed $value): bool
    {
        if (!$this->validateEmail($field, $value)) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        $domainPart = stristr($value, '@');
        if ($domainPart === false) {
            return false;
        }

        $domain = ltrim($domainPart, '@') . '.';
        if (function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46')) {
            $asciiDomain = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
            if ($asciiDomain === false) {
                return false;
            }
            $domain = $asciiDomain;
        }

        return checkdnsrr($domain, 'MX');
    }

    /**
     * Validate that a field is a valid URL by syntax
     *
     * Validates that a value is a syntactically correct URL with a valid protocol prefix.
     * Uses PHP's FILTER_VALIDATE_URL for syntax validation, but only accepts URLs that
     * start with an allowed protocol from the validUrlPrefixes property.
     *
     * Default allowed prefixes (configurable via validUrlPrefixes property):
     * - http://
     * - https://
     * - ftp://
     *
     * Validation checks:
     * - URL must start with one of the valid prefixes
     * - URL must pass PHP's filter_var() FILTER_VALIDATE_URL check
     * - URL structure must be well-formed (scheme, host, optional path/query)
     *
     * This validation is purely syntactic - it does NOT verify if the URL:
     * - Is reachable or returns a valid HTTP response
     * - Points to an existing resource
     * - Has valid DNS records
     *
     * For active URL verification, use validateUrlActive().
     *
     * Examples:
     * - "https://example.com" → true
     * - "http://localhost:8080/path" → true
     * - "javascript:alert(1)" → false (invalid prefix)
     * - "www.example.com" → false (missing scheme)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is a valid URL with allowed prefix, false otherwise.
     */
    protected function validateUrl(string $field, mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check if URL starts with valid prefix (FIXED: using str_starts_with)
        foreach ($this->validUrlPrefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            }
        }

        return false;
    }

    /**
     * Validate that a field is an active URL by verifying DNS record
     *
     * Validates that a URL is syntactically correct AND has active DNS records, indicating
     * the domain likely exists and can be reached. This provides stronger validation than
     * syntax-only checking by confirming DNS resolution.
     *
     * Validation steps:
     * 1. Checks URL starts with an allowed prefix (http://, https://, ftp://)
     * 2. Extracts the hostname from the URL
     * 3. Checks for DNS records (A, AAAA, or CNAME records)
     *
     * DNS record types checked:
     * - A records: IPv4 address mapping
     * - AAAA records: IPv6 address mapping
     * - CNAME records: Canonical name (alias) records
     *
     * This validation does NOT:
     * - Actually connect to the URL or verify HTTP response
     * - Check if the specific path/resource exists
     * - Validate SSL certificates
     * - Verify the service is responding on the expected port
     *
     * Important: This method performs network DNS queries, which may:
     * - Add latency to validation (typically 10-100ms)
     * - Fail in environments without DNS access
     * - Produce false negatives if DNS is temporarily unavailable
     * - Not detect if a web server is down (DNS may resolve but server offline)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if URL is syntactically valid AND has DNS records, false otherwise.
     */
    protected function validateUrlActive(string $field, mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check if URL starts with valid prefix (FIXED: using str_starts_with)
        foreach ($this->validUrlPrefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                $host = parse_url(strtolower($value), PHP_URL_HOST);

                if ($host === null || $host === false) {
                    return false;
                }

                return checkdnsrr($host, 'A')
                    || checkdnsrr($host, 'AAAA')
                    || checkdnsrr($host, 'CNAME');
            }
        }

        return false;
    }

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

    /**
     * Validate that a field contains a boolean
     *
     * Validates that a value represents a boolean using strict type checking.
     * Only accepts actual booleans, integers 1/0, and string representations '1'/'0'.
     * This is stricter than PHP's native boolean casting to prevent unexpected type coercion.
     *
     * Accepted values: true, false, 1, 0, '1', '0'
     * Rejected values: 'true', 'false', 'yes', 'no', 2, -1, etc.
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if value is a valid boolean representation, false otherwise.
     */
    protected function validateBoolean(string $field, mixed $value): bool
    {
        // Only accept actual booleans, integers 1/0, and strings '1'/'0'
        return in_array($value, [true, false, 1, 0, '1', '0'], true);
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
        $cards = null;
        $cardType = null;

        /**
         * If there has been an array of valid cards supplied, or the name of the users card
         * or the name and an array of valid cards
         */
        if (!empty($params)) {
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
        $numberIsValid = function () use ($value) {
            $number = preg_replace('/[^0-9]+/', '', (string)$value);
            if (!is_string($number) || $number === '') {
                return false;
            }

            $sum = 0;

            $strlen = strlen($number);

            // Check length bounds (FIXED: added max length)
            if ($strlen < 13 || $strlen > 19) {
                return false;
            }

            for ($i = 0; $i < $strlen; $i++) {
                $digit = (int)substr($number, $strlen - $i - 1, 1);
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
                'visa' => '#^4[0-9]{12}(?:[0-9]{3})?$#',
                'mastercard' => '#^(5[1-5]|2[2-7])[0-9]{14}$#',
                'amex' => '#^3[47][0-9]{13}$#',
                'dinersclub' => '#^3(?:0[0-5]|[68][0-9])[0-9]{11}$#',
                'discover' => '#^6(?:011|5[0-9]{2})[0-9]{12}$#',
            ];

            if ($cardType !== null) {
                // If we don't have any valid cards specified and the card we've been given isn't in our regex array
                if ($cards === null && !in_array($cardType, array_keys($cardRegex), true)) {
                    return false;
                }

                // We only need to test against one card type
                return preg_match($cardRegex[$cardType], (string)$value) === 1;
            }

            // If we have cards, check our users card against only the ones we have
            if ($cards !== null) {
                foreach ($cards as $card) {
                    if (in_array($card, array_keys($cardRegex), true) && preg_match($cardRegex[$card], (string)$value) === 1) {
                        // If the card is valid, we want to stop looping
                        return true;
                    }
                }
                // None of the specified cards matched
                return false;
            }
        }

        // If we've got this far, the card has passed no validation so it's invalid
        return false;
    }

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
                [$requiredFieldValue, $multiple] = $this->getPart($fields, explode('.', $requiredField));
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
        if ($conditionallyReq && (is_null($value) || (is_string($value) && trim($value) === ''))) {
            return false;
        }

        return true;
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
                [$requiredFieldValue, $multiple] = $this->getPart($fields, explode('.', $requiredField));
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
        if ($conditionallyReq && (is_null($value) || (is_string($value) && trim($value) === ''))) {
            return false;
        }

        return true;
    }

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
     * Validate array has specified keys
     *
     * Validates that an array contains all required keys specified in the parameters.
     * Uses array_key_exists() to check for key presence, which returns true even if
     * the key's value is null.
     *
     * This validation is useful for:
     * - Ensuring API request payloads have required fields
     * - Validating configuration arrays have necessary keys
     * - Checking structured data integrity
     * - Enforcing data contracts for associative arrays
     *
     * Validation checks:
     * 1. Value must be an array
     * 2. params[0] must be provided and be a non-empty array
     * 3. Each required key name must be a string or integer
     * 4. All specified keys must exist in the value array
     *
     * Important: This checks for KEY existence, not value presence:
     * - ['name' => null] HAS key 'name' (passes validation)
     * - ['name' => ''] HAS key 'name' (passes validation)
     * - [] does NOT have key 'name' (fails validation)
     *
     * To validate both key existence AND non-empty values, combine with other rules.
     *
     * Examples:
     * - validateArrayHasKeys('data', ['name' => 'John'], [['name']]) → true
     * - validateArrayHasKeys('data', ['name' => null], [['name']]) → true (key exists)
     * - validateArrayHasKeys('data', ['email' => 'a@b.c'], [['name']]) → false (missing key)
     * - validateArrayHasKeys('data', ['a' => 1, 'b' => 2], [['a', 'b', 'c']]) → false (missing 'c')
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate (should be an array).
     * @param array $params Required keys: [0] => array of key names (strings or integers).
     *
     * @return bool True if value is an array containing all required keys, false otherwise.
     */
    protected function validateArrayHasKeys(string $field, mixed $value, array $params): bool
    {
        if (!is_array($value) || !isset($params[0])) {
            return false;
        }

        $requiredFields = $params[0];
        if (!is_array($requiredFields) || count($requiredFields) === 0) {
            return false;
        }

        foreach ($requiredFields as $fieldName) {
            // Only check valid array keys (string or int)
            if (!is_string($fieldName) && !is_int($fieldName)) {
                return false;
            }

            if (!array_key_exists($fieldName, $value)) {
                return false;
            }
        }

        return true;
    }

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
