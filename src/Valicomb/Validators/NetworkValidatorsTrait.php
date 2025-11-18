<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Validators;

use function checkdnsrr;
use function defined;
use function filter_var;
use function function_exists;
use function idn_to_ascii;
use function is_string;
use function ltrim;
use function parse_url;
use function preg_match;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function stristr;
use function strtolower;

use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;

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
        return !str_starts_with($local, '.') && !str_ends_with($local, '.');
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

        // Check if URL starts with valid prefix (FIXED: using str_starts_with)
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
}
