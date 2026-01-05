<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use function checkdnsrr;

use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;

use function function_exists;
use function md5;
use function str_repeat;
use function time;

class UrlValidationTest extends BaseTestCase
{
    // URL Tests
    public function testUrlValid(): void
    {
        $v = new Validator(['website' => 'http://google.com']);
        $v->rule('url', 'website');
        $this->assertTrue($v->validate());
    }

    public function testUrlValidAltSyntax(): void
    {
        $v = new Validator(['website' => 'https://example.com/contact']);
        $v->rules([
            'url' => [
                ['website'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testUrlInvalid(): void
    {
        $v = new Validator(['website' => 'shoobedobop']);
        $v->rule('url', 'website');
        $this->assertFalse($v->validate());
    }

    public function testUrlInvalidAltSyntax(): void
    {
        $v = new Validator(['website' => 'thisisjusttext']);
        $v->rules([
            'url' => [
                ['website'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // URL Active Tests
    public function testUrlActive(): void
    {
        $v = new Validator(['website' => 'http://google.com']);
        $v->rule('urlActive', 'website');
        $this->assertTrue($v->validate());
    }

    public function testUrlActiveValidAltSyntax(): void
    {
        // Use a more reliable domain that's guaranteed to have DNS
        $v = new Validator(['website' => 'https://google.com']);
        $v->rules([
            'urlActive' => [
                ['website'],
            ],
        ]);

        // Skip test if DNS is not available
        if (!function_exists('checkdnsrr')) {
            $this->markTestSkipped('checkdnsrr function not available');
            return;
        }

        // Try to check DNS first - skip test if network unavailable
        if (!@checkdnsrr('google.com', 'A')) {
            $this->markTestSkipped('DNS/Network unavailable for this test');
            return;
        }

        $this->assertTrue($v->validate());
    }

    public function testUrlInactive(): void
    {
        $v = new Validator(['website' => 'http://example-test-domain-' . md5((string)time()) . '.com']);
        $v->rule('urlActive', 'website');
        $this->assertFalse($v->validate());
    }

    public function testUrlActiveInvalidAltSyntax(): void
    {
        $v = new Validator(['website' => 'https://example-domain']);
        $v->rules([
            'urlActive' => [
                ['website'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // ===========================================
    // URL Strict Tests (#274)
    // ===========================================

    /**
     * Test urlStrict with valid standard URL
     */
    public function testUrlStrictValidStandardUrl(): void
    {
        $v = new Validator(['website' => 'https://www.example.com']);
        $v->rule('urlStrict', 'website');
        $this->assertTrue($v->validate());
    }

    /**
     * Test urlStrict with valid URL without www
     */
    public function testUrlStrictValidWithoutWww(): void
    {
        $v = new Validator(['website' => 'https://example.com']);
        $v->rule('urlStrict', 'website');
        $this->assertTrue($v->validate());
    }

    /**
     * Test urlStrict with valid URL with path
     */
    public function testUrlStrictValidWithPath(): void
    {
        $v = new Validator(['website' => 'https://example.com/path/to/page']);
        $v->rule('urlStrict', 'website');
        $this->assertTrue($v->validate());
    }

    /**
     * Test urlStrict with valid subdomain
     */
    public function testUrlStrictValidWithSubdomain(): void
    {
        $v = new Validator(['website' => 'https://blog.example.com']);
        $v->rule('urlStrict', 'website');
        $this->assertTrue($v->validate());
    }

    /**
     * Test urlStrict fails with ww. typo (common error)
     */
    public function testUrlStrictFailsWithWwTypo(): void
    {
        $v = new Validator(['website' => 'https://ww.example.com']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict fails with wwww. typo
     */
    public function testUrlStrictFailsWithWwwwTypo(): void
    {
        $v = new Validator(['website' => 'https://wwww.example.com']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict fails with localhost (no dot)
     */
    public function testUrlStrictFailsWithLocalhost(): void
    {
        $v = new Validator(['website' => 'http://localhost']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict fails with single word domain (no dot)
     */
    public function testUrlStrictFailsWithSingleWordDomain(): void
    {
        $v = new Validator(['website' => 'http://intranet']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict fails with empty subdomain (double dot)
     */
    public function testUrlStrictFailsWithEmptySubdomain(): void
    {
        $v = new Validator(['website' => 'https://..example.com']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict fails with double dot in domain
     */
    public function testUrlStrictFailsWithDoubleDotInDomain(): void
    {
        $v = new Validator(['website' => 'https://example..com']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict fails with numeric TLD
     */
    public function testUrlStrictFailsWithNumericTld(): void
    {
        $v = new Validator(['website' => 'https://example.123']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict fails with missing protocol
     */
    public function testUrlStrictFailsWithMissingProtocol(): void
    {
        $v = new Validator(['website' => 'www.example.com']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict fails with invalid protocol
     */
    public function testUrlStrictFailsWithInvalidProtocol(): void
    {
        $v = new Validator(['website' => 'javascript:alert(1)']);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict with valid international domain
     */
    public function testUrlStrictValidWithInternationalDomain(): void
    {
        $v = new Validator(['website' => 'https://example.co.uk']);
        $v->rule('urlStrict', 'website');
        $this->assertTrue($v->validate());
    }

    /**
     * Test urlStrict valid with port number
     */
    public function testUrlStrictValidWithPort(): void
    {
        $v = new Validator(['website' => 'https://example.com:8080']);
        $v->rule('urlStrict', 'website');
        $this->assertTrue($v->validate());
    }

    /**
     * Test urlStrict valid with query string
     */
    public function testUrlStrictValidWithQueryString(): void
    {
        $v = new Validator(['website' => 'https://example.com/search?q=test&page=1']);
        $v->rule('urlStrict', 'website');
        $this->assertTrue($v->validate());
    }

    /**
     * Test urlStrict fails with very long domain label
     */
    public function testUrlStrictFailsWithVeryLongDomainLabel(): void
    {
        // Domain label > 63 characters
        $longLabel = str_repeat('a', 64);
        $v = new Validator(['website' => "https://{$longLabel}.example.com"]);
        $v->rule('urlStrict', 'website');
        $this->assertFalse($v->validate());
    }

    /**
     * Test urlStrict passes with max length domain label
     */
    public function testUrlStrictPassesWithMaxLengthDomainLabel(): void
    {
        // Domain label = 63 characters (valid max)
        $maxLabel = str_repeat('a', 63);
        $v = new Validator(['website' => "https://{$maxLabel}.example.com"]);
        $v->rule('urlStrict', 'website');
        $this->assertTrue($v->validate());
    }

    /**
     * Test urlStrict displays correct error message
     */
    public function testUrlStrictErrorMessage(): void
    {
        $v = new Validator(['website' => 'https://ww.example.com']);
        $v->rule('urlStrict', 'website');
        $v->validate();
        $errors = $v->errors('website');
        $this->assertStringContainsString('not a valid public URL', $errors[0]);
    }
}
