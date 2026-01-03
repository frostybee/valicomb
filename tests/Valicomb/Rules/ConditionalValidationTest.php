<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;

class ConditionalValidationTest extends BaseTestCase
{
    // Required Tests
    public function testRequiredValid(): void
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->rule('required', 'name');
        $this->assertTrue($v->validate());
    }

    public function testRequiredValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'spiderman', 'password' => 'Gr33nG0Blin', 'required_but_null' => null]);
        $v->rules([
            'required' => [
                ['username'],
                ['password'],
                ['required_but_null', true], // boolean flag allows empty value so long as the field name is set on the data array
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testRequiredNonExistentField(): void
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->rule('required', 'nonexistent_field');
        $this->assertFalse($v->validate());
    }

    public function testRequiredNonExistentFieldAltSyntax(): void
    {
        $v = new Validator(['boozername' => 'spiderman', 'notPassword' => 'Gr33nG0Blin']);
        $v->rules([
            'required' => [
                ['username'],
                ['password'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testRequiredSubfieldsArrayStringValue(): void
    {
        $v = new Validator(['name' => 'bob']);
        $v->rule('required', ['name.*.red']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredEdgeCases(): void
    {
        $v = new Validator([
            'zero' => 0,
            'zero_txt' => '0',
            'false' => false,
            'empty_array' => [],
        ]);
        $v->rule('required', ['zero', 'zero_txt', 'false', 'empty_array']);

        $this->assertTrue($v->validate());
    }

    public function testRequiredAllowEmpty(): void
    {
        $data = [
            'empty_text' => '',
            'null_value' => null,
            'in_array' => [
                'empty_text' => '',
            ],
        ];

        $v1 = new Validator($data);
        $v1->rule('required', ['empty_text', 'null_value', 'in_array.empty_text']);
        $this->assertFalse($v1->validate());

        $v2 = new Validator($data);
        $v2->rule('required', ['empty_text', 'null_value', 'in_array.empty_text'], true);
        $this->assertTrue($v2->validate());
    }

    // Accepted Tests
    public function testAcceptedValid(): void
    {
        $v = new Validator(['agree' => 'yes']);
        $v->rule('accepted', 'agree');
        $this->assertTrue($v->validate());
    }

    public function testAcceptedValidAltSyntax(): void
    {
        $v = new Validator(['remember_me' => true]);
        $v->rules([
            'accepted' => [
                ['remember_me'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testAcceptedInvalid(): void
    {
        $v = new Validator(['agree' => 'no']);
        $v->rule('accepted', 'agree');
        $this->assertFalse($v->validate());
    }

    public function testAcceptedInvalidAltSyntax(): void
    {
        $v = new Validator(['remember_me' => false]);
        $v->rules([
            'accepted' => [
                ['remember_me'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testAcceptedNotSet(): void
    {
        $v = new Validator();
        $v->rule('accepted', 'agree');
        $this->assertFalse($v->validate());
    }

    // Optional Tests
    public function testOptionalProvidedValid(): void
    {
        $v = new Validator(['address' => 'user@example.com']);
        $v->rule('optional', 'address')->rule('email', 'address');
        $this->assertTrue($v->validate());
    }

    public function testOptionalProvidedValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'batman']);
        $v->rules([
            'alpha' => [
                ['username'],
            ],
            'optional' => [
                ['username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testOptionalProvidedInvalid(): void
    {
        $v = new Validator(['address' => 'userexample.com']);
        $v->rule('optional', 'address')->rule('email', 'address');
        $this->assertFalse($v->validate());
    }

    public function testOptionalProvidedInvalidAltSyntax(): void
    {
        $v = new Validator(['username' => 'batman123']);
        $v->rules([
            'alpha' => [
                ['username'],
            ],
            'optional' => [
                ['username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testOptionalNotProvided(): void
    {
        $v = new Validator([]);
        $v->rule('optional', 'address')->rule('email', 'address');
        $this->assertTrue($v->validate());
    }

    // RequiredWith Tests
    public function testRequiredWithValid(): void
    {
        $v = new Validator(['username' => 'tester', 'password' => 'mypassword']);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidNoParams(): void
    {
        $v = new Validator([]);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidEmptyString(): void
    {
        $v = new Validator(['username' => '']);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidNullValue(): void
    {
        $v = new Validator(['username' => null]);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidAltSyntax(): void
    {
        $v = new Validator(['username' => 'tester', 'password' => 'mypassword']);
        $v->rules([
            'requiredWith' => [
                ['password', 'username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidArray(): void
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com', 'password' => 'mypassword']);
        $v->rule('requiredWith', 'password', ['username', 'email']);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithStrictValidArray(): void
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com', 'password' => 'mypassword']);
        $v->rule('requiredWith', 'password', ['username', 'email'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithStrictInvalidArray(): void
    {
        $v = new Validator(['email' => 'test@test.com', 'username' => 'batman']);
        $v->rule('requiredWith', 'password', ['username', 'email'], true);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithStrictValidArrayNotRequired(): void
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com']);
        $v->rule('requiredWith', 'password', ['username', 'email', 'nickname'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithStrictValidArrayEmptyValues(): void
    {
        $v = new Validator(['email' => '', 'username' => null]);
        $v->rule('requiredWith', 'password', ['username', 'email'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithStrictInvalidArraySingleValue(): void
    {
        $v = new Validator(['email' => 'tester', 'username' => null]);
        $v->rule('requiredWith', 'password', ['username', 'email'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidArrayAltSyntax(): void
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rules([
            'requiredWith' => [
                ['password', ['username', 'email']],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithInvalid(): void
    {
        $v = new Validator(['username' => 'tester']);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithInvalidAltSyntax(): void
    {
        $v = new Validator(['username' => 'tester']);
        $v->rules([
            'requiredWith' => [
                ['password', 'username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithInvalidArray(): void
    {
        $v = new Validator(['email' => 'test@test.com', 'nickname' => 'kevin']);
        $v->rule('requiredWith', 'password', ['username', 'email', 'nickname']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithInvalidStrictArray(): void
    {
        $v = new Validator(['email' => 'test@test.com', 'username' => 'batman', 'nickname' => 'james']);
        $v->rule('requiredWith', 'password', ['username', 'email', 'nickname'], true);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithInvalidArrayAltSyntax(): void
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com']);
        $v->rules([
            'requiredWith' => [
                ['password', ['username', 'email', 'nickname']],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithStrictInvalidArrayAltSyntax(): void
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com', 'nickname' => 'joseph']);
        $v->rules([
            'requiredWith' => [
                ['password', ['username', 'email', 'nickname'], true],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // RequiredWithout Tests
    public function testRequiredWithoutValid(): void
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidNotPresent(): void
    {
        $v = new Validator([]);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidEmptyString(): void
    {
        $v = new Validator(['username' => '', 'password' => 'mypassword']);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidEmptyStringNotPresent(): void
    {
        $v = new Validator(['username' => '']);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidNullValue(): void
    {
        $v = new Validator(['username' => null, 'password' => 'mypassword']);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvlidNullValueNotPresent(): void
    {
        $v = new Validator(['username' => null]);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidAltSyntax(): void
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rules([
            'requiredWithout' => [
                ['password', 'username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidAltSyntaxNotPresent(): void
    {
        $v = new Validator([]);
        $v->rules([
            'requiredWithout' => [
                ['password', 'username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidArray(): void
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidArrayNotPresent(): void
    {
        $v = new Validator([]);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidArrayPartial(): void
    {
        $v = new Validator(['password' => 'mypassword', 'email' => 'test@test.com']);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidArrayPartial(): void
    {
        $v = new Validator(['email' => 'test@test.com']);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidArrayStrict(): void
    {
        $v = new Validator(['email' => 'test@test.com']);
        $v->rule('requiredWithout', 'password', ['username', 'email'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidArrayStrict(): void
    {
        $v = new Validator([]);
        $v->rule('requiredWithout', 'password', ['username', 'email'], true);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutInvalidArrayNotProvided(): void
    {
        $v = new Validator(['email' => 'test@test.com']);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidArrayAltSyntax(): void
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rules([
            'requiredWithout' => [
                ['password', ['username', 'email']],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    // Conditional Auth Sample Tests
    public function testConditionallyRequiredAuthSampleToken(): void
    {
        $v = new Validator(['token' => 'ajkdhieyf2834fsuhf8934y89']);
        $v->rule('requiredWithout', 'token', ['email', 'password']);
        $v->rule('requiredWith', 'password', 'email');
        $v->rule('email', 'email');
        $v->rule('optional', 'email');
        $this->assertTrue($v->validate());
    }

    public function testConditionallyRequiredAuthSampleMissingPassword(): void
    {
        $v = new Validator(['email' => 'test@test.com']);
        $v->rule('requiredWithout', 'token', ['email', 'password']);
        $v->rule('requiredWith', 'password', 'email');
        $v->rule('email', 'email');
        $v->rule('optional', 'email');
        $this->assertFalse($v->validate());
    }

    public function testConditionallyRequiredAuthSampleTokenAltSyntax(): void
    {
        $v = new Validator(['token' => 'ajkdhieyf2834fsuhf8934y89']);
        $v->rules([
            'requiredWithout' => [
                ['token', ['email', 'password']],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testConditionallyRequiredAuthSampleEmailPasswordAltSyntax(): void
    {
        $v = new Validator(['email' => 'test@test.com', 'password' => 'mypassword']);
        $v->rules([
            'requiredWithout' => [
                ['token', ['email', 'password']],
            ],
            'requiredWith' => [
                ['password', ['email']],
            ],
            'email' => [
                ['email'],
            ],
            'optional' => [
                ['email'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    // Edge Cases - All Mode Testing
    public function testRequiredWithAllMode(): void
    {
        // All specified fields are present - should require the validated field
        $v1 = new Validator([
            'field1' => 'value1',
            'field2' => 'value2',
            'target' => '',
        ]);
        $v1->rule('requiredWith', 'target', ['field1', 'field2'], true); // true = all required
        $this->assertFalse($v1->validate(), 'Should fail when all fields present but target empty');

        // Not all specified fields are present - should NOT require the validated field
        $v2 = new Validator([
            'field1' => 'value1',
            'field2' => '',
            'target' => '',
        ]);
        $v2->rule('requiredWith', 'target', ['field1', 'field2'], true); // true = all required
        $this->assertTrue($v2->validate(), 'Should pass when not all fields present');
    }

    public function testRequiredWithoutAllMode(): void
    {
        // All specified fields are empty - should require the validated field
        $v1 = new Validator([
            'field1' => '',
            'field2' => '',
            'target' => '',
        ]);
        $v1->rule('requiredWithout', 'target', ['field1', 'field2'], true); // true = all empty
        $this->assertFalse($v1->validate(), 'Should fail when all fields empty but target empty');

        // Not all specified fields are empty - should NOT require the validated field
        $v2 = new Validator([
            'field1' => 'value1',
            'field2' => '',
            'target' => '',
        ]);
        $v2->rule('requiredWithout', 'target', ['field1', 'field2'], true); // true = all empty
        $this->assertTrue($v2->validate(), 'Should pass when not all fields empty');
    }

    // Nullable Tests

    /**
     * Test that nullable allows null values
     */
    public function testNullableAllowsNull(): void
    {
        $v = new Validator(['field' => null]);
        $v->rule('nullable', 'field')->rule('integer', 'field');
        $this->assertTrue($v->validate());
    }

    /**
     * Test that nullable validates non-null values against subsequent rules
     */
    public function testNullableValidatesNonNullValues(): void
    {
        $v = new Validator(['field' => 'not-an-integer']);
        $v->rule('nullable', 'field')->rule('integer', 'field');
        $this->assertFalse($v->validate());
    }

    /**
     * Test that nullable with valid non-null value passes
     */
    public function testNullableWithValidValue(): void
    {
        $v = new Validator(['field' => 42]);
        $v->rule('nullable', 'field')->rule('integer', 'field');
        $this->assertTrue($v->validate());
    }

    /**
     * Test that nullable with required means null is rejected
     * (required takes precedence - field must exist AND not be null/empty)
     */
    public function testNullableWithRequiredRejectsNull(): void
    {
        $v = new Validator(['field' => null]);
        $v->rule('required', 'field')->rule('nullable', 'field')->rule('integer', 'field');
        // Required rejects null
        $this->assertFalse($v->validate());
    }

    /**
     * Test that nullable field passes when field is missing entirely
     */
    public function testNullableFieldMissing(): void
    {
        $v = new Validator([]);
        $v->rule('nullable', 'field')->rule('integer', 'field');
        $this->assertTrue($v->validate());
    }

    /**
     * Test nullable with multiple subsequent rules
     */
    public function testNullableWithMultipleRules(): void
    {
        $v = new Validator(['age' => null]);
        $v->rule('nullable', 'age')
          ->rule('integer', 'age')
          ->rule('min', 'age', 0);
        $this->assertTrue($v->validate());
    }

    /**
     * Test nullable with nested field using dot notation
     */
    public function testNullableNestedField(): void
    {
        $v = new Validator(['user' => ['parent_id' => null]]);
        $v->rule('nullable', 'user.parent_id')->rule('integer', 'user.parent_id');
        $this->assertTrue($v->validate());
    }

    /**
     * Test nullable with nested field and valid non-null value
     */
    public function testNullableNestedFieldWithValidValue(): void
    {
        $v = new Validator(['user' => ['parent_id' => 123]]);
        $v->rule('nullable', 'user.parent_id')->rule('integer', 'user.parent_id');
        $this->assertTrue($v->validate());
    }

    /**
     * Test nullable with nested field and invalid non-null value
     */
    public function testNullableNestedFieldWithInvalidValue(): void
    {
        $v = new Validator(['user' => ['parent_id' => 'not-an-integer']]);
        $v->rule('nullable', 'user.parent_id')->rule('integer', 'user.parent_id');
        $this->assertFalse($v->validate());
    }

    /**
     * Test nullable with rules() method syntax
     */
    public function testNullableWithRulesSyntax(): void
    {
        $v = new Validator(['field' => null]);
        $v->rules([
            'nullable' => [
                ['field'],
            ],
            'integer' => [
                ['field'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    /**
     * Test nullable with valid value using rules() method syntax
     */
    public function testNullableWithRulesSyntaxValidValue(): void
    {
        $v = new Validator(['field' => 42]);
        $v->rules([
            'nullable' => [
                ['field'],
            ],
            'integer' => [
                ['field'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    /**
     * Test nullable with email validation
     */
    public function testNullableEmail(): void
    {
        // Null should pass
        $v1 = new Validator(['email' => null]);
        $v1->rule('nullable', 'email')->rule('email', 'email');
        $this->assertTrue($v1->validate());

        // Valid email should pass
        $v2 = new Validator(['email' => 'test@example.com']);
        $v2->rule('nullable', 'email')->rule('email', 'email');
        $this->assertTrue($v2->validate());

        // Invalid email should fail
        $v3 = new Validator(['email' => 'invalid-email']);
        $v3->rule('nullable', 'email')->rule('email', 'email');
        $this->assertFalse($v3->validate());
    }

    /**
     * Test nullable combined with optional
     * When both are set, missing field and null both pass
     */
    public function testNullableWithOptional(): void
    {
        // Missing field should pass (optional behavior)
        $v1 = new Validator([]);
        $v1->rule('optional', 'field')
           ->rule('nullable', 'field')
           ->rule('integer', 'field');
        $this->assertTrue($v1->validate());

        // Null should pass (nullable behavior)
        $v2 = new Validator(['field' => null]);
        $v2->rule('optional', 'field')
           ->rule('nullable', 'field')
           ->rule('integer', 'field');
        $this->assertTrue($v2->validate());

        // Valid value should pass
        $v3 = new Validator(['field' => 42]);
        $v3->rule('optional', 'field')
           ->rule('nullable', 'field')
           ->rule('integer', 'field');
        $this->assertTrue($v3->validate());
    }
}
