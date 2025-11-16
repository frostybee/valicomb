<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;
use InvalidArgumentException;

class StringValidationTest extends BaseTestCase
{
    // ASCII Tests
    public function testAsciiValid(): void
    {
        $v = new Validator(['text' => '12345 abcde']);
        $v->rule('ascii', 'text');
        $this->assertTrue($v->validate());
    }

    public function testAsciiValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'batman123']);
        $v->rules([
            'ascii' => [
                ['username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testAsciiInvalid(): void
    {
        $v = new Validator(['text' => '12345 abcdé']);
        $v->rule('ascii', 'text');
        $this->assertFalse($v->validate());
    }

    public function testAsciiInvalidAltSyntax(): void
    {
        $v = new Validator(['username' => '12345 abcdé']);
        $v->rules([
            'ascii' => [
                ['username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Email Tests
    public function testEmailValid(): void
    {
        $v = new Validator(['name' => 'Chester Tester', 'email' => 'chester@tester.com']);
        $v->rule('email', 'email');
        $this->assertTrue($v->validate());
    }

    public function testEmailValidAltSyntax(): void
    {
        $v = new Validator(['user_email' => 'someone@example.com']);
        $v->rules([
            'email' => [
                ['user_email'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testEmailInvalid(): void
    {
        $v = new Validator(['name' => 'Chester Tester', 'email' => 'chestertesterman']);
        $v->rule('email', 'email');
        $this->assertFalse($v->validate());
    }

    public function testEmailInvalidAltSyntax(): void
    {
        $v = new Validator(['user_email' => 'example.com']);
        $v->rules([
            'email' => [
                ['user_email'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testEmailDnsValid(): void
    {
        $v = new Validator(['name' => 'Chester Tester', 'email' => 'chester@tester.com']);
        $v->rule('emailDNS', 'email');
        $this->assertTrue($v->validate());
    }

    public function testEmailDnsValidAltSyntax(): void
    {
        $v = new Validator(['user_email' => 'some_fake_email_address@gmail.com']);
        $v->rules([
            'emailDNS' => [
                ['user_email'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testEmailDnsInvalid(): void
    {
        $v = new Validator(['name' => 'Chester Tester', 'email' => 'chester@tester.zyx']);
        $v->rule('emailDNS', 'email');
        $this->assertFalse($v->validate());
    }

    public function testEmailDnsInvalidAltSyntax(): void
    {
        $v = new Validator(['user_email' => 'some_fake_email_address@gmail.zyx']);
        $v->rules([
            'emailDNS' => [
                ['user_email'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Alpha Tests
    public function testAlphaValid(): void
    {
        $v = new Validator(['test' => 'abcDEF']);
        $v->rule('alpha', 'test');
        $this->assertTrue($v->validate());
    }

    public function testAlphaValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'batman']);
        $v->rules([
            'alpha' => [
                ['username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testAlphaInvalid(): void
    {
        $v = new Validator(['test' => 'abc123']);
        $v->rule('alpha', 'test');
        $this->assertFalse($v->validate());
    }

    public function testAlphaInvalidAltSyntax(): void
    {
        $v = new Validator(['username' => '123456asdf']);
        $v->rules([
            'alpha' => [
                ['username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // AlphaNum Tests
    public function testAlphaNumValid(): void
    {
        $v = new Validator(['test' => 'abc123']);
        $v->rule('alphaNum', 'test');
        $this->assertTrue($v->validate());
    }

    public function testAlphaNumValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'batman123']);
        $v->rules([
            'alphaNum' => [
                ['username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testAlphaNumInvalid(): void
    {
        $v = new Validator(['test' => 'abc123$%^']);
        $v->rule('alphaNum', 'test');
        $this->assertFalse($v->validate());
    }

    public function testAlphaNumInvalidAltSyntax(): void
    {
        $v = new Validator(['username' => 'batman123-$']);
        $v->rules([
            'alphaNum' => [
                ['username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Slug (AlphaDash) Tests
    public function testAlphaDashValid(): void
    {
        $v = new Validator(['test' => 'abc-123_DEF']);
        $v->rule('slug', 'test');
        $this->assertTrue($v->validate());
    }

    public function testSlugValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'L337-H4ckZ0rz_123']);
        $v->rules([
            'slug' => [
                ['username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testAlphaDashInvalid(): void
    {
        $v = new Validator(['test' => 'abc-123_DEF $%^']);
        $v->rule('slug', 'test');
        $this->assertFalse($v->validate());
    }

    public function testSlugInvalidAltSyntax(): void
    {
        $v = new Validator(['username' => 'L337-H4ckZ0rz_123 $%^']);
        $v->rules([
            'slug' => [
                ['username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Contains Tests
    public function testContainsValid(): void
    {
        $v = new Validator(['test_string' => 'this is a Test']);
        $v->rule('contains', 'test_string', 'Test');
        $this->assertTrue($v->validate());
    }

    public function testContainsValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'Batman123']);
        $v->rules([
            'contains' => [
                ['username', 'man'],
                ['username', 'man', true],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testContainsNonStrictValid(): void
    {
        $v = new Validator(['test_string' => 'this is a Test']);
        $v->rule('contains', 'test_string', 'test', false);
        $this->assertTrue($v->validate());
    }

    public function testContainsInvalid(): void
    {
        $v = new Validator(['test_string' => 'this is a test']);
        $v->rule('contains', 'test_string', 'foobar');
        $this->assertFalse($v->validate());
    }

    public function testContainsInvalidAltSyntax(): void
    {
        $v = new Validator(['username' => 'Batman123']);
        $v->rules([
            'contains' => [
                ['username', 'Man', true],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testContainsStrictInvalid(): void
    {
        $v = new Validator(['test_string' => 'this is a Test']);
        $v->rule('contains', 'test_string', 'test');
        $this->assertFalse($v->validate());
    }

    public function testContainsInvalidValue(): void
    {
        $v = new Validator(['test_string' => false]);
        $v->rule('contains', 'test_string', 'foobar');
        $this->assertFalse($v->validate());
    }

    public function testContainsInvalidRule(): void
    {
        $v = new Validator(['test_string' => 'this is a test']);
        $v->rule('contains', 'test_string', null);
        $this->assertFalse($v->validate());
    }

    // Regex Tests
    public function testRegexValid(): void
    {
        $v = new Validator(['test' => '42']);
        $v->rule('regex', 'test', '/[\d]+/');
        $this->assertTrue($v->validate());
    }

    public function testRegexValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'Batman123']);
        $v->rules([
            'regex' => [
                ['username', '/^[a-zA-Z0-9]{5,10}$/'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testRegexInvalid(): void
    {
        $v = new Validator(['test' => 'istheanswer']);
        $v->rule('regex', 'test', '/[\d]+/');
        $this->assertFalse($v->validate());
    }

    public function testRegexInvalidAltSyntax(): void
    {
        $v = new Validator(['username' => 'Batman_123']);
        $v->rules([
            'regex' => [
                ['username', '/^[a-zA-Z0-9]{5,10}$/'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Edge Cases
    public function testEmailEdgeCases(): void
    {
        // Valid edge cases
        $validEmails = [
            'a@b.c',                          // Minimal valid email
            'test+tag@example.com',           // Plus addressing
            'user.name@example.co.uk',        // Subdomain and TLD
            '123@example.com',                // Numeric local part
        ];

        foreach ($validEmails as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertTrue($v->validate(), "Valid email should pass: $email");
        }

        // Invalid edge cases
        $invalidEmails = [
            '@example.com',                   // Missing local part
            'test@',                          // Missing domain
            'test..user@example.com',         // Consecutive dots
            '.test@example.com',              // Leading dot
            'test.@example.com',              // Trailing dot
            "test\x00@example.com",           // Null byte
            'test\\user@example.com',         // Backslash
        ];

        foreach ($invalidEmails as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertFalse($v->validate(), "Invalid email should fail: $email");
        }
    }

    // Parameter Validation Tests
    public function testRegexRequiresStringPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Regex pattern must be provided as string');

        $v = new Validator(['field' => 'test']);
        $v->rule('regex', 'field', 123); // Integer instead of string
        $v->validate();
    }

    public function testRegexRequiresPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Regex pattern must be provided as string');

        $v = new Validator(['field' => 'test']);
        $v->rule('regex', 'field'); // Missing parameter
        $v->validate();
    }
}
