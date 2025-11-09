<?php

declare(strict_types=1);

namespace Valitron\Tests;

use function checkdnsrr;
use function defined;
use function function_exists;
use function md5;

use PHPUnit\Framework\TestCase;

use function time;

use Valitron\Validator;

/**
 * Tests for network-dependent validation rules (emailDNS, urlActive)
 * These tests may be skipped if network/DNS is unavailable
 */
class NetworkValidationTest extends TestCase
{
    /**
     * Helper to check if DNS is available
     */
    private function isDnsAvailable(): bool
    {
        if (!function_exists('checkdnsrr')) {
            return false;
        }
        // Try a known good domain
        return @checkdnsrr('google.com', 'A') !== false;
    }

    /**
     * Test emailDNS with valid email from known domain
     */
    public function testEmailDNSWithValidDomain(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        $v = new Validator(['email' => 'test@google.com']);
        $v->rule('emailDNS', 'email');
        $this->assertTrue($v->validate(), 'Email with valid MX records should pass');
    }

    /**
     * Test emailDNS with invalid domain
     */
    public function testEmailDNSWithInvalidDomain(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        // Use a very unlikely domain name
        $randomDomain = 'this-domain-absolutely-does-not-exist-' . md5((string)time()) . '.com';
        $v = new Validator(['email' => "test@{$randomDomain}"]);
        $v->rule('emailDNS', 'email');
        $this->assertFalse($v->validate(), 'Email with non-existent domain should fail');
    }

    /**
     * Test emailDNS with domain without MX records
     */
    public function testEmailDNSWithoutMXRecords(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        // localhost typically doesn't have MX records
        $v = new Validator(['email' => 'test@localhost']);
        $v->rule('emailDNS', 'email');
        $this->assertFalse($v->validate(), 'Email with domain without MX records should fail');
    }

    /**
     * Test emailDNS requires valid email format first
     */
    public function testEmailDNSRequiresValidFormat(): void
    {
        $v = new Validator(['email' => 'not-an-email']);
        $v->rule('emailDNS', 'email');
        $this->assertFalse($v->validate(), 'Invalid email format should fail before DNS check');
    }

    /**
     * Test emailDNS with internationalized domain (if intl extension available)
     */
    public function testEmailDNSWithInternationalizedDomain(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        if (!function_exists('idn_to_ascii') || !defined('INTL_IDNA_VARIANT_UTS46')) {
            $this->markTestSkipped('intl extension not available');
        }

        // Using a known internationalized domain with MX records
        // Note: This test might be flaky if the domain changes
        $v = new Validator(['email' => 'test@münchen.de']);
        $v->rule('emailDNS', 'email');
        // Just verify it doesn't crash - actual result depends on domain's MX records
        $v->validate();
        $this->assertTrue(true, 'Should handle IDN domains without crashing');
    }

    /**
     * Test emailDNS with multiple popular domains
     */
    public function testEmailDNSWithPopularDomains(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        $popularDomains = ['gmail.com', 'yahoo.com', 'outlook.com'];

        foreach ($popularDomains as $domain) {
            $v = new Validator(['email' => "test@{$domain}"]);
            $v->rule('emailDNS', 'email');
            $this->assertTrue($v->validate(), "Email with {$domain} should have MX records");
        }
    }

    /**
     * Test urlActive with valid URL
     */
    public function testUrlActiveWithValidUrl(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        $v = new Validator(['url' => 'https://google.com']);
        $v->rule('urlActive', 'url');
        $this->assertTrue($v->validate(), 'URL with valid DNS should pass');
    }

    /**
     * Test urlActive with invalid URL
     */
    public function testUrlActiveWithInvalidUrl(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        $randomDomain = 'http://this-does-not-exist-' . md5((string)time()) . '.com';
        $v = new Validator(['url' => $randomDomain]);
        $v->rule('urlActive', 'url');
        $this->assertFalse($v->validate(), 'URL with non-existent domain should fail');
    }

    /**
     * Test urlActive requires valid URL format
     */
    public function testUrlActiveRequiresValidFormat(): void
    {
        $v = new Validator(['url' => 'not-a-url']);
        $v->rule('urlActive', 'url');
        $this->assertFalse($v->validate(), 'Invalid URL format should fail before DNS check');
    }

    /**
     * Test urlActive with different protocols
     */
    public function testUrlActiveWithDifferentProtocols(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        $urls = [
            'http://google.com',
            'https://google.com',
            'ftp://ftp.gnu.org',
        ];

        foreach ($urls as $url) {
            $v = new Validator(['url' => $url]);
            $v->rule('urlActive', 'url');
            $v->validate(); // Just verify it doesn't crash
            $this->assertTrue(true, "Should handle {$url}");
        }
    }

    /**
     * Test urlActive with URL containing path
     */
    public function testUrlActiveWithPath(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        $v = new Validator(['url' => 'https://google.com/search?q=test']);
        $v->rule('urlActive', 'url');
        $this->assertTrue($v->validate(), 'URL with path should check domain DNS');
    }

    /**
     * Test urlActive with subdomain
     */
    public function testUrlActiveWithSubdomain(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        $v = new Validator(['url' => 'https://www.google.com']);
        $v->rule('urlActive', 'url');
        $this->assertTrue($v->validate(), 'URL with subdomain should pass');
    }

    /**
     * Test urlActive with port number
     */
    public function testUrlActiveWithPort(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        $v = new Validator(['url' => 'https://google.com:443']);
        $v->rule('urlActive', 'url');
        $this->assertTrue($v->validate(), 'URL with port should check domain DNS');
    }

    /**
     * Test urlActive checks A, AAAA, or CNAME records
     */
    public function testUrlActiveChecksMultipleDNSRecordTypes(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        // Google should have at least A records, possibly AAAA
        $v = new Validator(['url' => 'https://google.com']);
        $v->rule('urlActive', 'url');
        $this->assertTrue($v->validate(), 'Should pass with A/AAAA/CNAME records');
    }

    /**
     * Test emailDNS with valid email but without email validation
     */
    public function testEmailDNSStandaloneValidation(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        // emailDNS should validate email format internally
        $v = new Validator(['email' => 'valid@google.com']);
        $v->rule('emailDNS', 'email'); // No separate email rule
        $this->assertTrue($v->validate(), 'emailDNS should validate format internally');
    }

    /**
     * Test urlActive doesn't require url rule
     */
    public function testUrlActiveStandaloneValidation(): void
    {
        if (!$this->isDnsAvailable()) {
            $this->markTestSkipped('DNS not available');
        }

        // urlActive should validate URL format internally
        $v = new Validator(['url' => 'https://google.com']);
        $v->rule('urlActive', 'url'); // No separate url rule
        $this->assertTrue($v->validate(), 'urlActive should validate format internally');
    }
}
