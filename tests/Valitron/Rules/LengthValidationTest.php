<?php

declare(strict_types=1);

namespace Valitron\Tests\Rules;

use Valitron\Tests\BaseTestCase;
use Valitron\Validator;
use stdClass;

class LengthValidationTest extends BaseTestCase
{
    // Length Tests
    public function testLengthValid()
    {
        $v = new Validator(array('str' => 'happy'));
        $v->rule('length', 'str', 5);
        $this->assertTrue($v->validate());
    }

    public function testLengthValidAltSyntax()
    {
        $v = new Validator(['username' => 'bobburgers']);
        $v->rules([
            'length' => [
                ['username', 10]
            ]
        ]);
        $this->assertTrue($v->validate());
    }

    public function testLengthInvalid()
    {
        $v = new Validator(['str' => 'sad']);
        $v->rule('length', 'str', 6);
        $this->assertFalse($v->validate());

        $v = new Validator(['test' => []]);
        $v->rule('length', 'test', 1);
        $this->assertFalse($v->validate());

        $v = new Validator(['test' => new stdClass]);
        $v->rule('length', 'test', 1);
        $this->assertFalse($v->validate());
    }

    public function testLengthInvalidAltSyntax()
    {
        $v = new Validator(['username' => 'hi']);
        $v->rules([
            'length' => [
                ['username', 10]
            ]
        ]);
        $this->assertFalse($v->validate());
    }

    // Length Between Tests
    public function testLengthBetweenValid()
    {
        $v = new Validator(['str' => 'happy']);
        $v->rule('lengthBetween', 'str', 2, 8);
        $this->assertTrue($v->validate());
    }

    public function testLengthBetweenValidAltSyntax()
    {
        $v = new Validator(['username' => 'bobburgers']);
        $v->rules([
            'lengthBetween' => [
                ['username', 1, 10]
            ]
        ]);
        $this->assertTrue($v->validate());
    }

    public function testLengthBetweenInvalid()
    {
        $v = new Validator(['str' => 'sad']);
        $v->rule('lengthBetween', 'str', 4, 10);
        $this->assertFalse($v->validate());

        $v = new Validator(['test' => []]);
        $v->rule('lengthBetween', 'test', 50, 60);
        $this->assertFalse($v->validate());

        $v = new Validator(['test' => new stdClass]);
        $v->rule('lengthBetween', 'test', 99, 100);
        $this->assertFalse($v->validate());
    }

    public function testLengthBetweenInvalidAltSyntax()
    {
        $v = new Validator(['username' => 'hi']);
        $v->rules([
            'lengthBetween' => [
                ['username', 3, 10]
            ]
        ]);
        $this->assertFalse($v->validate());
    }

    // Length Min Tests
    public function testLengthMinValid()
    {
        $v = new Validator(['str' => 'happy']);
        $v->rule('lengthMin', 'str', 4);
        $this->assertTrue($v->validate());
    }

    public function testLengthMinValidAltSyntax()
    {
        $v = new Validator(['username' => 'martha']);
        $v->rules([
            'lengthMin' => [
                ['username', 5]
            ]
        ]);
        $this->assertTrue($v->validate());
    }

    public function testLengthMinInvalid()
    {
        $v = new Validator(['str' => 'sad']);
        $v->rule('lengthMin', 'str', 4);
        $this->assertFalse($v->validate());
    }

    public function testLengthMinInvalidAltSyntax()
    {
        $v = new Validator(['username' => 'abc']);
        $v->rules([
            'lengthMin' => [
                ['username', 5]
            ]
        ]);
        $this->assertFalse($v->validate());
    }

    // Length Max Tests
    public function testLengthMaxValid()
    {
        $v = new Validator(['str' => 'sad']);
        $v->rule('lengthMax', 'str', 4);
        $this->assertTrue($v->validate());
    }

    public function testLengthMaxValidAltSyntax()
    {
        $v = new Validator(['username' => 'bruins91']);
        $v->rules([
            'lengthMax' => [
                ['username', 10]
            ]
        ]);
        $this->assertTrue($v->validate());
    }

    public function testLengthMaxInvalid()
    {
        $v = new Validator(['str' => 'sad']);
        $v->rule('lengthMax', 'str', 2);
        $this->assertFalse($v->validate());
    }

    public function testLengthMaxInvalidAltSyntax()
    {
        $v = new Validator(['username' => 'bruins91']);
        $v->rules([
            'lengthMax' => [
                ['username', 3]
            ]
        ]);
        $this->assertFalse($v->validate());
    }

    // Edge Cases - Multibyte Strings
    public function testLengthWithMultibyteStrings(): void
    {
        // UTF-8 string with multibyte characters
        $v1 = new Validator(['text' => 'café']); // 4 characters, 5 bytes
        $v1->rule('length', 'text', 4);
        $this->assertTrue($v1->validate(), 'Should count 4 characters, not bytes');

        $v2 = new Validator(['text' => '日本語']); // 3 characters
        $v2->rule('lengthMin', 'text', 3);
        $this->assertTrue($v2->validate(), 'Should handle Japanese characters');

        $v3 = new Validator(['text' => 'Привет']); // 6 characters (Cyrillic)
        $v3->rule('lengthMax', 'text', 6);
        $this->assertTrue($v3->validate(), 'Should handle Cyrillic characters');
    }

    // Parameter Validation Tests
    public function testLengthRequiresIntegerParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Length parameter must be an integer');

        $v = new Validator(['field' => 'test']);
        $v->rule('length', 'field', 'not-an-int'); // String instead of integer
        $v->validate();
    }

    public function testLengthRequiresIntegerMaxParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum length parameter must be an integer');

        $v = new Validator(['field' => 'test']);
        $v->rule('length', 'field', 5, 'not-an-int'); // Second param not integer
        $v->validate();
    }

    public function testLengthBetweenRequiresIntegerParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum and maximum length parameters must be integers');

        $v = new Validator(['field' => 'test']);
        $v->rule('lengthBetween', 'field', '5', 10); // First param is string
        $v->validate();
    }

    public function testLengthBetweenRequiresMaxParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum and maximum length parameters must be integers');

        $v = new Validator(['field' => 'test']);
        $v->rule('lengthBetween', 'field', 5); // Missing max parameter
        $v->validate();
    }

    public function testLengthMinRequiresIntegerParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum length parameter must be an integer');

        $v = new Validator(['field' => 'test']);
        $v->rule('lengthMin', 'field', '5'); // String instead of integer
        $v->validate();
    }

    public function testLengthMaxRequiresIntegerParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum length parameter must be an integer');

        $v = new Validator(['field' => 'test']);
        $v->rule('lengthMax', 'field', '10'); // String instead of integer
        $v->validate();
    }
}
