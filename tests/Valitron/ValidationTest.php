<?php

declare(strict_types=1);

namespace Valitron\Tests;

use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Comprehensive validation tests
 * Focus on form data handling (strings) and all validation rules
 */
class ValidationTest extends TestCase
{
    /**
     * Test basic validation flow
     */
    public function testBasicValidation(): void
    {
        $data = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'age' => '25'
        ];

        $v = new Validator($data);
        $v->rule('required', ['username', 'email', 'age']);
        $v->rule('email', 'email');
        $v->rule('integer', 'age');

        $this->assertTrue($v->validate());
        $this->assertEmpty($v->errors());
    }

    /**
     * Test validation with errors
     */
    public function testValidationWithErrors(): void
    {
        $data = [
            'username' => '',
            'email' => 'invalid-email',
            'age' => 'not-a-number'
        ];

        $v = new Validator($data);
        $v->rule('required', 'username');
        $v->rule('email', 'email');
        $v->rule('integer', 'age');

        $this->assertFalse($v->validate());
        $this->assertNotEmpty($v->errors());
        $this->assertArrayHasKey('username', $v->errors());
        $this->assertArrayHasKey('email', $v->errors());
        $this->assertArrayHasKey('age', $v->errors());
    }

    /**
     * Test form data (strings) are handled correctly
     */
    public function testFormDataHandling(): void
    {
        // Simulate $_POST data - everything is strings
        $formData = [
            'age' => '25',           // String, not int
            'price' => '19.99',      // String, not float
            'active' => '1',         // String, not bool
            'count' => '0'           // String zero
        ];

        $v = new Validator($formData);
        $v->rule('integer', 'age');
        $v->rule('numeric', 'price');
        $v->rule('boolean', 'active');
        $v->rule('integer', 'count');

        $this->assertTrue($v->validate(), 'Form data strings should validate correctly');
    }

    /**
     * Test required validation
     */
    public function testRequired(): void
    {
        // Should pass
        $v1 = new Validator(['field' => 'value']);
        $v1->rule('required', 'field');
        $this->assertTrue($v1->validate());

        // Should fail - empty string
        $v2 = new Validator(['field' => '']);
        $v2->rule('required', 'field');
        $this->assertFalse($v2->validate());

        // Should fail - null
        $v3 = new Validator(['field' => null]);
        $v3->rule('required', 'field');
        $this->assertFalse($v3->validate());

        // Should fail - whitespace only
        $v4 = new Validator(['field' => '   ']);
        $v4->rule('required', 'field');
        $this->assertFalse($v4->validate());
    }

    /**
     * Test email validation
     */
    public function testEmail(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user123@test-domain.com'
        ];

        foreach ($validEmails as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertTrue($v->validate(), "Email should be valid: $email");
        }

        $invalidEmails = [
            'invalid',
            '@example.com',
            'test@',
            'test @example.com',
            'test@example',
        ];

        foreach ($invalidEmails as $email) {
            $v = new Validator(['email' => $email]);
            $v->rule('email', 'email');
            $this->assertFalse($v->validate(), "Email should be invalid: $email");
        }
    }

    /**
     * Test URL validation
     */
    public function testUrl(): void
    {
        $validUrls = [
            'http://example.com',
            'https://www.example.com',
            'ftp://ftp.example.com',
            'http://example.com/path/to/page',
            'https://example.com:8080/page?query=value'
        ];

        foreach ($validUrls as $url) {
            $v = new Validator(['url' => $url]);
            $v->rule('url', 'url');
            $this->assertTrue($v->validate(), "URL should be valid: $url");
        }

        $invalidUrls = [
            'example.com',
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            '//example.com'
        ];

        foreach ($invalidUrls as $url) {
            $v = new Validator(['url' => $url]);
            $v->rule('url', 'url');
            $this->assertFalse($v->validate(), "URL should be invalid: $url");
        }
    }

    /**
     * Test integer validation with form data
     */
    public function testInteger(): void
    {
        $validIntegers = ['0', '1', '10', '100', '-5', '-100'];

        foreach ($validIntegers as $int) {
            $v = new Validator(['num' => $int]);
            $v->rule('integer', 'num');
            $this->assertTrue($v->validate(), "Integer should be valid: $int");
        }

        $invalidIntegers = ['1.5', 'abc', '10.0'];

        foreach ($invalidIntegers as $int) {
            $v = new Validator(['num' => $int]);
            $v->rule('required', 'num')->rule('integer', 'num');
            $this->assertFalse($v->validate(), "Integer should be invalid: $int");
        }
    }

    /**
     * Test numeric validation
     */
    public function testNumeric(): void
    {
        $validNumbers = ['0', '1', '10', '10.5', '-5', '-5.5', '0.123'];

        foreach ($validNumbers as $num) {
            $v = new Validator(['num' => $num]);
            $v->rule('numeric', 'num');
            $this->assertTrue($v->validate(), "Number should be valid: $num");
        }

        $invalidNumbers = ['abc', 'twelve'];

        foreach ($invalidNumbers as $num) {
            $v = new Validator(['num' => $num]);
            $v->rule('required', 'num')->rule('numeric', 'num');
            $this->assertFalse($v->validate(), "Number should be invalid: $num");
        }
    }

    /**
     * Test length validation
     */
    public function testLength(): void
    {
        // Exact length
        $v1 = new Validator(['field' => 'hello']);
        $v1->rule('length', 'field', 5);
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['field' => 'hello']);
        $v2->rule('length', 'field', 10);
        $this->assertFalse($v2->validate());

        // Length between
        $v3 = new Validator(['field' => 'hello']);
        $v3->rule('lengthBetween', 'field', 3, 10);
        $this->assertTrue($v3->validate());

        $v4 = new Validator(['field' => 'hi']);
        $v4->rule('lengthBetween', 'field', 3, 10);
        $this->assertFalse($v4->validate());

        // Min length
        $v5 = new Validator(['field' => 'hello']);
        $v5->rule('lengthMin', 'field', 3);
        $this->assertTrue($v5->validate());

        $v6 = new Validator(['field' => 'hi']);
        $v6->rule('lengthMin', 'field', 5);
        $this->assertFalse($v6->validate());

        // Max length
        $v7 = new Validator(['field' => 'hello']);
        $v7->rule('lengthMax', 'field', 10);
        $this->assertTrue($v7->validate());

        $v8 = new Validator(['field' => 'hello world']);
        $v8->rule('lengthMax', 'field', 5);
        $this->assertFalse($v8->validate());
    }

    /**
     * Test min/max validation
     */
    public function testMinMax(): void
    {
        // Min
        $v1 = new Validator(['num' => '10']);
        $v1->rule('min', 'num', 5);
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['num' => '3']);
        $v2->rule('min', 'num', 5);
        $this->assertFalse($v2->validate());

        // Max
        $v3 = new Validator(['num' => '10']);
        $v3->rule('max', 'num', 20);
        $this->assertTrue($v3->validate());

        $v4 = new Validator(['num' => '25']);
        $v4->rule('max', 'num', 20);
        $this->assertFalse($v4->validate());

        // Between
        $v5 = new Validator(['num' => '15']);
        $v5->rule('between', 'num', [10, 20]);
        $this->assertTrue($v5->validate());

        $v6 = new Validator(['num' => '5']);
        $v6->rule('between', 'num', [10, 20]);
        $this->assertFalse($v6->validate());
    }

    /**
     * Test in/notIn validation
     */
    public function testInNotIn(): void
    {
        // In
        $v1 = new Validator(['color' => 'red']);
        $v1->rule('in', 'color', ['red', 'green', 'blue']);
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['color' => 'yellow']);
        $v2->rule('in', 'color', ['red', 'green', 'blue']);
        $this->assertFalse($v2->validate());

        // Not in
        $v3 = new Validator(['color' => 'yellow']);
        $v3->rule('notIn', 'color', ['red', 'green', 'blue']);
        $this->assertTrue($v3->validate());

        $v4 = new Validator(['color' => 'red']);
        $v4->rule('notIn', 'color', ['red', 'green', 'blue']);
        $this->assertFalse($v4->validate());
    }

    /**
     * Test equals/different validation
     */
    public function testEqualsDifferent(): void
    {
        // Equals
        $v1 = new Validator([
            'password' => 'secret123',
            'password_confirm' => 'secret123'
        ]);
        $v1->rule('equals', 'password', 'password_confirm');
        $this->assertTrue($v1->validate());

        $v2 = new Validator([
            'password' => 'secret123',
            'password_confirm' => 'different'
        ]);
        $v2->rule('equals', 'password', 'password_confirm');
        $this->assertFalse($v2->validate());

        // Different
        $v3 = new Validator([
            'new_password' => 'newsecret',
            'old_password' => 'oldsecret'
        ]);
        $v3->rule('different', 'new_password', 'old_password');
        $this->assertTrue($v3->validate());

        $v4 = new Validator([
            'new_password' => 'samesecret',
            'old_password' => 'samesecret'
        ]);
        $v4->rule('different', 'new_password', 'old_password');
        $this->assertFalse($v4->validate());
    }

    /**
     * Test alpha/alphaNum validation
     */
    public function testAlphaAlphaNum(): void
    {
        // Alpha
        $v1 = new Validator(['field' => 'abcABC']);
        $v1->rule('alpha', 'field');
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['field' => 'abc123']);
        $v2->rule('alpha', 'field');
        $this->assertFalse($v2->validate());

        // AlphaNum
        $v3 = new Validator(['field' => 'abc123ABC']);
        $v3->rule('alphaNum', 'field');
        $this->assertTrue($v3->validate());

        $v4 = new Validator(['field' => 'abc-123']);
        $v4->rule('alphaNum', 'field');
        $this->assertFalse($v4->validate());
    }

    /**
     * Test slug validation
     */
    public function testSlug(): void
    {
        $validSlugs = ['hello', 'hello-world', 'hello_world', 'hello-123', 'HELLO'];

        foreach ($validSlugs as $slug) {
            $v = new Validator(['slug' => $slug]);
            $v->rule('slug', 'slug');
            $this->assertTrue($v->validate(), "Slug should be valid: $slug");
        }

        $invalidSlugs = ['hello world', 'hello@world', 'hello.world', 'hello!'];

        foreach ($invalidSlugs as $slug) {
            $v = new Validator(['slug' => $slug]);
            $v->rule('slug', 'slug');
            $this->assertFalse($v->validate(), "Slug should be invalid: $slug");
        }
    }

    /**
     * Test IP validation
     */
    public function testIp(): void
    {
        // General IP
        $v1 = new Validator(['ip' => '192.168.1.1']);
        $v1->rule('ip', 'ip');
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        $v2->rule('ip', 'ip');
        $this->assertTrue($v2->validate());

        $v3 = new Validator(['ip' => 'invalid']);
        $v3->rule('ip', 'ip');
        $this->assertFalse($v3->validate());

        // IPv4
        $v4 = new Validator(['ip' => '192.168.1.1']);
        $v4->rule('ipv4', 'ip');
        $this->assertTrue($v4->validate());

        $v5 = new Validator(['ip' => '2001:0db8:85a3::8a2e:0370:7334']);
        $v5->rule('ipv4', 'ip');
        $this->assertFalse($v5->validate());

        // IPv6
        $v6 = new Validator(['ip' => '2001:0db8:85a3::8a2e:0370:7334']);
        $v6->rule('ipv6', 'ip');
        $this->assertTrue($v6->validate());

        $v7 = new Validator(['ip' => '192.168.1.1']);
        $v7->rule('ipv6', 'ip');
        $this->assertFalse($v7->validate());
    }

    /**
     * Test accepted validation
     */
    public function testAccepted(): void
    {
        $acceptedValues = ['yes', 'on', 1, '1', true];

        foreach ($acceptedValues as $value) {
            $v = new Validator(['terms' => $value]);
            $v->rule('accepted', 'terms');
            $this->assertTrue($v->validate(), "Value should be accepted: " . var_export($value, true));
        }

        $notAcceptedValues = ['no', 'off', 0, '0', false, ''];

        foreach ($notAcceptedValues as $value) {
            $v = new Validator(['terms' => $value]);
            $v->rule('accepted', 'terms');
            $this->assertFalse($v->validate(), "Value should not be accepted: " . var_export($value, true));
        }
    }

    /**
     * Test array validation
     */
    public function testArray(): void
    {
        $v1 = new Validator(['field' => ['a', 'b', 'c']]);
        $v1->rule('array', 'field');
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['field' => 'not an array']);
        $v2->rule('array', 'field');
        $this->assertFalse($v2->validate());
    }

    /**
     * Test contains validation
     */
    public function testContains(): void
    {
        $v1 = new Validator(['text' => 'Hello World']);
        $v1->rule('contains', 'text', 'World');
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['text' => 'Hello World']);
        $v2->rule('contains', 'text', 'Goodbye');
        $this->assertFalse($v2->validate());
    }

    /**
     * Test optional field
     */
    public function testOptional(): void
    {
        // Optional field not provided - should not validate other rules
        $v1 = new Validator([]);
        $v1->rule('optional', 'email');
        $v1->rule('email', 'email');
        $this->assertTrue($v1->validate());

        // Optional field provided - should validate
        $v2 = new Validator(['email' => 'invalid']);
        $v2->rule('optional', 'email');
        $v2->rule('email', 'email');
        $this->assertFalse($v2->validate());

        // Optional field provided with valid value
        $v3 = new Validator(['email' => 'test@example.com']);
        $v3->rule('optional', 'email');
        $v3->rule('email', 'email');
        $this->assertTrue($v3->validate());
    }

    /**
     * Test custom validation rules
     */
    public function testCustomRule(): void
    {
        $v = new Validator(['field' => 'test']);

        $v->rule(function ($field, $value) {
            return $value === 'test';
        }, 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * Test custom error messages
     */
    public function testCustomMessages(): void
    {
        $v = new Validator(['field' => '']);
        $v->rule('required', 'field')->message('This field is absolutely required!');

        $this->assertFalse($v->validate());
        $errors = $v->errors('field');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('absolutely required', $errors[0]);
    }

    /**
     * Test field labels
     */
    public function testLabels(): void
    {
        $v = new Validator(['email' => '']);
        $v->rule('required', 'email')->label('Email Address');

        $this->assertFalse($v->validate());
        $errors = $v->errors('email');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('Email Address', $errors[0]);
    }

    /**
     * Test stop on first fail
     */
    public function testStopOnFirstFail(): void
    {
        $v = new Validator([
            'field1' => '',
            'field2' => '',
            'field3' => ''
        ]);

        $v->stopOnFirstFail(true);
        $v->rule('required', 'field1');
        $v->rule('required', 'field2');
        $v->rule('required', 'field3');

        $this->assertFalse($v->validate());

        // Should only have one error (stopped on first)
        $this->assertCount(1, $v->errors());
    }

    /**
     * Test withData method
     */
    public function testWithData(): void
    {
        $v1 = new Validator(['field' => 'value1']);
        $v1->rule('required', 'field');

        $this->assertTrue($v1->validate());

        // Clone with new data
        $v2 = $v1->withData(['field' => '']);

        $this->assertFalse($v2->validate());
        $this->assertTrue($v1->validate(), 'Original validator should still be valid');
    }

    /**
     * Test arrayHasKeys validation
     */
    public function testArrayHasKeys(): void
    {
        $v1 = new Validator(['data' => ['name' => 'John', 'email' => 'john@example.com']]);
        $v1->rule('arrayHasKeys', 'data', ['name', 'email']);
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['data' => ['name' => 'John']]);
        $v2->rule('required', 'data')->rule('arrayHasKeys', 'data', ['name', 'email']);
        $this->assertFalse($v2->validate());
    }

    /**
     * Test containsUnique validation
     */
    public function testContainsUnique(): void
    {
        $v1 = new Validator(['tags' => ['php', 'javascript', 'python']]);
        $v1->rule('containsUnique', 'tags');
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['tags' => ['php', 'javascript', 'php']]);
        $v2->rule('containsUnique', 'tags');
        $this->assertFalse($v2->validate());
    }

    /**
     * Test date validation
     */
    public function testDate(): void
    {
        $validDates = [
            '2025-01-15',
            '2025-01-15 14:30:00',
            new \DateTime(),
        ];

        foreach ($validDates as $date) {
            $v = new Validator(['date' => $date]);
            $v->rule('date', 'date');
            $this->assertTrue($v->validate(), "Date should be valid");
        }

        $invalidDates = ['not a date', 'tomorrow', '2025-13-45'];

        foreach ($invalidDates as $date) {
            $v = new Validator(['date' => $date]);
            $v->rule('date', 'date');
            $this->assertFalse($v->validate(), "Date should be invalid: $date");
        }
    }

    /**
     * Test dateBefore/dateAfter validation
     */
    public function testDateBeforeAfter(): void
    {
        // Before
        $v1 = new Validator(['date' => '2025-01-01']);
        $v1->rule('dateBefore', 'date', '2025-12-31');
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['date' => '2025-12-31']);
        $v2->rule('dateBefore', 'date', '2025-01-01');
        $this->assertFalse($v2->validate());

        // After
        $v3 = new Validator(['date' => '2025-12-31']);
        $v3->rule('dateAfter', 'date', '2025-01-01');
        $this->assertTrue($v3->validate());

        $v4 = new Validator(['date' => '2025-01-01']);
        $v4->rule('dateAfter', 'date', '2025-12-31');
        $this->assertFalse($v4->validate());
    }

    /**
     * Test regex validation
     */
    public function testRegex(): void
    {
        $v1 = new Validator(['field' => 'ABC123']);
        $v1->rule('regex', 'field', '/^[A-Z0-9]+$/');
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['field' => 'abc123']);
        $v2->rule('regex', 'field', '/^[A-Z0-9]+$/');
        $this->assertFalse($v2->validate());
    }

    /**
     * Test ASCII validation
     */
    public function testAscii(): void
    {
        $v1 = new Validator(['field' => 'Hello World 123']);
        $v1->rule('ascii', 'field');
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['field' => 'Hello 世界']);
        $v2->rule('ascii', 'field');
        $this->assertFalse($v2->validate());
    }

    /**
     * Test multiple rules on single field
     */
    public function testMultipleRules(): void
    {
        $v = new Validator(['username' => 'john123']);
        $v->rule('required', 'username');
        $v->rule('alphaNum', 'username');
        $v->rule('lengthBetween', 'username', 3, 20);

        $this->assertTrue($v->validate());
    }

    /**
     * Test rules method (batch rules)
     */
    public function testRulesMethod(): void
    {
        $v = new Validator(['email' => 'test@example.com', 'age' => '25']);

        $v->rules([
            'required' => [['email'], ['age']],
            'email' => 'email',
            'integer' => 'age'
        ]);

        $this->assertTrue($v->validate());
    }
}
