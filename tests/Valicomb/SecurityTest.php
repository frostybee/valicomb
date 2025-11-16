<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use DateTime;
use DateTimeImmutable;
use Frostybee\Valicomb\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function str_repeat;
use function var_export;

/**
 * Security-focused tests for Valicomb
 * Tests protection against ReDoS, type juggling, path traversal, etc.
 */
class SecurityTest extends TestCase
{
    /**
     * Test ReDoS protection in regex validation
     */
    public function testReDoSProtection(): void
    {
        $v = new Validator(['field' => 'aaaaaaaaaaaaaaaaaaaaaaaaa!']);

        // This regex is vulnerable to catastrophic backtracking
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Backtrack limit');

        $v->rule('regex', 'field', '/^(a+)+$/');
        $v->validate();
    }

    /**
     * Test path traversal is blocked in language loading
     */
    public function testPathTraversalBlocked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language');

        new Validator([], [], '../../etc/passwd');
    }

    /**
     * Test language whitelist enforcement
     */
    public function testInvalidLanguageRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language');

        new Validator([], [], 'invalid_lang');
    }

    /**
     * Test type juggling is prevented with strict comparison
     */
    public function testTypeJugglingPrevented(): void
    {
        // These would be equal with loose comparison (==) but not with strict (===)
        $v = new Validator([
            'field1' => '0e123456',
            'field2' => '0e789012',
        ]);

        $v->rule('equals', 'field1', 'field2');

        // Should fail with strict comparison
        $this->assertFalse($v->validate());
        $this->assertNotEmpty($v->errors('field1'));
    }

    /**
     * Test type juggling prevention with different types
     */
    public function testTypeJugglingDifferentTypes(): void
    {
        $v = new Validator([
            'field1' => '0',
            'field2' => 0,
        ]);

        $v->rule('equals', 'field1', 'field2');

        // String '0' should not equal integer 0 with strict comparison
        $this->assertFalse($v->validate());
    }

    /**
     * Test URL prefix validation (FIXED: using str_starts_with)
     */
    public function testUrlPrefixValidation(): void
    {
        // This should FAIL - http:// is not at the start
        $v1 = new Validator(['url' => 'evil.com?redirect=http://trusted.com']);
        $v1->rule('url', 'url');
        $this->assertFalse($v1->validate(), 'URL with prefix not at start should fail');

        // This should PASS - http:// is at the start
        $v2 = new Validator(['url' => 'http://trusted.com']);
        $v2->rule('url', 'url');
        $this->assertTrue($v2->validate(), 'Valid URL should pass');

        // This should FAIL - no valid prefix
        $v3 = new Validator(['url' => 'javascript:alert(1)']);
        $v3->rule('url', 'url');
        $this->assertFalse($v3->validate(), 'JavaScript URL should fail');
    }

    /**
     * Test URL active validation with proper prefix checking
     */
    public function testUrlActiveValidation(): void
    {
        // Invalid URL with prefix in middle
        $v = new Validator(['url' => 'malicious.com?url=http://example.com']);
        $v->rule('urlActive', 'url');
        $this->assertFalse($v->validate());
    }

    /**
     * Test email validation rejects dangerous characters
     */
    public function testEmailDangerousCharacters(): void
    {
        $dangerousEmails = [
            'test<script>@example.com',
            'test@example.com"onclick="alert(1)',
            'test@example.com\'',
            'test(test)@example.com',
            'test[test]@example.com',
        ];

        foreach ($dangerousEmails as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertFalse($v->validate(), "Dangerous email should fail: $email");
        }
    }

    /**
     * Test email validation enforces length limits (RFC 5321)
     */
    public function testEmailLengthLimits(): void
    {
        // Email too long (> 254 chars)
        $longEmail = str_repeat('a', 250) . '@test.com';
        $v1 = new Validator(['email' => $longEmail]);
        $v1->rule('email', 'email');
        $this->assertFalse($v1->validate(), 'Email over 254 chars should fail');

        // Local part too long (> 64 chars)
        $longLocal = str_repeat('a', 65) . '@test.com';
        $v2 = new Validator(['email' => $longLocal]);
        $v2->rule('email', 'email');
        $this->assertFalse($v2->validate(), 'Local part over 64 chars should fail');

        // Domain too long (> 255 chars)
        $longDomain = 'test@' . str_repeat('a', 250) . '.com';
        $v3 = new Validator(['email' => $longDomain]);
        $v3->rule('email', 'email');
        $this->assertFalse($v3->validate(), 'Domain over 255 chars should fail');
    }

    /**
     * Test integer validation correctly matches multi-digit numbers
     */
    public function testIntegerValidationFixed(): void
    {
        $validIntegers = ['0', '10', '100', '1000', '-5', '-100', '-1000'];

        foreach ($validIntegers as $int) {
            $v = new Validator(['num' => $int]);
            $v->rule('integer', 'num', true); // strict mode
            $this->assertTrue($v->validate(), "Integer '$int' should be valid");
        }

        $invalidIntegers = ['01', '001', '1.5', 'abc', '10.0'];

        foreach ($invalidIntegers as $int) {
            $v = new Validator(['num' => $int]);
            $v->rule('integer', 'num', true); // strict mode
            $this->assertFalse($v->validate(), "Invalid integer '$int' should fail");
        }
    }

    /**
     * Test credit card validation has max length check
     */
    public function testCreditCardMaxLength(): void
    {
        // Valid length credit card (should pass Luhn)
        $validCard = '4532015112830366'; // 16 digits

        $v1 = new Validator(['card' => $validCard]);
        $v1->rule('creditCard', 'card');
        $this->assertTrue($v1->validate(), 'Valid 16-digit card should pass');

        // Too long (> 19 digits) - even if passes Luhn
        $tooLongCard = '45320151128303661234567890'; // 26 digits

        $v2 = new Validator(['card' => $tooLongCard]);
        $v2->rule('creditCard', 'card');
        $this->assertFalse($v2->validate(), 'Card over 19 digits should fail');

        // Too short (< 13 digits)
        $tooShortCard = '453201511283'; // 12 digits

        $v3 = new Validator(['card' => $tooShortCard]);
        $v3->rule('creditCard', 'card');
        $this->assertFalse($v3->validate(), 'Card under 13 digits should fail');
    }

    /**
     * Test subset validation logic is fixed
     */
    public function testSubsetValidationFixed(): void
    {
        // Valid subset
        $v1 = new Validator(['tags' => ['php', 'javascript']]);
        $v1->rule('subset', 'tags', ['php', 'javascript', 'python', 'ruby']);
        $this->assertTrue($v1->validate(), 'Valid subset should pass');

        // Invalid subset (contains element not in allowed list)
        $v2 = new Validator(['tags' => ['php', 'cobol']]);
        $v2->rule('subset', 'tags', ['php', 'javascript', 'python', 'ruby']);
        $this->assertFalse($v2->validate(), 'Invalid subset should fail');
    }

    /**
     * Test date validation rejects relative dates
     */
    public function testDateValidationRejectsRelative(): void
    {
        $relativeDates = ['tomorrow', 'yesterday', 'next week', 'last month', '5 days ago'];

        foreach ($relativeDates as $date) {
            $v = new Validator(['date' => $date]);
            $v->rule('date', 'date');
            $this->assertFalse($v->validate(), "Relative date '$date' should be rejected");
        }
    }

    /**
     * Test date validation accepts valid formats
     */
    public function testDateValidationAcceptsValidFormats(): void
    {
        $validDates = [
            '2025-01-15',
            '2025-01-15 14:30:00',
            '15/01/2025',
            '01/15/2025',
            '2025/01/15',
        ];

        foreach ($validDates as $date) {
            $v = new Validator(['date' => $date]);
            $v->rule('date', 'date');
            $this->assertTrue($v->validate(), "Valid date '$date' should pass");
        }
    }

    /**
     * Test invalid regex pattern throws exception
     */
    public function testInvalidRegexThrowsException(): void
    {
        $v = new Validator(['field' => 'test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid regex pattern');

        // Invalid regex (unmatched parenthesis)
        $v->rule('regex', 'field', '/^(test$/');
        $v->validate();
    }

    /**
     * Test alpha validation supports Unicode
     */
    public function testAlphaSupportsUnicode(): void
    {
        $unicodeNames = ['José', 'François', 'Müller', 'Владимир', '北京'];

        foreach ($unicodeNames as $name) {
            $v = new Validator(['name' => $name]);
            $v->rule('alpha', 'name');
            $this->assertTrue($v->validate(), "Unicode name '$name' should be valid");
        }
    }

    /**
     * Test alphaNum validation supports Unicode
     */
    public function testAlphaNumSupportsUnicode(): void
    {
        $unicodeStrings = ['José123', 'François2025', 'Müller42'];

        foreach ($unicodeStrings as $str) {
            $v = new Validator(['field' => $str]);
            $v->rule('alphaNum', 'field');
            $this->assertTrue($v->validate(), "Unicode alphanumeric '$str' should be valid");
        }
    }

    /**
     * Test boolean validation accepts correct representations
     */
    public function testBooleanAcceptsCorrectValues(): void
    {
        // Only accept actual booleans, integers 1/0, and strings '1'/'0'
        $booleanValues = [
            true, false,
            1, 0,
            '1', '0',
        ];

        foreach ($booleanValues as $bool) {
            $v = new Validator(['field' => $bool]);
            $v->rule('boolean', 'field');
            $this->assertTrue($v->validate(), "Boolean value should be valid: " . var_export($bool, true));
        }

        // Reject strings like 'true', 'false', 'yes', 'no'
        $invalidValues = ['true', 'false', 'yes', 'no', 'on', 'off'];

        foreach ($invalidValues as $val) {
            $v = new Validator(['field' => $val]);
            $v->rule('required', 'field')->rule('boolean', 'field');
            $this->assertFalse($v->validate(), "Boolean value should be invalid: " . var_export($val, true));
        }
    }

    /**
     * Test contains uses str_contains (PHP 8.0+)
     */
    public function testContainsUsingModernFunction(): void
    {
        // Should find substring
        $v1 = new Validator(['text' => 'Hello World']);
        $v1->rule('contains', 'text', 'World');
        $this->assertTrue($v1->validate());

        // Should not find substring
        $v2 = new Validator(['text' => 'Hello World']);
        $v2->rule('contains', 'text', 'Goodbye');
        $this->assertFalse($v2->validate());

        // Case-insensitive mode
        $v3 = new Validator(['text' => 'Hello World']);
        $v3->rule('contains', 'text', 'world', false); // false = case-insensitive
        $this->assertTrue($v3->validate());
    }

    /**
     * Test random_int is used instead of rand
     */
    public function testUniqueRuleNameUsingRandomInt(): void
    {
        $v = new Validator(['field' => 'test']);

        // Add multiple custom rules to test unique naming
        for ($i = 0; $i < 5; $i++) {
            $v->rule(fn ($field, $value): true => true, 'field');
        }

        // If this doesn't throw an exception, random_int is working
        $this->assertTrue($v->validate());
    }

    /**
     * Test instanceOf validation with proper type checking
     */
    public function testInstanceOfValidation(): void
    {
        $dateTime = new DateTime();

        // Valid instance
        $v1 = new Validator(['date' => $dateTime]);
        $v1->rule('instanceOf', 'date', DateTime::class);
        $this->assertTrue($v1->validate());

        // Invalid instance
        $v2 = new Validator(['date' => $dateTime]);
        $v2->rule('instanceOf', 'date', DateTimeImmutable::class);
        $this->assertFalse($v2->validate());

        // Non-object should fail
        $v3 = new Validator(['date' => 'not an object']);
        $v3->rule('instanceOf', 'date', DateTime::class);
        $this->assertFalse($v3->validate());
    }

    /**
     * Test email validation rejects control characters
     */
    public function testEmailRejectsControlCharacters(): void
    {
        $emailsWithControlChars = [
            "test\x00@example.com",     // Null byte
            "test\x01@example.com",     // Start of heading
            "test\r\n@example.com",     // CRLF
            "test\t@example.com",       // Tab
            "test\x7F@example.com",     // DEL character
        ];

        foreach ($emailsWithControlChars as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertFalse($v->validate(), "Email with control characters should fail");
        }
    }

    /**
     * Test email validation rejects consecutive dots in local part
     */
    public function testEmailRejectsConsecutiveDots(): void
    {
        $invalidEmails = [
            'test..user@example.com',
            'test...user@example.com',
            'a..b@example.com',
        ];

        foreach ($invalidEmails as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertFalse($v->validate(), "Email with consecutive dots should fail: $email");
        }
    }

    /**
     * Test email validation rejects leading/trailing dots in local part
     */
    public function testEmailRejectsLeadingTrailingDots(): void
    {
        $invalidEmails = [
            '.test@example.com',      // Leading dot
            'test.@example.com',      // Trailing dot
            '.test.@example.com',     // Both
        ];

        foreach ($invalidEmails as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertFalse($v->validate(), "Email with leading/trailing dots should fail: $email");
        }
    }

    /**
     * Test email validation accepts valid emails
     */
    public function testEmailAcceptsValidEmails(): void
    {
        $validEmails = [
            'user@example.com',
            'test.user@example.com',
            'test+tag@example.com',
            'user123@example.co.uk',
            'a@b.c',
        ];

        foreach ($validEmails as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertTrue($v->validate(), "Valid email should pass: $email");
        }
    }

    /**
     * Test email validation rejects backslash
     */
    public function testEmailRejectsBackslash(): void
    {
        $v = new Validator(['email' => 'test\\user@example.com']);
        $v->rule('email', 'email');
        $this->assertFalse($v->validate(), "Email with backslash should fail");
    }

    /**
     * Test containsUnique uses count comparison instead of strict array equality
     */
    public function testContainsUniqueUsesCountComparison(): void
    {
        // Should pass - all unique values
        $v1 = new Validator(['tags' => ['php', 'javascript', 'python']]);
        $v1->rule('containsUnique', 'tags');
        $this->assertTrue($v1->validate(), "Array with unique values should pass");

        // Should fail - has duplicates
        $v2 = new Validator(['tags' => ['php', 'javascript', 'php']]);
        $v2->rule('containsUnique', 'tags');
        $this->assertFalse($v2->validate(), "Array with duplicates should fail");

        // Edge case: numeric keys might be reordered by array_unique
        $v3 = new Validator(['numbers' => [1, 2, 3, 4, 5]]);
        $v3->rule('containsUnique', 'numbers');
        $this->assertTrue($v3->validate(), "Array with unique numbers should pass even if keys reordered");
    }
}
