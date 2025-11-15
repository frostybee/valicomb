<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;
use InvalidArgumentException;

class ComparisonValidationTest extends BaseTestCase
{
    // Equals Tests
    public function testEqualsValid()
    {
        $v = new Validator(['foo' => 'bar', 'bar' => 'bar']);
        $v->rule('equals', 'foo', 'bar');
        $this->assertTrue($v->validate());
    }

    public function testEqualsValidAltSyntax()
    {
        $v = new Validator(['password' => 'youshouldnotseethis', 'confirmPassword' => 'youshouldnotseethis']);
        $v->rules([
            'equals' => [
                ['password', 'confirmPassword'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testEqualsInvalid()
    {
        $v = new Validator(['foo' => 'foo', 'bar' => 'bar']);
        $v->rule('equals', 'foo', 'bar');
        $this->assertFalse($v->validate());
    }

    public function testEqualsInvalidAltSyntax()
    {
        $v = new Validator(['password' => 'youshouldnotseethis', 'confirmPassword' => 'differentpassword']);
        $v->rules([
            'equals' => [
                ['password', 'confirmPassword'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testEqualsBothNull()
    {
        $v = new Validator(['foo' => null, 'bar' => null]);
        $v->rule('equals', 'foo', 'bar');
        $this->assertTrue($v->validate());
    }

    public function testEqualsBothNullRequired()
    {
        $v = new Validator(['foo' => null, 'bar' => null]);
        $v->rule('required', ['foo', 'bar']);
        $v->rule('equals', 'foo', 'bar');
        $this->assertFalse($v->validate());
    }

    public function testEqualsBothUnset()
    {
        $v = new Validator(['foo' => 1]);
        $v->rule('equals', 'bar', 'baz');
        $this->assertTrue($v->validate());
    }

    public function testEqualsBothUnsetRequired()
    {
        $v = new Validator(['foo' => 1]);
        $v->rule('required', ['bar', 'baz']);
        $v->rule('equals', 'bar', 'baz');
        $this->assertFalse($v->validate());
    }

    // Nested Equals Tests
    public function testNestedEqualsValid()
    {
        $v = new Validator(['foo' => ['one' => 'bar', 'two' => 'bar']]);
        $v->rule('equals', 'foo.one', 'foo.two');
        $this->assertTrue($v->validate());
    }

    public function testNestedEqualsInvalid()
    {
        $v = new Validator(['foo' => ['one' => 'bar', 'two' => 'baz']]);
        $v->rule('equals', 'foo.one', 'foo.two');
        $this->assertFalse($v->validate());
    }

    public function testNestedEqualsBothNull()
    {
        $v = new Validator(['foo' => ['bar' => null, 'baz' => null]]);
        $v->rule('equals', 'foo.bar', 'foo.baz');
        $this->assertTrue($v->validate());
    }

    public function testNestedEqualsBothNullRequired()
    {
        $v = new Validator(['foo' => ['bar' => null, 'baz' => null]]);
        $v->rule('required', ['foo.bar', 'foo.baz']);
        $v->rule('equals', 'foo.bar', 'foo.baz');
        $this->assertFalse($v->validate());
    }

    public function testNestedEqualsBothUnset()
    {
        $v = new Validator(['foo' => 'bar']);
        $v->rule('equals', 'foo.one', 'foo.two');
        $this->assertTrue($v->validate());
    }

    public function testNestedEqualsBothUnsetRequired()
    {
        $v = new Validator(['foo' => 'bar']);
        $v->rule('required', ['foo.one', 'foo.two']);
        $v->rule('equals', 'foo.one', 'foo.two');
        $this->assertFalse($v->validate());
    }

    // Different Tests
    public function testDifferentValid()
    {
        $v = new Validator(['foo' => 'bar', 'bar' => 'baz']);
        $v->rule('different', 'foo', 'bar');
        $this->assertTrue($v->validate());
    }

    public function testDifferentValidAltSyntax()
    {
        $v = new Validator(['username' => 'test', 'password' => 'test123']);
        $v->rules([
            'different' => [
                ['username', 'password'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testDifferentInvalid()
    {
        $v = new Validator(['foo' => 'baz', 'bar' => 'baz']);
        $v->rule('different', 'foo', 'bar');
        $this->assertFalse($v->validate());
    }

    public function testDifferentInvalidAltSyntax()
    {
        $v = new Validator(['username' => 'test', 'password' => 'test']);
        $v->rules([
            'different' => [
                ['username', 'password'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testDifferentBothNull()
    {
        $v = new Validator(['foo' => null, 'bar' => null]);
        $v->rule('equals', 'foo', 'bar');
        $this->assertTrue($v->validate());
    }

    public function testDifferentBothNullRequired()
    {
        $v = new Validator(['foo' => null, 'bar' => null]);
        $v->rule('required', ['foo', 'bar']);
        $v->rule('equals', 'foo', 'bar');
        $this->assertFalse($v->validate());
    }

    public function testDifferentBothUnset()
    {
        $v = new Validator(['foo' => 1]);
        $v->rule('equals', 'bar', 'baz');
        $this->assertTrue($v->validate());
    }

    public function testDifferentBothUnsetRequired()
    {
        $v = new Validator(['foo' => 1]);
        $v->rule('required', ['bar', 'baz']);
        $v->rule('equals', 'bar', 'baz');
        $this->assertFalse($v->validate());
    }

    // Nested Different Tests
    public function testNestedDifferentValid()
    {
        $v = new Validator(['foo' => ['one' => 'bar', 'two' => 'baz']]);
        $v->rule('different', 'foo.one', 'foo.two');
        $this->assertTrue($v->validate());
    }

    public function testNestedDifferentInvalid()
    {
        $v = new Validator(['foo' => ['one' => 'baz', 'two' => 'baz']]);
        $v->rule('different', 'foo.one', 'foo.two');
        $this->assertFalse($v->validate());
    }

    public function testNestedDifferentBothNull()
    {
        $v = new Validator(['foo' => ['bar' => null, 'baz' => null]]);
        $v->rule('different', 'foo.bar', 'foo.baz');
        $this->assertTrue($v->validate());
    }

    public function testNestedDifferentBothNullRequired()
    {
        $v = new Validator(['foo' => ['bar' => null, 'baz' => null]]);
        $v->rule('required', ['foo.bar', 'foo.baz']);
        $v->rule('different', 'foo.bar', 'foo.baz');
        $this->assertFalse($v->validate());
    }

    public function testNestedDifferentBothUnset()
    {
        $v = new Validator(['foo' => 'bar']);
        $v->rule('different', 'foo.bar', 'foo.baz');
        $this->assertTrue($v->validate());
    }

    public function testNestedDifferentBothUnsetRequired()
    {
        $v = new Validator(['foo' => 'bar']);
        $v->rule('required', ['foo.bar', 'foo.baz']);
        $v->rule('different', 'foo.bar', 'foo.baz');
        $this->assertFalse($v->validate());
    }

    // Additional Nested Field Edge Cases
    public function testEqualsWithNestedFields(): void
    {
        $v1 = new Validator([
            'user' => [
                'email' => 'test@example.com',
                'email_confirm' => 'test@example.com',
            ],
        ]);
        $v1->rule('equals', 'user.email', 'user.email_confirm');
        $this->assertTrue($v1->validate(), 'Nested equal fields should pass');

        $v2 = new Validator([
            'user' => [
                'email' => 'test@example.com',
                'email_confirm' => 'different@example.com',
            ],
        ]);
        $v2->rule('equals', 'user.email', 'user.email_confirm');
        $this->assertFalse($v2->validate(), 'Nested unequal fields should fail');
    }

    // Parameter Validation Tests
    public function testEqualsRequiresFieldParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field name required for equals validation');

        $v = new Validator(['field1' => 'value']);
        $v->rule('equals', 'field1', []); // Missing parameter
        $v->validate();
    }

    public function testEqualsRequiresStringFieldParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field name required for equals validation');

        $v = new Validator(['field1' => 'value']);
        $v->rule('equals', 'field1', 123); // Integer instead of string
        $v->validate();
    }

    public function testDifferentRequiresFieldParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field name required for different validation');

        $v = new Validator(['field1' => 'value']);
        $v->rule('different', 'field1'); // Missing parameter
        $v->validate();
    }

    public function testDifferentRequiresStringFieldParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field name required for different validation');

        $v = new Validator(['field1' => 'value']);
        $v->rule('different', 'field1', null); // Null instead of string
        $v->validate();
    }
}
