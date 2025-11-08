<?php

declare(strict_types=1);

namespace Valitron\Tests\Rules;

use Valitron\Tests\BaseTestCase;
use Valitron\Validator;

class MiscellaneousValidationTest extends BaseTestCase
{
    // Basic Validation Tests
    public function testValidWithNoRules()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $this->assertTrue($v->validate());
    }

    public function testOptionalFieldFilter()
    {
        $v = new Validator(['foo' => 'bar', 'bar' => 'baz'], ['foo']);
        $this->assertEquals($v->data(), ['foo' => 'bar']);
    }

    public function testAccurateErrorShouldReturnFalse()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->rule('required', 'name');
        $this->assertFalse($v->errors('name'));
    }

    public function testArrayOfFieldsToValidate()
    {
        $v = new Validator(['name' => 'Chester Tester', 'email' => 'chester@tester.com']);
        $v->rule('required', ['name', 'email']);
        $this->assertTrue($v->validate());
    }

    public function testArrayOfFieldsToValidateOneEmpty()
    {
        $v = new Validator(['name' => 'Chester Tester', 'email' => '']);
        $v->rule('required', ['name', 'email']);
        $this->assertFalse($v->validate());
    }

    public function testNoErrorFailOnArray()
    {
        $v = new Validator(['test' => []]);
        $v->rule('slug', 'test');
        $this->assertFalse($v->validate());
    }

    // Custom Rules Tests
    public function testAddRuleClosure()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->addRule('testRule', function () {
            return true;
        });
        $v->rule('testRule', 'name');
        $this->assertTrue($v->validate());
    }

    public function testAddRuleClosureReturnsFalse()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->addRule('testRule', function () {
            return false;
        });
        $v->rule('testRule', 'name');
        $this->assertFalse($v->validate());
    }

    public function testAddRuleClosureWithFieldArray()
    {
        $v = new Validator(['name' => 'Chester Tester', 'email' => 'foo@example.com']);
        $v->addRule('testRule', function () {
            return true;
        });
        $v->rule('testRule', ['name', 'email']);
        $this->assertTrue($v->validate());
    }

    public function testAddRuleClosureWithArrayAsExtraParameter()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->addRule('testRule', function () {
            return true;
        });
        $v->rule('testRule', 'name', ['foo', 'bar']);
        $this->assertTrue($v->validate());
    }

    public function testAddRuleCallback()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->addRule('testRule', 'Valitron\Tests\Rules\sampleFunctionCallback');
        $v->rule('testRule', 'name');
        $this->assertTrue($v->validate());
    }

    public function sampleObjectCallback()
    {
        return true;
    }

    public function sampleObjectCallbackFalse()
    {
        return false;
    }

    public function testAddRuleCallbackArray()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->addRule('testRule', array($this, 'sampleObjectCallback'));
        $v->rule('testRule', 'name');
        $this->assertTrue($v->validate());
    }

    public function testAddRuleCallbackArrayWithArrayAsExtraParameter()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->addRule('testRule', [$this, 'sampleObjectCallback']);
        $v->rule('testRule', 'name', ['foo', 'bar']);
        $this->assertTrue($v->validate());
    }

    public function testAddRuleCallbackArrayWithArrayAsExtraParameterAndCustomMessage()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->addRule('testRule', [$this, 'sampleObjectCallbackFalse']);
        $v->rule('testRule', 'name', ['foo', 'bar'])->message('Invalid name selected.');
        $this->assertFalse($v->validate());
    }

    public function testAddRuleCallbackArrayWithArrayAsExtraParameterAndCustomMessageLabel()
    {
        $v = new Validator(['name' => 'Chester Tester']);
        $v->labels(['name' => 'Name']);
        $v->addRule('testRule', [$this, 'sampleObjectCallbackFalse']);
        $v->rule('testRule', 'name', ['foo', 'bar'])->message('Invalid name selected.');
        $this->assertFalse($v->validate());
    }

    // Bulk Rules Tests
    public function testAcceptBulkRulesWithSingleParams()
    {
        $rules = [
            'required' => 'nonexistent_field',
            'accepted' => 'foo',
            'integer' => 'foo'
        ];

        $v1 = new Validator(['foo' => 'bar', 'bar' => 'baz']);
        $v1->rules($rules);
        $v1->validate();

        $v2 = new Validator(['foo' => 'bar', 'bar' => 'baz']);
        $v2->rule('required', 'nonexistent_field');
        $v2->rule('accepted', 'foo');
        $v2->rule('integer', 'foo');
        $v2->validate();

        $this->assertEquals($v1->errors(), $v2->errors());
    }

    public function testAcceptBulkRulesWithMultipleParams()
    {
        $rules = [
            'required' => [
                [['nonexistent_field', 'other_missing_field']]
            ],
            'equals' => [
                ['foo', 'bar']
            ],
            'length' => [
                ['foo', 5]
            ]
        ];

        $v1 = new Validator(['foo' => 'bar', 'bar' => 'baz']);
        $v1->rules($rules);
        $v1->validate();

        $v2 = new Validator(['foo' => 'bar', 'bar' => 'baz']);
        $v2->rule('required', ['nonexistent_field', 'other_missing_field']);
        $v2->rule('equals', 'foo', 'bar');
        $v2->rule('length', 'foo', 5);
        $v2->validate();

        $this->assertEquals($v1->errors(), $v2->errors());
    }

    public function testAcceptBulkRulesWithNestedRules()
    {
        $rules = [
            'length' => [
                ['foo', 5],
                ['bar', 5]
            ]
        ];

        $v1 = new Validator(['foo' => 'bar', 'bar' => 'baz']);
        $v1->rules($rules);
        $v1->validate();

        $v2 = new Validator(['foo' => 'bar', 'bar' => 'baz']);
        $v2->rule('length', 'foo', 5);
        $v2->rule('length', 'bar', 5);
        $v2->validate();

        $this->assertEquals($v1->errors(), $v2->errors());
    }

    public function testAcceptBulkRulesWithNestedRulesAndMultipleFields()
    {
        $rules = [
            'length' => [
                [['foo', 'bar'], 5],
                ['baz', 5]
            ]
        ];

        $v1 = new Validator(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo']);
        $v1->rules($rules);
        $v1->validate();

        $v2 = new Validator(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo']);
        $v2->rule('length', ['foo', 'bar'], 5);
        $v2->rule('length', 'baz', 5);
        $v2->validate();

        $this->assertEquals($v1->errors(), $v2->errors());
    }

    public function testAcceptBulkRulesWithMultipleArrayParams()
    {
        $rules = [
            'in' => [
                [['foo', 'bar'], ['x', 'y']]
            ]
        ];

        $v1 = new Validator(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo']);
        $v1->rules($rules);
        $v1->validate();

        $v2 = new Validator(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo']);
        $v2->rule('in', ['foo', 'bar'], ['x', 'y']);
        $v2->validate();

        $this->assertEquals($v1->errors(), $v2->errors());
    }

    public function testMalformedBulkRules()
    {
        $v = new Validator();
        $v->rules(
            [
                'required' => ['foo', 'bar']
            ]
        );

        $this->assertFalse($v->validate());
    }

    // Label and Message Tests
    public function testCustomLabelInMessage()
    {
        $v = new Validator([]);
        $v->rule('required', 'name')->message('{field} is required')->label('NAME!!!');
        $v->validate();
        $this->assertEquals(['NAME!!! is required'], $v->errors('name'));
    }

    public function testCustomLabelArrayInMessage()
    {
        $v = new Validator([]);
        $v->rule('required', ['name', 'email'])->message('{field} is required');
        $v->labels([
            'name' => 'Name',
            'email' => 'Email address'
        ]);
        $v->validate();
        $this->assertEquals(['name' => ['Name is required'], 'email' => ['Email address is required']], $v->errors());
    }

    public function testCustomLabelArrayWithoutMessage()
    {
        $v = new Validator([
            'password' => 'foo',
            'passwordConfirm' => 'bar'
        ]);
        $v->rule('equals', 'password', 'passwordConfirm');
        $v->labels([
            'password' => 'Password',
            'passwordConfirm' => 'Password Confirm'
        ]);
        $v->validate();
        $this->assertEquals(['password' => ["Password must be the same as 'Password Confirm'"]], $v->errors());
    }

    /**
     * @dataProvider dataProviderFor_testError
     */
    public function testError($expected, $input, $test, $message)
    {
        $v = new Validator(['test' => $input]);
        $v->error('test', $message, $test);

        $this->assertEquals(['test' => [$expected]], $v->errors());
    }

    public function dataProviderFor_testError()
    {
        return [
            [
                'expected' => 'Test must be at least 140 long',
                'input' => 'tweeet',
                'test' => [140],
                'message' => '{field} must be at least %d long',
            ],
            [
                'expected' => 'Test must be between 1 and 140 characters',
                'input' => [1, 2, 3],
                'test' => [1, 140],
                'message' => 'Test must be between %d and %d characters',
            ],
        ];
    }

    // Chaining and Dot Notation Tests
    public function testChainingRules()
    {
        $v = new Validator(['email_address' => 'test@test.com']);
        $v->rule('required', 'email_address')->rule('email', 'email_address');
        $this->assertTrue($v->validate());
    }

    public function testNestedDotNotation()
    {
        $v = new Validator(['user' => ['first_name' => 'Steve', 'last_name' => 'Smith', 'username' => 'Batman123']]);
        $v->rule('alpha', 'user.first_name')->rule('alpha', 'user.last_name')->rule('alphaNum', 'user.username');
        $this->assertTrue($v->validate());
    }

    // WithData Tests
    public function testWithData()
    {
        $v = new Validator([]);
        $v->rule('required', 'name');
        //validation failed, so must have errors
        $this->assertFalse($v->validate());
        $this->assertNotEmpty($v->errors());

        //create copy with valid data
        $v2 = $v->withData(['name' => 'Chester Tester']);
        $this->assertTrue($v2->validate());
        $this->assertEmpty($v2->errors());

        //create copy with invalid data
        $v3 = $v->withData(['firstname' => 'Chester']);
        $this->assertFalse($v3->validate());
        $this->assertNotEmpty($v3->errors());
    }
}

function sampleFunctionCallback($field, $value, array $params)
{
    return true;
}
