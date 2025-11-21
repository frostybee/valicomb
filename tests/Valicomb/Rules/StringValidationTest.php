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

    // UUID Tests
    public function testUuidValid(): void
    {
        // Valid UUIDv4
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertTrue($v->validate());

        // Valid UUIDv1
        $v = new Validator(['id' => '550e8400-e29b-11d4-a716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertTrue($v->validate());

        // Valid UUIDv3
        $v = new Validator(['id' => 'a3bb189e-8bf9-3888-9912-ace4e6543002']);
        $v->rule('uuid', 'id');
        $this->assertTrue($v->validate());

        // Valid UUIDv5
        $v = new Validator(['id' => '886313e1-3b8a-5372-9b90-0c9aee199e5d']);
        $v->rule('uuid', 'id');
        $this->assertTrue($v->validate());
    }

    public function testUuidValidUppercase(): void
    {
        // UUIDs should be case-insensitive
        $v = new Validator(['id' => '550E8400-E29B-41D4-A716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertTrue($v->validate());
    }

    public function testUuidValidMixedCase(): void
    {
        $v = new Validator(['id' => '550e8400-E29b-41D4-a716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertTrue($v->validate());
    }

    public function testUuidValidAltSyntax(): void
    {
        $v = new Validator(['request_id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rules([
            'uuid' => [
                ['request_id'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testUuidValidSpecificVersion(): void
    {
        // Valid UUIDv4 with version parameter
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rule('uuid', 'id', 4);
        $this->assertTrue($v->validate());

        // Valid UUIDv1 with version parameter
        $v = new Validator(['id' => '550e8400-e29b-11d4-a716-446655440000']);
        $v->rule('uuid', 'id', 1);
        $this->assertTrue($v->validate());

        // Valid UUIDv3 with version parameter
        $v = new Validator(['id' => 'a3bb189e-8bf9-3888-9912-ace4e6543002']);
        $v->rule('uuid', 'id', 3);
        $this->assertTrue($v->validate());

        // Valid UUIDv5 with version parameter
        $v = new Validator(['id' => '886313e1-3b8a-5372-9b90-0c9aee199e5d']);
        $v->rule('uuid', 'id', 5);
        $this->assertTrue($v->validate());
    }

    public function testUuidInvalidFormat(): void
    {
        // Missing hyphens
        $v = new Validator(['id' => '550e8400e29b41d4a716446655440000']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Wrong format (too short)
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Wrong format (too long)
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000-extra']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Invalid characters
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-44665544000g']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());
    }

    public function testUuidInvalidVersion(): void
    {
        // Version 0 doesn't exist
        $v = new Validator(['id' => '550e8400-e29b-01d4-a716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Version 6 doesn't exist
        $v = new Validator(['id' => '550e8400-e29b-61d4-a716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Version 9 doesn't exist
        $v = new Validator(['id' => '550e8400-e29b-91d4-a716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());
    }

    public function testUuidInvalidVariant(): void
    {
        // Invalid variant (must be 8, 9, a, or b)
        // Variant digit is at position 19 (first char of 4th group)

        // Variant 'c' is invalid
        $v = new Validator(['id' => '550e8400-e29b-41d4-c716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Variant '0' is invalid
        $v = new Validator(['id' => '550e8400-e29b-41d4-0716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Variant 'f' is invalid
        $v = new Validator(['id' => '550e8400-e29b-41d4-f716-446655440000']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());
    }

    public function testUuidInvalidWrongVersion(): void
    {
        // UUIDv1 checked against v4
        $v = new Validator(['id' => '550e8400-e29b-11d4-a716-446655440000']);
        $v->rule('uuid', 'id', 4);
        $this->assertFalse($v->validate());

        // UUIDv4 checked against v1
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rule('uuid', 'id', 1);
        $this->assertFalse($v->validate());

        // UUIDv3 checked against v5
        $v = new Validator(['id' => 'a3bb189e-8bf9-3888-9912-ace4e6543002']);
        $v->rule('uuid', 'id', 5);
        $this->assertFalse($v->validate());
    }

    public function testUuidInvalidNonString(): void
    {
        // Array
        $v = new Validator(['id' => ['550e8400-e29b-41d4-a716-446655440000']]);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Integer
        $v = new Validator(['id' => 12345]);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        // Boolean
        $v = new Validator(['id' => true]);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());
    }

    public function testUuidWithEmptyString(): void
    {
        // Empty strings are treated as "not provided" and pass unless required
        $v = new Validator(['id' => '']);
        $v->rule('uuid', 'id');
        $this->assertTrue($v->validate());

        // With required, empty string should fail
        $v = new Validator(['id' => '']);
        $v->rule('required', 'id')
          ->rule('uuid', 'id');
        $this->assertFalse($v->validate());
    }

    public function testUuidInvalidRandomString(): void
    {
        $v = new Validator(['id' => 'not-a-uuid-at-all']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        $v = new Validator(['id' => 'this-is-not-a-valid-uuid-format']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());
    }

    public function testUuidInvalidVersionParameter(): void
    {
        // Version parameter is 0 (invalid)
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rule('uuid', 'id', 0);
        $this->assertFalse($v->validate());

        // Version parameter is 6 (invalid)
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rule('uuid', 'id', 6);
        $this->assertFalse($v->validate());

        // Version parameter is negative
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rule('uuid', 'id', -1);
        $this->assertFalse($v->validate());

        // Version parameter is not an integer
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rule('uuid', 'id', '4');
        $this->assertFalse($v->validate());
    }

    public function testUuidErrorMessage(): void
    {
        $v = new Validator(['id' => 'not-a-uuid']);
        $v->rule('uuid', 'id');
        $this->assertFalse($v->validate());

        $errors = $v->errors();
        $this->assertArrayHasKey('id', $errors);
        $this->assertStringContainsString('must be a valid UUID', $errors['id'][0]);
    }

    public function testUuidWithLabel(): void
    {
        $v = new Validator(['request_id' => 'invalid']);
        $v->rule('uuid', 'request_id')
          ->label('Request ID');
        $this->assertFalse($v->validate());

        $errors = $v->errors();
        $this->assertArrayHasKey('request_id', $errors);
        $this->assertStringContainsString('Request ID must be a valid UUID', $errors['request_id'][0]);
    }

    public function testUuidRealWorldExamples(): void
    {
        // Common UUIDv4 examples from real systems
        $validUuids = [
            '123e4567-e89b-12d3-a456-426614174000',  // v1
            '123e4567-e89b-42d3-a456-426614174000',  // v4
            '6ba7b810-9dad-11d1-80b4-00c04fd430c8',  // v1 (DNS namespace)
            '6ba7b811-9dad-11d1-80b4-00c04fd430c8',  // v1 (URL namespace)
            'f47ac10b-58cc-4372-a567-0e02b2c3d479',  // v4
            '00000000-0000-4000-8000-000000000000',  // v4 (nil with valid format)
        ];

        foreach ($validUuids as $uuid) {
            $v = new Validator(['id' => $uuid]);
            $v->rule('uuid', 'id');
            $this->assertTrue($v->validate(), "UUID {$uuid} should be valid");
        }
    }

    public function testUuidAllVersionsIndividually(): void
    {
        // Test each version explicitly
        $versions = [
            1 => '550e8400-e29b-11d4-a716-446655440000',
            2 => '550e8400-e29b-21d4-a716-446655440000',
            3 => 'a3bb189e-8bf9-3888-9912-ace4e6543002',
            4 => '550e8400-e29b-41d4-a716-446655440000',
            5 => '886313e1-3b8a-5372-9b90-0c9aee199e5d',
        ];

        foreach ($versions as $version => $uuid) {
            // Test without version parameter (should pass)
            $v = new Validator(['id' => $uuid]);
            $v->rule('uuid', 'id');
            $this->assertTrue($v->validate(), "UUID version {$version} should be valid");

            // Test with correct version parameter
            $v = new Validator(['id' => $uuid]);
            $v->rule('uuid', 'id', $version);
            $this->assertTrue($v->validate(), "UUID should validate as version {$version}");

            // Test with wrong version parameter
            $wrongVersion = $version === 1 ? 2 : 1;
            $v = new Validator(['id' => $uuid]);
            $v->rule('uuid', 'id', $wrongVersion);
            $this->assertFalse($v->validate(), "UUID version {$version} should fail validation as version {$wrongVersion}");
        }
    }

    public function testUuidWithOtherRules(): void
    {
        // Combine with required
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->rule('required', 'id')
          ->rule('uuid', 'id');
        $this->assertTrue($v->validate());

        // Combine with required (missing field)
        $v = new Validator([]);
        $v->rule('required', 'id')
          ->rule('uuid', 'id');
        $this->assertFalse($v->validate());
    }

    // Starts With Tests
    public function testStartsWithValid(): void
    {
        // Single prefix
        $v = new Validator(['url' => 'https://example.com']);
        $v->rule('startsWith', 'url', 'https://');
        $this->assertTrue($v->validate());

        // Another single prefix
        $v = new Validator(['code' => 'PROD-12345']);
        $v->rule('startsWith', 'code', 'PROD-');
        $this->assertTrue($v->validate());

        // Exact match
        $v = new Validator(['text' => 'hello']);
        $v->rule('startsWith', 'text', 'hello');
        $this->assertTrue($v->validate());
    }

    public function testStartsWithValidMultiplePrefixes(): void
    {
        // First prefix matches
        $v = new Validator(['phone' => '+1234567890']);
        $v->rule('startsWith', 'phone', ['+1', '+44', '+61']);
        $this->assertTrue($v->validate());

        // Second prefix matches
        $v = new Validator(['phone' => '+44234567890']);
        $v->rule('startsWith', 'phone', ['+1', '+44', '+61']);
        $this->assertTrue($v->validate());

        // Third prefix matches
        $v = new Validator(['phone' => '+61234567890']);
        $v->rule('startsWith', 'phone', ['+1', '+44', '+61']);
        $this->assertTrue($v->validate());
    }

    public function testStartsWithValidAltSyntax(): void
    {
        $v = new Validator(['url' => 'https://example.com']);
        $v->rules([
            'startsWith' => [
                ['url', 'https://'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testStartsWithCaseInsensitive(): void
    {
        // Lowercase value, uppercase prefix
        $v = new Validator(['code' => 'prod-12345']);
        $v->rule('startsWith', 'code', 'PROD-', false);
        $this->assertTrue($v->validate());

        // Uppercase value, lowercase prefix
        $v = new Validator(['code' => 'PROD-12345']);
        $v->rule('startsWith', 'code', 'prod-', false);
        $this->assertTrue($v->validate());

        // Mixed case
        $v = new Validator(['text' => 'HeLLo World']);
        $v->rule('startsWith', 'text', 'hello', false);
        $this->assertTrue($v->validate());
    }

    public function testStartsWithCaseSensitive(): void
    {
        // Case-sensitive (default)
        $v = new Validator(['code' => 'PROD-12345']);
        $v->rule('startsWith', 'code', 'PROD-');
        $this->assertTrue($v->validate());

        // Explicit case-sensitive
        $v = new Validator(['code' => 'PROD-12345']);
        $v->rule('startsWith', 'code', 'PROD-', true);
        $this->assertTrue($v->validate());
    }

    public function testStartsWithInvalid(): void
    {
        // Wrong prefix
        $v = new Validator(['url' => 'http://example.com']);
        $v->rule('startsWith', 'url', 'https://');
        $this->assertFalse($v->validate());

        // Prefix in middle
        $v = new Validator(['text' => 'hello world']);
        $v->rule('startsWith', 'text', 'world');
        $this->assertFalse($v->validate());

        // Prefix at end
        $v = new Validator(['text' => 'hello world']);
        $v->rule('startsWith', 'text', 'ld');
        $this->assertFalse($v->validate());
    }

    public function testStartsWithInvalidCaseSensitive(): void
    {
        // Case mismatch with case-sensitive (default)
        $v = new Validator(['code' => 'prod-12345']);
        $v->rule('startsWith', 'code', 'PROD-');
        $this->assertFalse($v->validate());

        // Explicit case-sensitive fails
        $v = new Validator(['code' => 'prod-12345']);
        $v->rule('startsWith', 'code', 'PROD-', true);
        $this->assertFalse($v->validate());
    }

    public function testStartsWithInvalidMultiplePrefixes(): void
    {
        // None of the prefixes match
        $v = new Validator(['phone' => '+33234567890']);
        $v->rule('startsWith', 'phone', ['+1', '+44', '+61']);
        $this->assertFalse($v->validate());

        // Close but not exact
        $v = new Validator(['code' => 'PRODUCT-12345']);
        $v->rule('startsWith', 'code', ['PROD-', 'DEV-', 'TEST-']);
        $this->assertFalse($v->validate());
    }

    public function testStartsWithInvalidNonString(): void
    {
        // Array
        $v = new Validator(['value' => ['https://example.com']]);
        $v->rule('startsWith', 'value', 'https://');
        $this->assertFalse($v->validate());

        // Integer
        $v = new Validator(['value' => 12345]);
        $v->rule('startsWith', 'value', '1');
        $this->assertFalse($v->validate());

        // Boolean
        $v = new Validator(['value' => true]);
        $v->rule('startsWith', 'value', 't');
        $this->assertFalse($v->validate());
    }

    public function testStartsWithEmptyString(): void
    {
        // Empty string with empty prefix
        $v = new Validator(['text' => '']);
        $v->rule('startsWith', 'text', '');
        $this->assertTrue($v->validate());

        // Non-empty string with empty prefix
        $v = new Validator(['text' => 'hello']);
        $v->rule('startsWith', 'text', '');
        $this->assertTrue($v->validate());

        // Empty string passes without required (treated as not provided)
        $v = new Validator(['text' => '']);
        $v->rule('startsWith', 'text', 'hello');
        $this->assertTrue($v->validate());
    }

    public function testStartsWithInvalidParameter(): void
    {
        // Non-string prefix (integer) - skips in validation
        $v = new Validator(['text' => 'hello']);
        $v->rule('startsWith', 'text', 123);
        $this->assertFalse($v->validate());

        // Array with non-string items
        $v = new Validator(['text' => 'hello']);
        $v->rule('startsWith', 'text', [123, 456]);
        $this->assertFalse($v->validate());
    }

    public function testStartsWithErrorMessage(): void
    {
        $v = new Validator(['url' => 'http://example.com']);
        $v->rule('startsWith', 'url', 'https://');
        $this->assertFalse($v->validate());

        $errors = $v->errors();
        $this->assertArrayHasKey('url', $errors);
        $this->assertStringContainsString("must start with 'https://'", $errors['url'][0]);
    }

    public function testStartsWithCommonUseCases(): void
    {
        // URL protocol
        $v = new Validator(['secure_url' => 'https://api.example.com']);
        $v->rule('startsWith', 'secure_url', 'https://');
        $this->assertTrue($v->validate());

        // Phone country code
        $v = new Validator(['us_phone' => '+1-555-1234']);
        $v->rule('startsWith', 'us_phone', '+1');
        $this->assertTrue($v->validate());

        // SKU prefix
        $v = new Validator(['sku' => 'PROD-ABC-123']);
        $v->rule('startsWith', 'sku', 'PROD-');
        $this->assertTrue($v->validate());

        // File path
        $v = new Validator(['path' => '/var/www/html/index.php']);
        $v->rule('startsWith', 'path', '/var/www/');
        $this->assertTrue($v->validate());

        // Invoice number
        $v = new Validator(['invoice' => 'INV-2024-001']);
        $v->rule('startsWith', 'invoice', 'INV-');
        $this->assertTrue($v->validate());
    }

    public function testStartsWithWithOtherRules(): void
    {
        // Combine with required
        $v = new Validator(['url' => 'https://example.com']);
        $v->rule('required', 'url')
          ->rule('startsWith', 'url', 'https://');
        $this->assertTrue($v->validate());

        // Combine with lengthMin
        $v = new Validator(['code' => 'PROD-12345']);
        $v->rule('startsWith', 'code', 'PROD-')
          ->rule('lengthMin', 'code', 10);
        $this->assertTrue($v->validate());
    }

    public function testStartsWithSpecialCharacters(): void
    {
        // Special characters in prefix
        $v = new Validator(['text' => '@user123']);
        $v->rule('startsWith', 'text', '@');
        $this->assertTrue($v->validate());

        // Dollar sign
        $v = new Validator(['price' => '$19.99']);
        $v->rule('startsWith', 'price', '$');
        $this->assertTrue($v->validate());

        // Hash
        $v = new Validator(['tag' => '#trending']);
        $v->rule('startsWith', 'tag', '#');
        $this->assertTrue($v->validate());
    }

    public function testStartsWithUnicodeCharacters(): void
    {
        // Unicode prefix
        $v = new Validator(['text' => 'こんにちは世界']);
        $v->rule('startsWith', 'text', 'こんにちは');
        $this->assertTrue($v->validate());

        // Emoji prefix
        $v = new Validator(['message' => '🔥 Hot deal!']);
        $v->rule('startsWith', 'message', '🔥');
        $this->assertTrue($v->validate());
    }

    // Ends With Tests
    public function testEndsWithValid(): void
    {
        // Single suffix
        $v = new Validator(['email' => 'user@company.com']);
        $v->rule('endsWith', 'email', '@company.com');
        $this->assertTrue($v->validate());

        // File extension
        $v = new Validator(['file' => 'image.jpg']);
        $v->rule('endsWith', 'file', '.jpg');
        $this->assertTrue($v->validate());

        // Exact match
        $v = new Validator(['text' => 'hello']);
        $v->rule('endsWith', 'text', 'hello');
        $this->assertTrue($v->validate());
    }

    public function testEndsWithValidMultipleSuffixes(): void
    {
        // First suffix matches
        $v = new Validator(['domain' => 'example.com']);
        $v->rule('endsWith', 'domain', ['.com', '.org', '.net']);
        $this->assertTrue($v->validate());

        // Second suffix matches
        $v = new Validator(['domain' => 'example.org']);
        $v->rule('endsWith', 'domain', ['.com', '.org', '.net']);
        $this->assertTrue($v->validate());

        // Third suffix matches
        $v = new Validator(['domain' => 'example.net']);
        $v->rule('endsWith', 'domain', ['.com', '.org', '.net']);
        $this->assertTrue($v->validate());
    }

    public function testEndsWithValidAltSyntax(): void
    {
        $v = new Validator(['file' => 'document.pdf']);
        $v->rules([
            'endsWith' => [
                ['file', '.pdf'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testEndsWithCaseInsensitive(): void
    {
        // Uppercase extension, lowercase suffix
        $v = new Validator(['file' => 'image.JPG']);
        $v->rule('endsWith', 'file', '.jpg', false);
        $this->assertTrue($v->validate());

        // Lowercase extension, uppercase suffix
        $v = new Validator(['file' => 'image.jpg']);
        $v->rule('endsWith', 'file', '.JPG', false);
        $this->assertTrue($v->validate());

        // Mixed case
        $v = new Validator(['text' => 'Hello WoRLd']);
        $v->rule('endsWith', 'text', 'WORLD', false);
        $this->assertTrue($v->validate());
    }

    public function testEndsWithCaseSensitive(): void
    {
        // Case-sensitive (default)
        $v = new Validator(['file' => 'image.jpg']);
        $v->rule('endsWith', 'file', '.jpg');
        $this->assertTrue($v->validate());

        // Explicit case-sensitive
        $v = new Validator(['file' => 'image.pdf']);
        $v->rule('endsWith', 'file', '.pdf', true);
        $this->assertTrue($v->validate());
    }

    public function testEndsWithInvalid(): void
    {
        // Wrong suffix
        $v = new Validator(['file' => 'image.jpg']);
        $v->rule('endsWith', 'file', '.png');
        $this->assertFalse($v->validate());

        // Suffix in middle
        $v = new Validator(['text' => 'hello world']);
        $v->rule('endsWith', 'text', 'hello');
        $this->assertFalse($v->validate());

        // Suffix at beginning
        $v = new Validator(['text' => 'hello world']);
        $v->rule('endsWith', 'text', 'he');
        $this->assertFalse($v->validate());
    }

    public function testEndsWithInvalidCaseSensitive(): void
    {
        // Case mismatch with case-sensitive (default)
        $v = new Validator(['file' => 'image.jpg']);
        $v->rule('endsWith', 'file', '.JPG');
        $this->assertFalse($v->validate());

        // Explicit case-sensitive fails
        $v = new Validator(['file' => 'image.jpg']);
        $v->rule('endsWith', 'file', '.JPG', true);
        $this->assertFalse($v->validate());
    }

    public function testEndsWithInvalidMultipleSuffixes(): void
    {
        // None of the suffixes match
        $v = new Validator(['domain' => 'example.edu']);
        $v->rule('endsWith', 'domain', ['.com', '.org', '.net']);
        $this->assertFalse($v->validate());

        // Close but not exact
        $v = new Validator(['file' => 'image.jpeg']);
        $v->rule('endsWith', 'file', ['.jpg', '.png', '.gif']);
        $this->assertFalse($v->validate());
    }

    public function testEndsWithInvalidNonString(): void
    {
        // Array
        $v = new Validator(['value' => ['file.jpg']]);
        $v->rule('endsWith', 'value', '.jpg');
        $this->assertFalse($v->validate());

        // Integer
        $v = new Validator(['value' => 12345]);
        $v->rule('endsWith', 'value', '5');
        $this->assertFalse($v->validate());

        // Boolean
        $v = new Validator(['value' => true]);
        $v->rule('endsWith', 'value', 'e');
        $this->assertFalse($v->validate());
    }

    public function testEndsWithEmptyString(): void
    {
        // Empty string with empty suffix
        $v = new Validator(['text' => '']);
        $v->rule('endsWith', 'text', '');
        $this->assertTrue($v->validate());

        // Non-empty string with empty suffix
        $v = new Validator(['text' => 'hello']);
        $v->rule('endsWith', 'text', '');
        $this->assertTrue($v->validate());

        // Empty string passes without required (treated as not provided)
        $v = new Validator(['text' => '']);
        $v->rule('endsWith', 'text', 'world');
        $this->assertTrue($v->validate());
    }

    public function testEndsWithInvalidParameter(): void
    {
        // Non-string suffix (integer) - skips in validation
        $v = new Validator(['text' => 'hello']);
        $v->rule('endsWith', 'text', 123);
        $this->assertFalse($v->validate());

        // Array with non-string items
        $v = new Validator(['text' => 'hello']);
        $v->rule('endsWith', 'text', [123, 456]);
        $this->assertFalse($v->validate());
    }

    public function testEndsWithErrorMessage(): void
    {
        $v = new Validator(['file' => 'document.txt']);
        $v->rule('endsWith', 'file', '.pdf');
        $this->assertFalse($v->validate());

        $errors = $v->errors();
        $this->assertArrayHasKey('file', $errors);
        $this->assertStringContainsString("must end with '.pdf'", $errors['file'][0]);
    }

    public function testEndsWithCommonUseCases(): void
    {
        // Domain extension
        $v = new Validator(['website' => 'example.com']);
        $v->rule('endsWith', 'website', '.com');
        $this->assertTrue($v->validate());

        // File extension
        $v = new Validator(['avatar' => 'profile.jpg']);
        $v->rule('endsWith', 'avatar', ['.jpg', '.png', '.gif']);
        $this->assertTrue($v->validate());

        // Email domain
        $v = new Validator(['email' => 'admin@company.com']);
        $v->rule('endsWith', 'email', '@company.com');
        $this->assertTrue($v->validate());

        // URL path
        $v = new Validator(['api_url' => 'https://example.com/api']);
        $v->rule('endsWith', 'api_url', '/api');
        $this->assertTrue($v->validate());

        // License key
        $v = new Validator(['license' => 'ABC-DEF-TRIAL']);
        $v->rule('endsWith', 'license', '-TRIAL');
        $this->assertTrue($v->validate());
    }

    public function testEndsWithWithOtherRules(): void
    {
        // Combine with required
        $v = new Validator(['file' => 'image.jpg']);
        $v->rule('required', 'file')
          ->rule('endsWith', 'file', ['.jpg', '.png']);
        $this->assertTrue($v->validate());

        // Combine with lengthMin
        $v = new Validator(['filename' => 'document.pdf']);
        $v->rule('endsWith', 'filename', '.pdf')
          ->rule('lengthMin', 'filename', 8);
        $this->assertTrue($v->validate());
    }

    public function testEndsWithSpecialCharacters(): void
    {
        // Question mark
        $v = new Validator(['url' => 'https://example.com?']);
        $v->rule('endsWith', 'url', '?');
        $this->assertTrue($v->validate());

        // Exclamation
        $v = new Validator(['text' => 'Hello world!']);
        $v->rule('endsWith', 'text', '!');
        $this->assertTrue($v->validate());

        // Period
        $v = new Validator(['sentence' => 'This is a sentence.']);
        $v->rule('endsWith', 'sentence', '.');
        $this->assertTrue($v->validate());
    }

    public function testEndsWithUnicodeCharacters(): void
    {
        // Unicode suffix
        $v = new Validator(['text' => 'Hello世界']);
        $v->rule('endsWith', 'text', '世界');
        $this->assertTrue($v->validate());

        // Emoji suffix
        $v = new Validator(['message' => 'Great deal 🔥']);
        $v->rule('endsWith', 'message', '🔥');
        $this->assertTrue($v->validate());
    }
}
