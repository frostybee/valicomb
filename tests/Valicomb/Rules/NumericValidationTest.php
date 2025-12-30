<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;

use function function_exists;

use stdClass;

use function strpos;

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
     * Test min/max with scientific notation values.
     * bccomp() does NOT support scientific notation, so we must convert it.
     * This reproduces the bug: bccomp(): Argument #2 ($num2) is not well-formed
     */
    public function testMinMaxWithScientificNotation(): void
    {
        if (!function_exists('bccomp')) {
            $this->markTestSkipped("BC Math extension required for this test");
        }

        // Test with scientific notation string
        $v1 = new Validator(['age' => '1e10']);
        $v1->rule('min', 'age', 0);
        $this->assertTrue($v1->validate());

        // Test with large float that may convert to scientific notation
        $v2 = new Validator(['age' => 10000000000]);
        $v2->rule('min', 'age', 0);
        $this->assertTrue($v2->validate());

        // Test max with scientific notation
        $v3 = new Validator(['value' => '1.5e9']);
        $v3->rule('max', 'value', 2000000000);
        $this->assertTrue($v3->validate());

        // Test with very small scientific notation
        $v4 = new Validator(['value' => '1e-5']);
        $v4->rule('min', 'value', 0);
        $this->assertTrue($v4->validate());

        // Test positive with scientific notation
        $v5 = new Validator(['value' => '1e10']);
        $v5->rule('positive', 'value');
        $this->assertTrue($v5->validate());
    }

    /**
     * Test min validation with real-world star data (reproduces reported bug)
     */
    public function testMinWithStarAgeData(): void
    {
        if (!function_exists('bccomp')) {
            $this->markTestSkipped("BC Math extension required for this test");
        }

        // Real data from the reported bug
        $stars = [
            ['name' => 'Sun', 'age' => 4600000000],
            ['name' => 'TRAPPIST-1', 'age' => 7600000000],
            ['name' => "Barnard's Star", 'age' => 10000000000],
        ];

        foreach ($stars as $star) {
            $v = new Validator(['age' => $star['age']]);
            $v->rule('min', 'age', 0);
            $this->assertTrue($v->validate(), "Failed for {$star['name']} with age {$star['age']}");
        }
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

    // Positive Number Tests
    public function testPositiveValid(): void
    {
        $v = new Validator(['num' => 1]);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => 100]);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => 0.1]);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());
    }

    public function testPositiveValidString(): void
    {
        $v = new Validator(['num' => '42']);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => '3.14']);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());
    }

    public function testPositiveValidFloat(): void
    {
        if (!function_exists('bccomp')) {
            $this->markTestSkipped("BC Math extension required for high-precision decimal tests");
        }

        $v = new Validator(['num' => 0.0001]);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());

        $v = new Validator(['num' => '0.00000000000001']);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());
    }

    public function testPositiveInvalidZero(): void
    {
        $v = new Validator(['num' => 0]);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '0']);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => 0.0]);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());
    }

    public function testPositiveInvalidNegative(): void
    {
        $v = new Validator(['num' => -1]);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => -100]);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => -0.1]);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => '-42']);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());
    }

    public function testPositiveInvalidNonNumeric(): void
    {
        $v = new Validator(['num' => 'abc']);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => []]);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());

        $v = new Validator(['num' => new stdClass()]);
        $v->rule('positive', 'num');
        $this->assertFalse($v->validate());
    }

    public function testPositiveValidAltSyntax(): void
    {
        $v = new Validator(['quantity' => 5]);
        $v->rules([
            'positive' => [
                ['quantity'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testPositiveInvalidAltSyntax(): void
    {
        $v = new Validator(['quantity' => -5]);
        $v->rules([
            'positive' => [
                ['quantity'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testPositiveWithLargeNumbers(): void
    {
        $v = new Validator(['num' => '999999999999']);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());
    }

    public function testPositiveWithVerySmallPositiveNumber(): void
    {
        if (!function_exists('bccomp')) {
            $this->markTestSkipped("BC Math extension required for high-precision decimal tests");
        }

        $v = new Validator(['num' => '0.00000000000001']);
        $v->rule('positive', 'num');
        $this->assertTrue($v->validate());
    }

    // Decimal Places Tests
    public function testDecimalPlacesValid(): void
    {
        // Integer values should pass (0 decimal places)
        $v = new Validator(['price' => 100]);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertTrue($v->validate());

        // 1 decimal place should pass with max 2
        $v = new Validator(['price' => 10.5]);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertTrue($v->validate());

        // Exactly 2 decimal places
        $v = new Validator(['price' => 19.99]);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertTrue($v->validate());

        // String with 2 decimals
        $v = new Validator(['price' => '19.99']);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertTrue($v->validate());
    }

    public function testDecimalPlacesValidAltSyntax(): void
    {
        $v = new Validator(['amount' => 3.14]);
        $v->rules([
            'decimalPlaces' => [
                ['amount', 2],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testDecimalPlacesInvalid(): void
    {
        // 3 decimal places with max 2
        $v = new Validator(['price' => 19.999]);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertFalse($v->validate());

        // 4 decimal places with max 2
        $v = new Validator(['price' => 19.9999]);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertFalse($v->validate());

        // String with 3 decimals
        $v = new Validator(['price' => '10.123']);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesInvalidAltSyntax(): void
    {
        $v = new Validator(['amount' => 3.1416]);
        $v->rules([
            'decimalPlaces' => [
                ['amount', 2],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesWithZeroDecimals(): void
    {
        // Only integers allowed
        $v = new Validator(['quantity' => 100]);
        $v->rule('decimalPlaces', 'quantity', 0);
        $this->assertTrue($v->validate());

        // Any decimal should fail
        $v = new Validator(['quantity' => 100.1]);
        $v->rule('decimalPlaces', 'quantity', 0);
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesWithTrailingZeros(): void
    {
        // Trailing zeros ARE significant (per documentation)
        // "10.50" has 2 decimal places
        $v = new Validator(['price' => '10.50']);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertTrue($v->validate());

        // "10.00" has 2 decimal places (trailing zeros count)
        $v = new Validator(['price' => '10.00']);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertTrue($v->validate());

        // "10.00" should fail if max is 1 (has 2 decimal places)
        $v = new Validator(['price' => '10.00']);
        $v->rule('decimalPlaces', 'price', 1);
        $this->assertFalse($v->validate());

        // "10.000" has 3 decimal places
        $v = new Validator(['price' => '10.000']);
        $v->rule('decimalPlaces', 'price', 3);
        $this->assertTrue($v->validate());

        // "10.000" should fail if max is 2 (has 3 decimal places)
        $v = new Validator(['price' => '10.000']);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesWithHighPrecision(): void
    {
        // Financial calculations often need 4 decimals
        $v = new Validator(['rate' => 0.1234]);
        $v->rule('decimalPlaces', 'rate', 4);
        $this->assertTrue($v->validate());

        // 5 decimals should fail
        $v = new Validator(['rate' => 0.12345]);
        $v->rule('decimalPlaces', 'rate', 4);
        $this->assertFalse($v->validate());

        // Very high precision
        $v = new Validator(['measurement' => '3.14159265359']);
        $v->rule('decimalPlaces', 'measurement', 11);
        $this->assertTrue($v->validate());

        $v = new Validator(['measurement' => '3.14159265359']);
        $v->rule('decimalPlaces', 'measurement', 10);
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesWithNegativeNumbers(): void
    {
        // Negative numbers should work the same
        $v = new Validator(['temp' => -10.5]);
        $v->rule('decimalPlaces', 'temp', 2);
        $this->assertTrue($v->validate());

        $v = new Validator(['temp' => -10.555]);
        $v->rule('decimalPlaces', 'temp', 2);
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesWithNonNumeric(): void
    {
        // Non-numeric values should fail
        $v = new Validator(['price' => 'abc']);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertFalse($v->validate());

        $v = new Validator(['price' => []]);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertFalse($v->validate());

        $v = new Validator(['price' => new stdClass()]);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesWithInvalidParams(): void
    {
        // Negative max places (invalid)
        $v = new Validator(['price' => 19.99]);
        $v->rule('decimalPlaces', 'price', -1);
        $this->assertFalse($v->validate());

        // Non-integer parameter
        $v = new Validator(['price' => 19.99]);
        $v->rule('decimalPlaces', 'price', 2.5);
        $this->assertFalse($v->validate());

        // String parameter
        $v = new Validator(['price' => 19.99]);
        $v->rule('decimalPlaces', 'price', '2');
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesErrorMessage(): void
    {
        $v = new Validator(['price' => 19.999]);
        $v->rule('decimalPlaces', 'price', 2);
        $this->assertFalse($v->validate());

        $errors = $v->errors();
        $this->assertArrayHasKey('price', $errors);
        $this->assertStringContainsString('must have at most 2 decimal places', $errors['price'][0]);
    }

    public function testDecimalPlacesCurrencyUseCase(): void
    {
        // Typical currency validation (2 decimal places)
        $validPrices = [19.99, 0.99, 100, 5.5, '10.00'];
        foreach ($validPrices as $price) {
            $v = new Validator(['price' => $price]);
            $v->rule('decimalPlaces', 'price', 2);
            $this->assertTrue($v->validate(), "Price {$price} should be valid");
        }

        $invalidPrices = [19.999, 0.9999, 5.123];
        foreach ($invalidPrices as $price) {
            $v = new Validator(['price' => $price]);
            $v->rule('decimalPlaces', 'price', 2);
            $this->assertFalse($v->validate(), "Price {$price} should be invalid");
        }
    }

    public function testDecimalPlacesPercentageUseCase(): void
    {
        // Percentage with up to 4 decimal places
        $v = new Validator(['percentage' => 3.1416]);
        $v->rule('decimalPlaces', 'percentage', 4);
        $this->assertTrue($v->validate());

        $v = new Validator(['percentage' => 99.9]);
        $v->rule('decimalPlaces', 'percentage', 4);
        $this->assertTrue($v->validate());

        $v = new Validator(['percentage' => 0.12345]);
        $v->rule('decimalPlaces', 'percentage', 4);
        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesWithScientificNotation(): void
    {
        // Scientific notation gets converted to decimal string
        // 1.5e2 = 150 (integer, 0 decimals)
        $v = new Validator(['value' => 1.5e2]);
        $v->rule('decimalPlaces', 'value', 0);
        // This depends on how PHP converts 1.5e2 to string
        // If it becomes "150", it passes. If it becomes "150.0", it might fail
        // Let's test the actual behavior
        $stringValue = (string)(1.5e2);
        if (strpos($stringValue, '.') === false) {
            $this->assertTrue($v->validate());
        }
    }

    public function testDecimalPlacesZeroValue(): void
    {
        // Zero as integer
        $v = new Validator(['value' => 0]);
        $v->rule('decimalPlaces', 'value', 2);
        $this->assertTrue($v->validate());

        // Zero as float
        $v = new Validator(['value' => 0.0]);
        $v->rule('decimalPlaces', 'value', 2);
        $this->assertTrue($v->validate());

        // Zero as string
        $v = new Validator(['value' => '0']);
        $v->rule('decimalPlaces', 'value', 2);
        $this->assertTrue($v->validate());

        // Zero with decimals
        $v = new Validator(['value' => '0.00']);
        $v->rule('decimalPlaces', 'value', 2);
        $this->assertTrue($v->validate());
    }

    public function testDecimalPlacesVeryLargeNumbers(): void
    {
        // Large number with 2 decimals
        $v = new Validator(['amount' => '999999999999.99']);
        $v->rule('decimalPlaces', 'amount', 2);
        $this->assertTrue($v->validate());

        // Large number with 3 decimals
        $v = new Validator(['amount' => '999999999999.999']);
        $v->rule('decimalPlaces', 'amount', 2);
        $this->assertFalse($v->validate());
    }
}
