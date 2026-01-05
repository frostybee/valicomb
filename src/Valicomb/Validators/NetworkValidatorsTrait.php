<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use function checkdnsrr;
use function count;
use function defined;
use function explode;

use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_URL;

use function filter_var;
use function function_exists;
use function idn_to_ascii;
use function in_array;
use function is_array;
use function is_string;
use function ltrim;
use function parse_url;

use const PHP_URL_HOST;

use function preg_match;
use function preg_replace;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function stristr;
use function strlen;
use function strtolower;
use function strtoupper;

/**
 * Network Validators Trait
 *
 * Contains all network-related validation methods including:
 * - IP address validation (v4, v6, and general)
 * - Email validation (basic and DNS-verified)
 * - URL validation (syntax and active DNS)
 *
 * Note: This trait requires the $validUrlPrefixes property to be defined in the using class.
 *
 * @package Valicomb\Validators
 */
trait NetworkValidatorsTrait
{
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

        // Reject leading/trailing dots in domain part
        if (str_starts_with($domain, '.') || str_ends_with($domain, '.')) {
            return false;
        }

        // Reject consecutive dots in domain part
        if (str_contains($domain, '..')) {
            return false;
        }

        return true;
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

        // Reject extremely long URLs (max 2048 chars is a common limit)
        if (strlen($value) > 2048) {
            return false;
        }

