<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use Frostybee\Valicomb\Validator;

class ErrorMessagesTest extends BaseTestCase
{
    public function testErrorMessageIncludesFieldName(): void
    {
        $v = new Validator([]);
        $v->rule('required', 'name');
        $v->validate();
        $this->assertSame(["Name is required"], $v->errors('name'));
    }

    /**
     * Test the disabling of prepending the field labels
     * to error messages.
     */
    public function testErrorMessageExcludeFieldName(): void
    {
        $v = new Validator([]);
        $v->setPrependLabels(false);
        $v->rule('required', 'name');
        $v->validate();
        $this->assertSame(["is required"], $v->errors('name'));
    }

    public function testAccurateErrorMessageParams(): void
    {
        $v = new Validator(['num' => 5]);
        $v->rule('min', 'num', 6);
        $v->validate();
        $this->assertSame(["Num must be at least 6"], $v->errors('num'));
    }

    public function testCustomErrorMessage(): void
    {
        $v = new Validator([]);
        $v->rule('required', 'name')->message('Name is required');
        $v->validate();
        $errors = $v->errors('name');
        $this->assertSame('Name is required', $errors[0]);
    }

    public function testCustomLabel(): void
    {
        $v = new Validator([]);
        $v->rule('required', 'name')->message('{field} is required')->label('Custom Name');
        $v->validate();
        $errors = $v->errors('name');
        $this->assertSame('Custom Name is required', $errors[0]);
    }

    public function testCustomLabels(): void
    {
        $messages = [
            'name' => ['Name is required'],
            'email' => ['Email should be a valid email address'],
        ];

        $v = new Validator(['name' => '', 'email' => '$']);
        $v->rule('required', 'name')->message('{field} is required');
        $v->rule('email', 'email')->message('{field} should be a valid email address');

        $v->labels([
            'name' => 'Name',
            'email' => 'Email',
        ]);

        $v->validate();
        $errors = $v->errors();
        $this->assertEquals($messages, $errors);
    }

    public function testMessageWithFieldSet(): void
    {
        $v = new Validator(['name' => ''], [], 'en', __DIR__ . '/../lang');
        $v->rule('required', 'name');
        $v->validate();
        $this->assertEquals($v->errors('name'), ['A value is required for Name']);
    }

    public function testMessageWithFieldAndLabelSet(): void
    {
        $v = new Validator(['name' => ''], [], 'en', __DIR__ . '/../lang');
        $v->rule('required', 'name')->label('my name');
        $v->validate();
        $this->assertEquals($v->errors('name'), ['A value is required for my name']);
    }

    // {value} Placeholder Tests

    /**
     * Test that {value} placeholder is replaced with string value
     */
    public function testValuePlaceholderWithString(): void
    {
        $v = new Validator(['email' => 'not-an-email']);
        $v->rule('email', 'email')->message('{field} "{value}" is not a valid email');
        $v->validate();
        $errors = $v->errors('email');
        $this->assertSame('Email "not-an-email" is not a valid email', $errors[0]);
    }

    /**
     * Test that {value} placeholder displays "null" for null values
     */
    public function testValuePlaceholderWithNull(): void
    {
        $v = new Validator(['field' => null]);
        $v->rule('required', 'field')->message('{field} with value {value} is required');
        $v->validate();
        $errors = $v->errors('field');
        $this->assertSame('Field with value null is required', $errors[0]);
    }

    /**
     * Test that {value} placeholder displays JSON for array values
     */
    public function testValuePlaceholderWithArray(): void
    {
        $v = new Validator(['field' => ['a', 'b']]);
        $v->rule('integer', 'field')->message('{field} value {value} is not an integer');
        $v->validate();
        $errors = $v->errors('field');
        $this->assertStringContainsString('["a","b"]', $errors[0]);
    }

    /**
     * Test that {value} placeholder displays "true" for boolean true
     */
    public function testValuePlaceholderWithBooleanTrue(): void
    {
        $v = new Validator(['field' => true]);
        // Use email rule which will fail for boolean true
        $v->rule('email', 'field')->message('{field} value {value} is not valid');
        $v->validate();
        $errors = $v->errors('field');
        $this->assertSame('Field value true is not valid', $errors[0]);
    }

    /**
     * Test that {value} placeholder displays "false" for boolean false
     */
    public function testValuePlaceholderWithBooleanFalse(): void
    {
        $v = new Validator(['field' => false]);
        // Use email rule which will fail for boolean false
        $v->rule('email', 'field')->message('{field} value {value} is not valid');
        $v->validate();
        $errors = $v->errors('field');
        $this->assertSame('Field value false is not valid', $errors[0]);
    }

    /**
     * Test that {value} placeholder works combined with {field} and sprintf placeholders
     */
    public function testValuePlaceholderWithOtherPlaceholders(): void
    {
        $v = new Validator(['age' => 5]);
        $v->rule('min', 'age', 18)->message('{field} "{value}" must be at least %d');
        $v->validate();
        $errors = $v->errors('age');
        $this->assertSame('Age "5" must be at least 18', $errors[0]);
    }

    /**
     * Test {value} placeholder with custom field label
     */
    public function testValuePlaceholderWithCustomLabel(): void
    {
        $v = new Validator(['user_email' => 'invalid']);
        $v->rule('email', 'user_email')
          ->message('{field} "{value}" is not valid')
          ->label('Email Address');
        $v->validate();
        $errors = $v->errors('user_email');
        $this->assertSame('Email Address "invalid" is not valid', $errors[0]);
    }

    /**
     * Test {value} placeholder with numeric value
     */
    public function testValuePlaceholderWithNumeric(): void
    {
        $v = new Validator(['count' => 1000]);
        $v->rule('max', 'count', 100)->message('{field} value "{value}" exceeds maximum');
        $v->validate();
        $errors = $v->errors('count');
        $this->assertSame('Count value "1000" exceeds maximum', $errors[0]);
    }

    /**
     * Test {value} placeholder preserves message without placeholder
     */
    public function testMessageWithoutValuePlaceholder(): void
    {
        $v = new Validator(['email' => 'invalid']);
        $v->rule('email', 'email')->message('{field} is not a valid email');
        $v->validate();
        $errors = $v->errors('email');
        $this->assertSame('Email is not a valid email', $errors[0]);
    }

    /**
     * Test manual error() method with {value} placeholder
     */
    public function testManualErrorWithValuePlaceholder(): void
    {
        $v = new Validator(['field' => 'test-value']);
        $v->error('field', '{field} with value "{value}" failed validation', [], 'test-value');
        $errors = $v->errors('field');
        $this->assertSame('Field with value "test-value" failed validation', $errors[0]);
    }

    /**
     * Test {value} placeholder with DateTime object
     */
    public function testValuePlaceholderWithDateTime(): void
    {
        $date = new \DateTime('2024-01-15 14:30:00');
        $v = new Validator([]);
        $v->error('field', 'Value {value} is not valid', [], $date);
        $errors = $v->errors('field');
        $this->assertSame('Value 2024-01-15 14:30:00 is not valid', $errors[0]);
    }
}
