<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Core;

use function basename;

use const DIRECTORY_SEPARATOR;

use function dirname;
use function implode;
use function in_array;

use InvalidArgumentException;

use function is_array;
use function is_file;
use function is_readable;
use function realpath;

/**
 * Language Manager
 *
 * Handles internationalization (i18n) for validation messages including
 * language loading, validation, and secure file access.
 *
 * @package Valicomb\Core
 *
 * @internal
 */
class LanguageManager
{
    /**
     * Allowed language codes for validation messages.
     */
    private const ALLOWED_LANGUAGES = [
        'ar', 'cs', 'da', 'de', 'en', 'es', 'fa', 'fi', 'fr', 'hu',
        'id', 'it', 'ja', 'nl', 'no', 'pl', 'pt', 'ru', 'sv', 'tr', 'uk', 'zh',
    ];

    /**
     * Current language for validation messages.
     */
    private static ?string $lang = null;

    /**
     * Language file directory.
     */
    private static ?string $langDir = null;

    /**
     * Global rule messages.
     */
    private static array $ruleMessages = [];

    /**
     * Get/set language to use for validation messages.
     *
     * @param string|null $lang The language code to set, or null to get current language.
     *
     * @return string The current language code.
     */
    public static function lang(?string $lang = null): string
    {
        if ($lang !== null) {
            self::$lang = $lang;
        }

        return self::$lang ?? 'en';
    }

    /**
     * Get/set language file path.
     *
     * @param string|null $dir The directory path containing language files, or null to get current directory.
     *
     * @return string The current language directory path.
     */
    public static function langDir(?string $dir = null): string
    {
        if ($dir !== null) {
            self::$langDir = $dir;
        }

        return self::$langDir ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'lang';
    }

    /**
     * Initialize and load language files securely.
     *
     * @param string|null $lang The language code to load.
     * @param string|null $langDir The directory containing language files.
     *
     * @throws InvalidArgumentException If language code is invalid or file cannot be loaded
     *
     * @return array The loaded language messages.
     */
    public static function loadLanguage(?string $lang = null, ?string $langDir = null): array
    {
        // Determine language
        $lang ??= static::lang();
        $lang = basename($lang); // Remove any path traversal attempts

        // Validate language against whitelist
        if (!in_array($lang, self::ALLOWED_LANGUAGES, true)) {
            throw new InvalidArgumentException(
                "Invalid language '$lang'. Allowed: " . implode(', ', self::ALLOWED_LANGUAGES),
            );
        }

        // Determine and validate language directory
        $langDir ??= static::langDir();
        $originalLangDir = $langDir;

        // Resolve the real path (this will normalize .. and check existence)
        $langDir = realpath($langDir);

        if ($langDir === false) {
            // Directory doesn't exist - construct expected file path for error message
            $langFile = $originalLangDir . '/' . $lang . '.php';
            throw new InvalidArgumentException("Fail to load language file '$langFile'");
        }

        // Build and validate file path
        $langFile = $langDir . DIRECTORY_SEPARATOR . $lang . '.php';

        if (!is_file($langFile) || !is_readable($langFile)) {
            throw new InvalidArgumentException("Fail to load language file '$langFile'");
        }

        // Load language file
        $langMessages = include $langFile;

        if (!is_array($langMessages)) {
            throw new InvalidArgumentException("Language file must return an array");
        }

        // Merge with existing messages
        self::$ruleMessages = [...self::$ruleMessages, ...$langMessages];

        return $langMessages;
    }

    /**
     * Get all rule messages.
     *
     * @return array The rule messages array.
     */
    public static function getRuleMessages(): array
    {
        return self::$ruleMessages;
    }

    /**
     * Add a rule message.
     *
     * @param string $rule The rule name.
     * @param string $message The message template.
     */
    public static function addRuleMessage(string $rule, string $message): void
    {
        self::$ruleMessages[$rule] = $message;
    }

    /**
     * Add multiple rule messages.
     *
     * @param array $messages Associative array of rule names to messages.
     */
    public static function addRuleMessages(array $messages): void
    {
        self::$ruleMessages = [...self::$ruleMessages, ...$messages];
    }

    /**
     * Clear all rule messages.
     */
    public static function clearRuleMessages(): void
    {
        self::$ruleMessages = [];
    }

    /**
     * Get allowed language codes.
     *
     * @return array List of supported language codes.
     */
    public static function getAllowedLanguages(): array
    {
        return self::ALLOWED_LANGUAGES;
    }
}