        // Check if URL starts with valid prefix
        foreach ($this->validUrlPrefixes as $prefix) {
            if (str_starts_with($value, (string) $prefix)) {
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

        // Reject extremely long URLs (max 2048 chars is a common limit)
        if (strlen($value) > 2048) {
            return false;
        }

        // Check if URL starts with valid prefix
        foreach ($this->validUrlPrefixes as $prefix) {
            if (str_starts_with($value, (string) $prefix)) {
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
     * Validate that a field is a valid URL with stricter checks
     *
     * Performs enhanced URL validation beyond PHP's FILTER_VALIDATE_URL to catch
     * common URL typos and edge cases that pass basic validation but are likely errors.
     *
     * This validation includes all checks from validateUrl() plus:
     * - Domain must contain at least one dot (rejects "http://localhost")
     * - Detects common typos like "ww." instead of "www."
     * - Rejects empty subdomain parts (e.g., "http://..example.com")
     * - Validates reasonable domain label lengths (max 63 chars per label)
     * - Rejects numeric-only TLDs (e.g., ".123")
     *
     * Use cases:
     * - Public-facing URLs that should be accessible globally
     * - User-submitted URLs where typos are common
     * - Applications requiring well-formed, internet-accessible URLs
     *
     * For less strict validation (RFC compliance only), use validateUrl().
     * For DNS verification (ensure domain resolves), use validateUrlActive().
     *
     * Examples:
     * - "https://www.example.com" → true
     * - "https://example.com" → true
     * - "https://ww.example.com" → false (common typo)
     * - "http://localhost" → false (no dot in domain)
     * - "https://..example.com" → false (empty subdomain)
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     *
     * @return bool True if URL passes strict validation, false otherwise.
     */
    protected function validateUrlStrict(string $field, mixed $value): bool
    {
        // First perform basic URL validation
        if (!$this->validateUrl($field, $value)) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        // Extract hostname
        $host = parse_url($value, PHP_URL_HOST);
        if ($host === null || $host === false || $host === '') {
            return false;
        }

        // Domain must contain at least one dot (reject "localhost" style)
        if (!str_contains($host, '.')) {
            return false;
        }

        // Check for common typos: "ww." instead of "www."
        // Also catch "wwww.", "www1.", and similar
        $hostLower = strtolower($host);
        if (preg_match('/^ww[w\d]*\./', $hostLower) && !str_starts_with($hostLower, 'www.')) {
            return false;
        }

        // Split into domain labels
        $labels = explode('.', $host);

        // Reject empty labels (e.g., "..example.com" or "example..com")
        foreach ($labels as $label) {
            if ($label === '') {
                return false;
            }

            // Each label should be 1-63 characters (DNS standard)
            if (strlen($label) > 63) {
                return false;
            }
        }

        // TLD (last label) should not be purely numeric
        // Note: $labels is guaranteed non-empty since we verified a dot exists
        $tld = $labels[count($labels) - 1];
        if (preg_match('/^\d+$/', $tld)) {
            return false;
        }

        return true;
    }

    /**
     * Validate that a field is a valid phone number
     *
     * Validates phone numbers in various international formats. Supports:
     * - International format with country code: +1234567890, +44 20 1234 5678
     * - National format with area code: (123) 456-7890, 123-456-7890
     * - Various separators: spaces, dashes, dots, parentheses
     * - Optional country code parameter for country-specific validation
     *
     * Validation approach:
     * - Removes common formatting characters (spaces, dashes, dots, parentheses)
     * - Validates digit count (7-15 digits for international, allows country-specific rules)
     * - Validates country code prefix if provided
     * - Ensures only valid characters are present
     *
     * Accepted formats (examples):
     * - "+1234567890"
     * - "+1 (234) 567-8900"
     * - "+44 20 1234 5678"
     * - "(123) 456-7890"
     * - "123-456-7890"
     * - "123.456.7890"
     * - "1234567890"
     *
     * Country code support:
     * - 'US' or 'CA': 10 digits, optional +1 prefix
     * - 'UK' or 'GB': 10-11 digits, optional +44 prefix
     * - 'AU': 9-10 digits, optional +61 prefix
     * - 'IN': 10 digits, optional +91 prefix
     * - 'DE': 10-11 digits, optional +49 prefix
     * - 'FR': 9-10 digits, optional +33 prefix
     * - 'IT': 9-10 digits, optional +39 prefix
     * - 'ES': 9 digits, optional +34 prefix
     * - 'BR': 10-11 digits, optional +55 prefix
     * - 'MX': 10 digits, optional +52 prefix
     *
     * This validation is format-based only - it does NOT:
     * - Verify the number is active or in service
     * - Check against a phone number database
     * - Validate carrier or network
     * - Perform number portability lookups
     *
     * @param string $field The field name being validated.
     * @param mixed $value The value to validate.
     * @param array $params Optional country code: [0] => string country code (e.g., 'US', 'UK', 'FR').
     *
     * @return bool True if value is a valid phone number format, false otherwise.
     */
    protected function validatePhone(string $field, mixed $value, array $params = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Country code if provided
        $countryCode = $params[0] ?? null;
        if ($countryCode !== null && !is_string($countryCode)) {
            return false;
        }

        // Normalize country code to uppercase
        $countryCode = $countryCode !== null ? strtoupper($countryCode) : null;

        // Strip common formatting characters
        $stripped = preg_replace('/[\s\-\.\(\)]/', '', $value);

        if ($stripped === null || $stripped === '') {
            return false;
        }

        // Check if starts with + (international format)
        $hasPlus = str_starts_with($stripped, '+');

        // Remove + for digit counting
        $digitsOnly = ltrim($stripped, '+');

        // Validate that only digits remain
        if (!preg_match('/^\d+$/', $digitsOnly)) {
            return false;
        }

        // Country-specific validation
        if ($countryCode !== null) {
            return $this->validatePhoneByCountry($digitsOnly, $countryCode, $hasPlus);
        }

        // General validation: 7-15 digits (international standard)
        $length = strlen($digitsOnly);

        return $length >= 7 && $length <= 15;
    }

    /**
     * Validate phone number for specific country
     *
     * Internal helper method that validates phone numbers against country-specific rules
     * including digit count and country code prefix requirements.
     *
     * @param string $digitsOnly The phone number with only digits (no + or formatting).
     * @param string $countryCode The country code (e.g., 'US', 'UK').
     * @param bool $hasPlus Whether the original number started with +.
     *
     * @return bool True if number is valid for the specified country, false otherwise.
     */
    private function validatePhoneByCountry(string $digitsOnly, string $countryCode, bool $hasPlus): bool
    {
        $length = strlen($digitsOnly);

        // Country-specific rules
        $rules = [
            'US' => ['prefix' => '1', 'length' => 10, 'withPrefix' => 11],
            'CA' => ['prefix' => '1', 'length' => 10, 'withPrefix' => 11],
            'UK' => ['prefix' => '44', 'length' => [10, 11], 'withPrefix' => [12, 13]],
            'GB' => ['prefix' => '44', 'length' => [10, 11], 'withPrefix' => [12, 13]],
            'AU' => ['prefix' => '61', 'length' => [9, 10], 'withPrefix' => [11, 12]],
            'IN' => ['prefix' => '91', 'length' => 10, 'withPrefix' => 12],
            'DE' => ['prefix' => '49', 'length' => [10, 11], 'withPrefix' => [12, 13]],
            'FR' => ['prefix' => '33', 'length' => [9, 10], 'withPrefix' => [11, 12]],
            'IT' => ['prefix' => '39', 'length' => [9, 10], 'withPrefix' => [11, 12]],
            'ES' => ['prefix' => '34', 'length' => 9, 'withPrefix' => 11],
            'BR' => ['prefix' => '55', 'length' => [10, 11], 'withPrefix' => [12, 13]],
            'MX' => ['prefix' => '52', 'length' => 10, 'withPrefix' => 12],
        ];

        if (!isset($rules[$countryCode])) {
            // Unknown country code - fall back to general validation
            return $length >= 7 && $length <= 15;
        }

        $rule = $rules[$countryCode];
        $prefix = $rule['prefix'];

        // Check if number starts with country code
        if (str_starts_with($digitsOnly, $prefix)) {
            // Number includes country code prefix
            $expectedLengths = is_array($rule['withPrefix']) ? $rule['withPrefix'] : [$rule['withPrefix']];

            return in_array($length, $expectedLengths, true);
        }

        // Number without country code prefix
        $expectedLengths = is_array($rule['length']) ? $rule['length'] : [$rule['length']];

        return in_array($length, $expectedLengths, true);
    }
}
