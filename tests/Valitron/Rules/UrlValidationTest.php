<?php

declare(strict_types=1);

namespace Valitron\Tests\Rules;

use function checkdnsrr;
use function function_exists;
use function md5;
use function time;

use Valitron\Tests\BaseTestCase;
use Valitron\Validator;

class UrlValidationTest extends BaseTestCase
{
    // URL Tests
    public function testUrlValid()
    {
        $v = new Validator(['website' => 'http://google.com']);
        $v->rule('url', 'website');
        $this->assertTrue($v->validate());
    }

    public function testUrlValidAltSyntax()
    {
        $v = new Validator(['website' => 'https://example.com/contact']);
        $v->rules([
            'url' => [
                ['website'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testUrlInvalid()
    {
        $v = new Validator(['website' => 'shoobedobop']);
        $v->rule('url', 'website');
        $this->assertFalse($v->validate());
    }

    public function testUrlInvalidAltSyntax()
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
    public function testUrlActive()
    {
        $v = new Validator(['website' => 'http://google.com']);
        $v->rule('urlActive', 'website');
        $this->assertTrue($v->validate());
    }

    public function testUrlActiveValidAltSyntax()
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

    public function testUrlInactive()
    {
        $v = new Validator(['website' => 'http://example-test-domain-' . md5((string)time()) . '.com']);
        $v->rule('urlActive', 'website');
        $this->assertFalse($v->validate());
    }

    public function testUrlActiveInvalidAltSyntax()
    {
        $v = new Validator(['website' => 'https://example-domain']);
        $v->rules([
            'urlActive' => [
                ['website'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }
}
