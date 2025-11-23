<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Core;

use function implode;

use InvalidArgumentException;

use function is_array;
use function method_exists;
use function random_int;
use function ucfirst;

/**
 * Rule Registry
 *
 * Manages registration and storage of validation rules, both global and instance-specific.
 * Handles rule callbacks, messages, and rule existence checking.
 *
 * @package Valicomb\Core
 *
 * @internal
 */
class RuleRegistry
{
    /**
     * Default error message.
     */
    public const ERROR_DEFAULT = 'Invalid';

    /**
     * Global validation rules.
     */
    private static array $rules = [];

    /**
     * Instance-specific validation rules.
     */
    private array $instanceRules = [];

    /**
     * Instance-specific rule messages.
     */
    private array $instanceRuleMessages = [];

    /**
     * Reference to the validator instance (for method_exists checks).
     */
    private object $validator;

    /**
     * Create a new RuleRegistry instance.
     *
     * @param object $validator The validator instance this registry is attached to.
     */
    public function __construct(object $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Register new global validation rule callback.
     *
     * @param string $name The name of the validation rule.
     * @param callable(string, mixed, array): bool $callback The validation function.
     * @param string|null $message Optional custom error message.
     *
     * @throws InvalidArgumentException If the callback is not valid
     */
    public static function addGlobalRule(string $name, callable $callback, ?string $message = null): void
    {
        if ($message === null) {
            $message = self::ERROR_DEFAULT;
        }

        self::assertRuleCallback($callback);

        self::$rules[$name] = $callback;
        LanguageManager::addRuleMessage($name, $message);
    }

    /**
     * Adds a new validation rule callback that is tied to the current instance only.
     *
     * @param string $name The name of the validation rule.
     * @param callable(string, mixed, array): bool $callback The validation function.
     * @param string|null $message Optional custom error message.
     *
     * @throws InvalidArgumentException If the callback is not valid
     */
    public function addInstanceRule(string $name, callable $callback, ?string $message = null): void
    {
        self::assertRuleCallback($callback);

        $this->instanceRules[$name] = $callback;
        $this->instanceRuleMessages[$name] = $message;
    }

    /**
     * Returns all rule callbacks, the static and instance ones.
     *
     * @return array Associative array of rule names to callbacks.
     */
    public function getAllRules(): array
    {
        return [...$this->instanceRules, ...self::$rules];
    }

    /**
     * Returns all rule messages, the static and instance ones.
     *
     * @return array Associative array of rule names to error messages.
     */
    public function getAllRuleMessages(): array
    {
        return [...$this->instanceRuleMessages, ...LanguageManager::getRuleMessages()];
    }

    /**
     * Check if a validator exists.
     *
     * @param string $name The name of the validation rule to check.
     *
     * @return bool True if the validator exists, false otherwise.
     */
    public function hasValidator(string $name): bool
    {
        $rules = $this->getAllRules();
        return method_exists($this->validator, "validate" . ucfirst($name))
            || isset($rules[$name]);
    }

    /**
     * Get a unique rule name for custom rules.
     *
     * @param string|array $fields The field name(s) to base the rule name on.
     *
     * @return string A unique rule name.
     */
    public function getUniqueRuleName(string|array $fields): string
    {
        if (is_array($fields)) {
            $fields = implode("_", $fields);
        }

        $orgName = "{$fields}_rule";
        $name = $orgName;
        $rules = $this->getAllRules();

        while (isset($rules[$name])) {
            $name = $orgName . "_" . random_int(0, 99999);
        }

        return $name;
    }

    /**
     * Validate rule callback.
     *
     * @param callable(string, mixed, array): bool $callback The callback to validate.
     *
     * @throws InvalidArgumentException If the callback is not callable
     */
    protected static function assertRuleCallback(callable $callback): void
    {
        // No-op: Type hint already enforces callable, kept for backwards compatibility
    }

    /**
     * Clear all instance-specific rules.
     */
    public function clearInstanceRules(): void
    {
        $this->instanceRules = [];
        $this->instanceRuleMessages = [];
    }

    /**
     * Get global rules.
     *
     * @return array The global rules array.
     */
    public static function getGlobalRules(): array
    {
        return self::$rules;
    }

    /**
     * Get instance rules.
     *
     * @return array The instance rules array.
     */
    public function getInstanceRules(): array
    {
        return $this->instanceRules;
    }
}
