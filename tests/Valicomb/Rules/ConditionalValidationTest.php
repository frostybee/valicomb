<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;

class ConditionalValidationTest extends BaseTestCase
{
    // Required Tests
    public function testRequiredValid()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->rule('required', 'name');
        $this->assertTrue($v->validate());
    }

    public function testRequiredValidAltSyntax()
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

    public function testRequiredNonExistentField()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->rule('required', 'nonexistent_field');
        $this->assertFalse($v->validate());
    }

    public function testRequiredNonExistentFieldAltSyntax()
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

    public function testRequiredSubfieldsArrayStringValue()
    {
        $v = new Validator(['name' => 'bob']);
        $v->rule('required', ['name.*.red']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredEdgeCases()
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

    public function testRequiredAllowEmpty()
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
    public function testAcceptedValid()
    {
        $v = new Validator(['agree' => 'yes']);
        $v->rule('accepted', 'agree');
        $this->assertTrue($v->validate());
    }

    public function testAcceptedValidAltSyntax()
    {
        $v = new Validator(['remember_me' => true]);
        $v->rules([
            'accepted' => [
                ['remember_me'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testAcceptedInvalid()
    {
        $v = new Validator(['agree' => 'no']);
        $v->rule('accepted', 'agree');
        $this->assertFalse($v->validate());
    }

    public function testAcceptedInvalidAltSyntax()
    {
        $v = new Validator(['remember_me' => false]);
        $v->rules([
            'accepted' => [
                ['remember_me'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testAcceptedNotSet()
    {
        $v = new Validator();
        $v->rule('accepted', 'agree');
        $this->assertFalse($v->validate());
    }

    // Optional Tests
    public function testOptionalProvidedValid()
    {
        $v = new Validator(['address' => 'user@example.com']);
        $v->rule('optional', 'address')->rule('email', 'address');
        $this->assertTrue($v->validate());
    }

    public function testOptionalProvidedValidAltSyntax()
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

    public function testOptionalProvidedInvalid()
    {
        $v = new Validator(['address' => 'userexample.com']);
        $v->rule('optional', 'address')->rule('email', 'address');
        $this->assertFalse($v->validate());
    }

    public function testOptionalProvidedInvalidAltSyntax()
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

    public function testOptionalNotProvided()
    {
        $v = new Validator([]);
        $v->rule('optional', 'address')->rule('email', 'address');
        $this->assertTrue($v->validate());
    }

    // RequiredWith Tests
    public function testRequiredWithValid()
    {
        $v = new Validator(['username' => 'tester', 'password' => 'mypassword']);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidNoParams()
    {
        $v = new Validator([]);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidEmptyString()
    {
        $v = new Validator(['username' => '']);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidNullValue()
    {
        $v = new Validator(['username' => null]);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidAltSyntax()
    {
        $v = new Validator(['username' => 'tester', 'password' => 'mypassword']);
        $v->rules([
            'requiredWith' => [
                ['password', 'username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidArray()
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com', 'password' => 'mypassword']);
        $v->rule('requiredWith', 'password', ['username', 'email']);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithStrictValidArray()
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com', 'password' => 'mypassword']);
        $v->rule('requiredWith', 'password', ['username', 'email'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithStrictInvalidArray()
    {
        $v = new Validator(['email' => 'test@test.com', 'username' => 'batman']);
        $v->rule('requiredWith', 'password', ['username', 'email'], true);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithStrictValidArrayNotRequired()
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com']);
        $v->rule('requiredWith', 'password', ['username', 'email', 'nickname'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithStrictValidArrayEmptyValues()
    {
        $v = new Validator(['email' => '', 'username' => null]);
        $v->rule('requiredWith', 'password', ['username', 'email'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithStrictInvalidArraySingleValue()
    {
        $v = new Validator(['email' => 'tester', 'username' => null]);
        $v->rule('requiredWith', 'password', ['username', 'email'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithValidArrayAltSyntax()
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rules([
            'requiredWith' => [
                ['password', ['username', 'email']],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithInvalid()
    {
        $v = new Validator(['username' => 'tester']);
        $v->rule('requiredWith', 'password', 'username');
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithInvalidAltSyntax()
    {
        $v = new Validator(['username' => 'tester']);
        $v->rules([
            'requiredWith' => [
                ['password', 'username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithInvalidArray()
    {
        $v = new Validator(['email' => 'test@test.com', 'nickname' => 'kevin']);
        $v->rule('requiredWith', 'password', ['username', 'email', 'nickname']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithInvalidStrictArray()
    {
        $v = new Validator(['email' => 'test@test.com', 'username' => 'batman', 'nickname' => 'james']);
        $v->rule('requiredWith', 'password', ['username', 'email', 'nickname'], true);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithInvalidArrayAltSyntax()
    {
        $v = new Validator(['username' => 'tester', 'email' => 'test@test.com']);
        $v->rules([
            'requiredWith' => [
                ['password', ['username', 'email', 'nickname']],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithStrictInvalidArrayAltSyntax()
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
    public function testRequiredWithoutValid()
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidNotPresent()
    {
        $v = new Validator([]);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidEmptyString()
    {
        $v = new Validator(['username' => '', 'password' => 'mypassword']);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidEmptyStringNotPresent()
    {
        $v = new Validator(['username' => '']);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidNullValue()
    {
        $v = new Validator(['username' => null, 'password' => 'mypassword']);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvlidNullValueNotPresent()
    {
        $v = new Validator(['username' => null]);
        $v->rule('requiredWithout', 'password', 'username');
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidAltSyntax()
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rules([
            'requiredWithout' => [
                ['password', 'username'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidAltSyntaxNotPresent()
    {
        $v = new Validator([]);
        $v->rules([
            'requiredWithout' => [
                ['password', 'username'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidArray()
    {
        $v = new Validator(['password' => 'mypassword']);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidArrayNotPresent()
    {
        $v = new Validator([]);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidArrayPartial()
    {
        $v = new Validator(['password' => 'mypassword', 'email' => 'test@test.com']);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidArrayPartial()
    {
        $v = new Validator(['email' => 'test@test.com']);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidArrayStrict()
    {
        $v = new Validator(['email' => 'test@test.com']);
        $v->rule('requiredWithout', 'password', ['username', 'email'], true);
        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutInvalidArrayStrict()
    {
        $v = new Validator([]);
        $v->rule('requiredWithout', 'password', ['username', 'email'], true);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutInvalidArrayNotProvided()
    {
        $v = new Validator(['email' => 'test@test.com']);
        $v->rule('requiredWithout', 'password', ['username', 'email']);
        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutValidArrayAltSyntax()
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
    public function testConditionallyRequiredAuthSampleToken()
    {
        $v = new Validator(['token' => 'ajkdhieyf2834fsuhf8934y89']);
        $v->rule('requiredWithout', 'token', ['email', 'password']);
        $v->rule('requiredWith', 'password', 'email');
        $v->rule('email', 'email');
        $v->rule('optional', 'email');
        $this->assertTrue($v->validate());
    }

    public function testConditionallyRequiredAuthSampleMissingPassword()
    {
        $v = new Validator(['email' => 'test@test.com']);
        $v->rule('requiredWithout', 'token', ['email', 'password']);
        $v->rule('requiredWith', 'password', 'email');
        $v->rule('email', 'email');
        $v->rule('optional', 'email');
        $this->assertFalse($v->validate());
    }

    public function testConditionallyRequiredAuthSampleTokenAltSyntax()
    {
        $v = new Validator(['token' => 'ajkdhieyf2834fsuhf8934y89']);
        $v->rules([
            'requiredWithout' => [
                ['token', ['email', 'password']],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testConditionallyRequiredAuthSampleEmailPasswordAltSyntax()
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
}
