<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use Frostybee\Valicomb\Validator;

/**
 * Tests for extra input field detection (#382)
 */
class ExtraFieldsTest extends BaseTestCase
{
    // ===========================================
    // getDefinedFields() Tests
    // ===========================================

    /**
     * Test getDefinedFields returns all fields with rules
     */
    public function testGetDefinedFieldsReturnsAllFieldsWithRules(): void
    {
        $v = new Validator(['name' => 'John', 'email' => 'john@example.com', 'age' => 25]);
        $v->rule('required', ['name', 'email']);
        $v->rule('integer', 'age');

        $defined = $v->getDefinedFields();

        $this->assertCount(3, $defined);
        $this->assertContains('name', $defined);
        $this->assertContains('email', $defined);
        $this->assertContains('age', $defined);
    }

    /**
     * Test getDefinedFields returns unique fields when same field has multiple rules
     */
    public function testGetDefinedFieldsReturnsUniqueFields(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $v->rule('required', 'email');
        $v->rule('email', 'email');
        $v->rule('lengthMax', 'email', 254);

        $defined = $v->getDefinedFields();

        $this->assertCount(1, $defined);
        $this->assertContains('email', $defined);
    }

    /**
     * Test getDefinedFields returns empty array when no rules defined
     */
    public function testGetDefinedFieldsEmptyWhenNoRules(): void
    {
        $v = new Validator(['name' => 'John']);

        $this->assertSame([], $v->getDefinedFields());
    }

    /**
     * Test getDefinedFields handles dot notation by using root field
     */
    public function testGetDefinedFieldsHandlesDotNotation(): void
    {
        $v = new Validator(['user' => ['email' => 'test@example.com', 'name' => 'John']]);
        $v->rule('required', 'user.email');
        $v->rule('required', 'user.name');

        $defined = $v->getDefinedFields();

        $this->assertCount(1, $defined);
        $this->assertContains('user', $defined);
    }

    // ===========================================
    // hasExtraFields() Tests
    // ===========================================

    /**
     * Test hasExtraFields returns true when extra fields exist
     */
    public function testHasExtraFieldsReturnsTrueWhenExtraFieldsExist(): void
    {
        $v = new Validator(['name' => 'John', 'hack' => 'malicious']);
        $v->rule('required', 'name');

        $this->assertTrue($v->hasExtraFields());
    }

    /**
     * Test hasExtraFields returns false when no extra fields
     */
    public function testHasExtraFieldsReturnsFalseWhenNoExtraFields(): void
    {
        $v = new Validator(['name' => 'John', 'email' => 'john@example.com']);
        $v->rule('required', ['name', 'email']);

        $this->assertFalse($v->hasExtraFields());
    }

    /**
     * Test hasExtraFields with empty data
     */
    public function testHasExtraFieldsWithEmptyData(): void
    {
        $v = new Validator([]);
        $v->rule('required', 'name');

        $this->assertFalse($v->hasExtraFields());
    }

    // ===========================================
    // getExtraFields() Tests
    // ===========================================

    /**
     * Test getExtraFields returns list of extra field names
     */
    public function testGetExtraFieldsReturnsList(): void
    {
        $v = new Validator([
            'name' => 'John',
            'email' => 'john@example.com',
            'foo' => 'bar',
            'hack' => 'malicious',
        ]);
        $v->rule('required', ['name', 'email']);

        $extra = $v->getExtraFields();

        $this->assertCount(2, $extra);
        $this->assertContains('foo', $extra);
        $this->assertContains('hack', $extra);
    }

    /**
     * Test getExtraFields returns empty array when all fields are defined
     */
    public function testGetExtraFieldsReturnsEmptyWhenAllDefined(): void
    {
        $v = new Validator(['name' => 'John', 'email' => 'john@example.com']);
        $v->rule('required', 'name');
        $v->rule('email', 'email');

        $this->assertSame([], $v->getExtraFields());
    }

    /**
     * Test getExtraFields returns all fields when no rules defined
     */
    public function testGetExtraFieldsReturnsAllWhenNoRules(): void
    {
        $v = new Validator(['name' => 'John', 'age' => 25]);

        $extra = $v->getExtraFields();

        $this->assertCount(2, $extra);
        $this->assertContains('name', $extra);
        $this->assertContains('age', $extra);
    }

