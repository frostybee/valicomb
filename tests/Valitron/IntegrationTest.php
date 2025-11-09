<?php

declare(strict_types=1);

namespace Valitron\Tests;

use Valitron\Validator;

/**
 * Integration and scenario-based validation tests
 *
 * These tests focus on real-world usage patterns, feature interactions,
 * and complete validation workflows rather than individual validators.
 */
class IntegrationTest extends BaseTestCase
{
    /**
     * Test complete validation workflow with valid data
     */
    public function testBasicValidation(): void
    {
        $data = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'age' => '25',
        ];

        $v = new Validator($data);
        $v->rule('required', ['username', 'email', 'age']);
        $v->rule('email', 'email');
        $v->rule('integer', 'age');

        $this->assertTrue($v->validate());
        $this->assertEmpty($v->errors());
    }

    /**
     * Test complete validation workflow with errors
     */
    public function testValidationWithErrors(): void
    {
        $data = [
            'username' => '',
            'email' => 'invalid-email',
            'age' => 'not-a-number',
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
     * CRITICAL: Test that form data (all strings) is handled correctly
     *
     * This is crucial because $_POST, $_GET, and form submissions
     * always provide data as strings, not native types.
     */
    public function testFormDataHandling(): void
    {
        // Simulate $_POST data - everything comes as strings
        $formData = [
            'age' => '25',           // String, not int
            'price' => '19.99',      // String, not float
            'active' => '1',         // String, not bool
            'count' => '0',          // String zero
            'negative' => '-5',       // String negative number
        ];

        $v = new Validator($formData);
        $v->rule('integer', 'age');
        $v->rule('numeric', 'price');
        $v->rule('boolean', 'active');
        $v->rule('integer', 'count');
        $v->rule('integer', 'negative');

        $this->assertTrue($v->validate(), 'Form data strings should validate correctly');
    }

    /**
     * Test stop on first fail feature
     */
    public function testStopOnFirstFail(): void
    {
        $v = new Validator([
            'field1' => '',
            'field2' => '',
            'field3' => '',
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
     * Test multiple rules chained on a single field
     */
    public function testMultipleRulesChained(): void
    {
        $v = new Validator(['username' => 'john123']);
        $v->rule('required', 'username')
          ->rule('alphaNum', 'username')
          ->rule('lengthBetween', 'username', 3, 20);

        $this->assertTrue($v->validate());
    }

    /**
     * Test rules method (batch rules syntax)
     */
    public function testBulkRulesMethod(): void
    {
        $v = new Validator([
            'email' => 'test@example.com',
            'age' => '25',
            'username' => 'john123',
        ]);

        $v->rules([
            'required' => [['email'], ['age'], ['username']],
            'email' => 'email',
            'integer' => 'age',
            'alphaNum' => 'username',
        ]);

        $this->assertTrue($v->validate());
    }

    /**
     * Test withData method for validator reuse
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
     * Test custom error messages in real scenario
     */
    public function testCustomMessagesScenario(): void
    {
        $v = new Validator(['password' => 'short']);
        $v->rule('required', 'password')->message('Password cannot be empty');
        $v->rule('lengthMin', 'password', 8)->message('Password must be at least 8 characters for security');

        $this->assertFalse($v->validate());
        $errors = $v->errors('password');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('at least 8 characters', $errors[0]);
    }

    /**
     * Test field labels in real scenario
     */
    public function testLabelsScenario(): void
    {
        $v = new Validator(['user_email' => '', 'user_name' => '']);
        $v->labels([
            'user_email' => 'Email Address',
            'user_name' => 'Full Name',
        ]);
        $v->rule('required', ['user_email', 'user_name']);

        $this->assertFalse($v->validate());

        $emailErrors = $v->errors('user_email');
        $nameErrors = $v->errors('user_name');

        $this->assertStringContainsString('Email Address', $emailErrors[0]);
        $this->assertStringContainsString('Full Name', $nameErrors[0]);
    }

    /**
     * Test complex validation scenario: User registration form
     */
    public function testUserRegistrationScenario(): void
    {
        $formData = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123',
            'password_confirm' => 'SecurePass123',
            'age' => '25',
            'terms' => '1',
        ];

        $v = new Validator($formData);

        // All fields required
        $v->rule('required', ['username', 'email', 'password', 'password_confirm', 'age', 'terms']);

        // Username validation
        $v->rule('lengthBetween', 'username', 3, 20);
        $v->rule('slug', 'username');

        // Email validation
        $v->rule('email', 'email');

        // Password validation
        $v->rule('lengthMin', 'password', 8);
        $v->rule('equals', 'password', 'password_confirm');

        // Age validation
        $v->rule('integer', 'age');
        $v->rule('min', 'age', 18);

        // Terms acceptance
        $v->rule('accepted', 'terms');

        $this->assertTrue($v->validate());
    }

    /**
     * Test complex validation scenario: Contact form with optional fields
     */
    public function testContactFormScenario(): void
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '',  // Optional field not provided
            'message' => 'Hello, this is my message.',
        ];

        $v = new Validator($formData);

        // Required fields
        $v->rule('required', ['name', 'email', 'message']);

        // Email validation
        $v->rule('email', 'email');

        // Optional phone - only validate if provided
        $v->rule('optional', 'phone');
        $v->rule('regex', 'phone', '/^\+?[0-9\s\-\(\)]+$/');

        // Message validation
        $v->rule('lengthMin', 'message', 10);

        $this->assertTrue($v->validate());
    }

    /**
     * Test nested array field validation scenario
     */
    public function testNestedFieldsScenario(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                ],
                'settings' => [
                    'notifications' => '1',
                    'theme' => 'dark',
                ],
            ],
        ];

        $v = new Validator($data);

        // Validate nested fields using dot notation
        $v->rule('required', ['user.profile.first_name', 'user.profile.last_name', 'user.profile.email']);
        $v->rule('alpha', ['user.profile.first_name', 'user.profile.last_name']);
        $v->rule('email', 'user.profile.email');
        $v->rule('boolean', 'user.settings.notifications');
        $v->rule('in', 'user.settings.theme', ['light', 'dark', 'auto']);

        $this->assertTrue($v->validate());
    }

    /**
     * Test array field validation with wildcard
     */
    public function testArrayWildcardScenario(): void
    {
        $data = [
            'users' => [
                ['name' => 'John', 'age' => '25'],
                ['name' => 'Jane', 'age' => '30'],
                ['name' => 'Bob', 'age' => '35'],
            ],
        ];

        $v = new Validator($data);

        // Validate all users
        $v->rule('required', ['users.*.name', 'users.*.age']);
        $v->rule('alpha', 'users.*.name');
        $v->rule('integer', 'users.*.age');
        $v->rule('min', 'users.*.age', 18);

        $this->assertTrue($v->validate());
    }
}
