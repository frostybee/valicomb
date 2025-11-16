<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;

use function function_exists;

use stdClass;

class NumericValidationTest extends BaseTestCase
{
    // Numeric Tests
    public function testNumericValid(): void
    {
        $v = new Validator(['num' => '42.341569']);
        $v->rule('numeric', 'num');
        $this->assertTrue($v->validate());
    }

    public function testNumericValidAltSyntax(): void
    {
        $v = new Validator(['amount' => 3.14]);
        $v->rules([
            'numeric' => [
                ['amount'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testNumericInvalid(): void
    {
        $v = new Validator(['num' => 'nope']);
        $v->rule('numeric', 'num');
        $this->assertFalse($v->validate());
    }

    public function testNumericInvalidAltSyntax(): void
    {
        $v = new Validator(['amount' => 'banana']);
        $v->rules([
            'numeric' => [
                ['amount'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Integer Tests
    public function testIntegerValid(): void
    {
        $v = new Validator(['num' => '41243']);
        $v->rule('integer', 'num');
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => '-41243']);
        $v->rule('integer', 'num');
        $this->assertTrue($v->validate());
    }

    public function testIntegerValidAltSyntax(): void
    {
        $v = new Validator(['age' => 27]);
        $v->rules([
            'integer' => [
                ['age', true],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testIntegerStrict(): void
    {

        $v = new Validator(['num' => ' 41243']);
        $v->rule('integer', 'num');
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => ' 41243']);
        $v->rule('integer', 'num', true);
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '+41243']);
        $v->rule('integer', 'num');
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => '+41243']);
        $v->rule('integer', 'num', true);
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '-1']);
        $v->rule('integer', 'num', true);
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => '-0']);
        $v->rule('integer', 'num', true);
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '0']);
        $v->rule('integer', 'num', true);
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => '+0']);
        $v->rule('integer', 'num', true);
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '+1']);
        $v->rule('integer', 'num', true);
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '0123']);
        $v->rule('integer', 'num', true);
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '-0123']);
        $v->rule('integer', 'num', true);
        $this->assertFalse($v->validate());
    }

    public function testIntegerInvalid(): void
    {
        $v = new Validator(['num' => '42.341569']);
        $v->rule('integer', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '--1231']);
        $v->rule('integer', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '0x3a']);
        $v->rule('integer', 'num');
        $this->assertFalse($v->validate());
    }

    public function testIntegerInvalidAltSyntax(): void
    {
        $v = new Validator(['age' => 3.14]);
        $v->rules([
            'integer' => [
                ['age'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Min Tests
    public function testMinValid(): void
    {
        $v = new Validator(['num' => 5]);
        $v->rule('min', 'num', 2);
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => 5]);
        $v->rule('min', 'num', 5);
        $this->assertTrue($v->validate());
    }

    public function testMinValidAltSyntax(): void
    {
        $v = new Validator(['age' => 28]);
        $v->rules([
            'min' => [
                ['age', 18],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testMinValidFloat(): void
    {
        if (!function_exists('bccomp')) {
            $this->markTestSkipped("Floating point comparison requires the BC Math extension to be installed");
        }

        $v = new Validator(['num' => 0.9]);
        $v->rule('min', 'num', 0.5);
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => 1 - 0.81]);
        $v->rule('min', 'num', 0.19);
        $this->assertTrue($v->validate());
    }

    public function testMinInvalid(): void
    {
        $v = new Validator(['num' => 5]);
        $v->rule('min', 'num', 6);
        $this->assertFalse($v->validate());

        $v = new Validator(['test' => []]);
        $v->rule('min', 'test', 1);
        $this->assertFalse($v->validate());

        $v = new Validator(['test' => new stdClass()]);
        $v->rule('min', 'test', 1);
        $this->assertFalse($v->validate());
    }

    public function testMinInvalidAltSyntax(): void
    {
        $v = new Validator(['age' => 16]);
        $v->rules([
            'min' => [
                ['age', 18],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testMinInvalidFloat(): void
    {
        $v = new Validator(['num' => 0.5]);
        $v->rule('min', 'num', 0.9);
        $this->assertFalse($v->validate());
    }

    // Max Tests
    public function testMaxValid(): void
    {
        $v = new Validator(['num' => 5]);
        $v->rule('max', 'num', 6);
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => 5]);
        $v->rule('max', 'num', 5);
        $this->assertTrue($v->validate());
    }

    public function testMaxValidAltSyntax(): void
    {
        $v = new Validator(['age' => 10]);
        $v->rules([
            'max' => [
                ['age', 12],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testMaxValidFloat(): void
    {
        if (!function_exists('bccomp')) {
            $this->markTestSkipped("Accurate floating point comparison requires the BC Math extension to be installed");
        }

        $v = new Validator(['num' => 0.4]);
        $v->rule('max', 'num', 0.5);
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => 1 - 0.83]);
        $v->rule('max', 'num', 0.17);
        $this->assertTrue($v->validate());
    }

    public function testMaxInvalid(): void
    {
        $v = new Validator(['num' => 5]);
        $v->rule('max', 'num', 4);
        $this->assertFalse($v->validate());

        $v = new Validator(['test' => []]);
        $v->rule('min', 'test', 1);
        $this->assertFalse($v->validate());

        $v = new Validator(['test' => new stdClass()]);
        $v->rule('min', 'test', 1);
        $this->assertFalse($v->validate());
    }

    public function testMaxInvalidAltSyntax(): void
    {
        $v = new Validator(['age' => 29]);
        $v->rules([
            'max' => [
                ['age', 12],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testMaxInvalidFloat(): void
    {
        $v = new Validator(['num' => 0.9]);
        $v->rule('max', 'num', 0.5);
        $this->assertFalse($v->validate());
    }

    // Between Tests
    public function testBetweenValid(): void
    {
        $v = new Validator(['num' => 5]);
        $v->rule('between', 'num', [3, 7]);
        $this->assertTrue($v->validate());
    }

    public function testBetweenInvalid(): void
    {
        $v = new Validator(['num' => 3]);
        $v->rule('between', 'num', [5, 10]);
        $this->assertFalse($v->validate());
    }

    public function testBetweenInvalidValue(): void
    {
        $v = new Validator(['num' => [3]]);
        $v->rule('between', 'num', [5, 10]);
        $this->assertFalse($v->validate());
    }

    public function testBetweenInvalidRange(): void
    {
        $v = new Validator(['num' => 3]);
        $v->rule('between', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => 3]);
        $v->rule('between', 'num', 5);
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => 3]);
        $v->rule('between', 'num', [5]);
        $this->assertFalse($v->validate());
    }

    // Special Numeric Edge Cases
    public function testZeroStillTriggersValidation(): void
    {
        $v = new Validator(['test' => 0]);
        $v->rule('min', 'test', 1);
        $this->assertFalse($v->validate());
    }

    public function testFalseStillTriggersValidation(): void
    {
        $v = new Validator(['test' => false]);
        $v->rule('min', 'test', 5);
        $this->assertFalse($v->validate());
    }

    // Edge Case Tests

    /**
     * Test min with very large numbers
     */
    public function testMinWithLargeNumbers(): void
    {
        $v = new Validator(['num' => '999999999999']);
        $v->rule('min', 'num', 999999999998);
        $this->assertTrue($v->validate());
    }

    /**
     * Test min with decimal precision (bcmath handles 14 decimal places)
     */
    public function testMinWithDecimalPrecision(): void
    {
        if (!function_exists('bccomp')) {
            $this->markTestSkipped("BC Math extension required for decimal precision tests");
        }

        $v1 = new Validator(['num' => '10.123456789012345']);
        $v1->rule('min', 'num', 10.123456789012344);
        $this->assertTrue($v1->validate());
    }

    /**
     * Test between with negative range
     */
    public function testBetweenWithNegativeRange(): void
    {
        $v = new Validator(['num' => '-5']);
        $v->rule('between', 'num', [-10, -1]);
        $this->assertTrue($v->validate());
    }

    /**
     * Test between with range crossing zero
     */
    public function testBetweenCrossingZero(): void
    {
        $v1 = new Validator(['num' => '-5']);
        $v1->rule('between', 'num', [-10, 10]);
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['num' => '5']);
        $v2->rule('between', 'num', [-10, 10]);
        $this->assertTrue($v2->validate());

        $v3 = new Validator(['num' => '0']);
        $v3->rule('between', 'num', [-10, 10]);
        $this->assertTrue($v3->validate());
    }

    /**
     * Test between with single point range (edge case)
     */
    public function testBetweenWithSinglePointRange(): void
    {
        $v1 = new Validator(['num' => '10']);
        $v1->rule('between', 'num', [10, 10]);
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['num' => '9.99']);
        $v2->rule('between', 'num', [10, 10]);
        $this->assertFalse($v2->validate());
    }

    /**
     * Test between with decimal boundaries
     */
    public function testBetweenWithDecimalBoundaries(): void
    {
        $v = new Validator(['num' => '10.5']);
        $v->rule('between', 'num', [10.1, 10.9]);
        $this->assertTrue($v->validate());
    }

    // Edge Cases
    public function testIntegerEdgeCases(): void
    {
        // Strict mode edge cases
        $v1 = new Validator(['num' => '0']);
        $v1->rule('integer', 'num', true);
        $this->assertTrue($v1->validate(), 'Zero should be valid in strict mode');

        // Note: '-0' is not a valid integer representation in the regex
        // It would be normalized to '0' by PHP, but as a string it doesn't match the pattern
        $v2 = new Validator(['num' => '0']);
        $v2->rule('integer', 'num', true);
        $this->assertTrue($v2->validate(), 'Zero should be valid');

        $v3 = new Validator(['num' => '00']);
        $v3->rule('integer', 'num', true);
        $this->assertFalse($v3->validate(), 'Leading zeros should fail in strict mode');

        // Non-strict mode
        $v4 = new Validator(['num' => 42]);
        $v4->rule('integer', 'num', false);
        $this->assertTrue($v4->validate(), 'Actual integer should pass');
    }
}