    // ===========================================
    // strict() Mode Tests
    // ===========================================

    /**
     * Test strict mode fails validation when extra fields present
     */
    public function testStrictModeFailsWithExtraFields(): void
    {
        $v = new Validator([
            'name' => 'John',
            'email' => 'john@example.com',
            'hack' => 'malicious',
        ]);
        $v->rule('required', ['name', 'email']);
        $v->strict();

        $this->assertFalse($v->validate());
        $errors = $v->errors('hack');
        $this->assertNotFalse($errors);
        $this->assertStringContainsString('not an allowed field', $errors[0]);
    }

    /**
     * Test strict mode passes when no extra fields
     */
    public function testStrictModePassesWithNoExtraFields(): void
    {
        $v = new Validator(['name' => 'John', 'email' => 'john@example.com']);
        $v->rule('required', ['name', 'email']);
        $v->strict();

        $this->assertTrue($v->validate());
    }

    /**
     * Test strict mode can be disabled
     */
    public function testStrictModeCanBeDisabled(): void
    {
        $v = new Validator(['name' => 'John', 'hack' => 'value']);
        $v->rule('required', 'name');
        $v->strict(true);
        $v->strict(false); // Disable

        $this->assertTrue($v->validate());
    }

    /**
     * Test strict mode reports multiple extra fields
     */
    public function testStrictModeReportsMultipleExtraFields(): void
    {
        $v = new Validator([
            'name' => 'John',
            'foo' => 'bar',
            'baz' => 123,
            'hack' => 'malicious',
        ]);
        $v->rule('required', 'name');
        $v->strict();

        $this->assertFalse($v->validate());
        $errors = $v->errors();
        $this->assertArrayHasKey('foo', $errors);
        $this->assertArrayHasKey('baz', $errors);
        $this->assertArrayHasKey('hack', $errors);
    }

    /**
     * Test strict mode with stopOnFirstFail only reports first extra field
     */
    public function testStrictModeWithStopOnFirstFail(): void
    {
        $v = new Validator([
            'name' => 'John',
            'foo' => 'bar',
            'baz' => 123,
        ]);
        $v->rule('required', 'name');
        $v->strict();
        $v->stopOnFirstFail();

        $this->assertFalse($v->validate());
        $errors = $v->errors();
        // Only one error should be reported
        $this->assertCount(1, $errors);
    }

    /**
     * Test strict mode is chainable
     */
    public function testStrictModeIsChainable(): void
    {
        $v = new Validator(['name' => 'John', 'hack' => 'value']);
        $result = $v->rule('required', 'name')->strict();

        // Should return Validator for chaining
        $this->assertInstanceOf(Validator::class, $result);
        $this->assertFalse($result->validate());
    }

    /**
     * Test strict mode with regular validation errors
     */
    public function testStrictModeWithRegularValidationErrors(): void
    {
        $v = new Validator([
            'name' => '',  // Will fail required
            'hack' => 'malicious',  // Extra field
        ]);
        $v->rule('required', 'name');
        $v->strict();

        $this->assertFalse($v->validate());
        $errors = $v->errors();
        // Both errors should be present
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('hack', $errors);
    }

    /**
     * Test strict mode with nested fields (dot notation)
     */
    public function testStrictModeWithNestedFields(): void
    {
        $v = new Validator([
            'user' => ['email' => 'test@example.com'],
            'hack' => 'malicious',
        ]);
        $v->rule('email', 'user.email');
        $v->strict();

        $this->assertFalse($v->validate());
        $errors = $v->errors('hack');
        $this->assertNotFalse($errors);
    }

    /**
     * Test strict mode with withData preserves strict setting
     */
    public function testStrictModePreservedWithWithData(): void
    {
        $v = new Validator(['name' => 'John']);
        $v->rule('required', 'name');
        $v->strict();

        $v2 = $v->withData(['name' => 'Jane', 'hack' => 'value']);

        $this->assertFalse($v2->validate());
        $errors = $v2->errors('hack');
        $this->assertNotFalse($errors);
    }
}
