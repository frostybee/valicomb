<?php

declare(strict_types=1);

namespace Valicomb\Tests\Rules;

use Valicomb\Tests\BaseTestCase;
use Valicomb\Validator;

class ArrayValidationTest extends BaseTestCase
{
    // Array Tests
    public function testArrayValid()
    {
        $v = new Validator(['colors' => ['yellow']]);
        $v->rule('array', 'colors');
        $this->assertTrue($v->validate());
    }

    public function testArrayValidAltSyntax()
    {
        $v = new Validator(['user_notifications' => ['bulletin_notifications' => true, 'marketing_notifications' => false, 'message_notification' => true]]);
        $v->rules([
            'array' => [
                ['user_notifications'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testAssocArrayValid()
    {
        $v = new Validator(['settings' => ['color' => 'yellow']]);
        $v->rule('array', 'settings');
        $this->assertTrue($v->validate());
    }

    public function testArrayInvalidAltSyntax()
    {
        $v = new Validator(['user_notifications' => 'string']);
        $v->rules([
            'array' => [
                ['user_notifications'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testArrayInvalid()
    {
        $v = new Validator(['colors' => 'yellow']);
        $v->rule('array', 'colors');
        $this->assertFalse($v->validate());
    }

    // Array Access Tests
    public function testArrayAccess()
    {
        $v = new Validator(['settings' => ['enabled' => true]]);
        $v->rule('boolean', 'settings.enabled');
        $this->assertTrue($v->validate());
    }

    public function testArrayAccessInvalid()
    {
        $v = new Validator(['settings' => ['threshold' => 500]]);
        $v->rule('max', 'settings.threshold', 100);
        $this->assertFalse($v->validate());
    }

    // Foreach Tests
    public function testForeachDiscreteValues()
    {
        $v = new Validator(['values' => [5, 10, 15, 20, 25]]);
        $v->rule('integer', 'values.*');
        $this->assertTrue($v->validate());
    }

    public function testForeachAssocValues()
    {
        $v = new Validator(['values' => [
            'foo' => 5,
            'bar' => 10,
            'baz' => 15,
        ]]);
        $v->rule('integer', 'values.*');
        $this->assertTrue($v->validate());
    }

    public function testForeachAssocValuesFails()
    {
        $v = new Validator(['values' => [
            'foo' => 5,
            'bar' => 10,
            'baz' => 'faz',
        ]]);
        $v->rule('integer', 'values.*');
        $this->assertFalse($v->validate());
    }

    public function testForeachArrayAccess()
    {
        $v = new Validator(['settings' => [
            ['enabled' => true],
            ['enabled' => true],
        ]]);
        $v->rule('boolean', 'settings.*.enabled');
        $this->assertTrue($v->validate());
    }

    public function testForeachArrayAccessInvalid()
    {
        $v = new Validator(['settings' => [
            ['threshold' => 50],
            ['threshold' => 500],
        ]]);
        $v->rule('max', 'settings.*.threshold', 100);
        $this->assertFalse($v->validate());
    }

    public function testNestedForeachArrayAccess()
    {
        $v = new Validator(['widgets' => [
            ['settings' => [
                ['enabled' => true],
                ['enabled' => true],
            ]],
            ['settings' => [
                ['enabled' => true],
                ['enabled' => true],
            ]],
        ]]);
        $v->rule('boolean', 'widgets.*.settings.*.enabled');
        $this->assertTrue($v->validate());
    }

    public function testNestedForeachArrayAccessInvalid()
    {
        $v = new Validator(['widgets' => [
            ['settings' => [
                ['threshold' => 50],
                ['threshold' => 90],
            ]],
            ['settings' => [
                ['threshold' => 40],
                ['threshold' => 500],
            ]],
            ['settings' => [
                ['threshold' => 40],
                ['threshold' => 500],
            ]],
        ]]);
        $v->rule('max', 'widgets.*.settings.*.threshold', 100);
        $this->assertFalse($v->validate());
    }

    // In Tests
    public function testInValid()
    {
        $v = new Validator(['color' => 'green']);
        $v->rule('in', 'color', ['red', 'green', 'blue']);
        $this->assertTrue($v->validate());
    }

    public function testInValidAltSyntax()
    {
        $v = new Validator(['color' => 'purple']);
        $v->rules([
            'in' => [
                ['color', ['blue', 'green', 'red', 'purple']],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testInInvalidAltSyntax()
    {
        $v = new Validator(['color' => 'orange']);
        $v->rules([
            'in' => [
                ['color', ['blue', 'green', 'red', 'purple']],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testInValidAssociativeArray()
    {
        $v = new Validator(['color' => 'green']);
        $v->rule('in', 'color', [
            'red' => 'Red',
            'green' => 'Green',
            'blue' => 'Blue',
        ]);
        $this->assertTrue($v->validate());
    }

    public function testInStrictInvalid()
    {
        $v = new Validator(['color' => '1']);
        $v->rule('in', 'color', [1, 2, 3], true);
        $this->assertFalse($v->validate());
    }

    public function testInInvalid()
    {
        $v = new Validator(['color' => 'yellow']);
        $v->rule('in', 'color', ['red', 'green', 'blue']);
        $this->assertFalse($v->validate());
    }

    public function testInRuleSearchesValuesForNumericArray()
    {
        $v = new Validator(['color' => 'purple']);

        $v->rules([
            'in' => [
                ['color', [3 => 'green', 2 => 'purple']],
            ],
        ]);

        $this->assertTrue($v->validate());
    }

    public function testInRuleSearchesKeysForAssociativeArray()
    {
        $v = new Validator(['color' => 'c-2']);

        $v->rules([
            'in' => [
                ['color', ['c-3' => 'green', 'c-2' => 'purple']],
            ],
        ]);

        $this->assertTrue($v->validate());
    }

    public function testInRuleSearchesKeysWhenForcedTo()
    {
        $v = new Validator(['color' => 2]);

        $v->rules([
            'in' => [
                ['color', ['3' => 'green', '2' => 'purple'], null, true],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    // NotIn Tests
    public function testNotInValid()
    {
        $v = new Validator(['color' => 'yellow']);
        $v->rule('notIn', 'color', ['red', 'green', 'blue']);
        $this->assertTrue($v->validate());
    }

    public function testNotInValidAltSyntax()
    {
        $v = new Validator(['color' => 'purple']);
        $v->rules([
            'notIn' => [
                ['color', ['blue', 'green', 'red', 'yellow']],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testNotInInvalid()
    {
        $v = new Validator(['color' => 'blue']);
        $v->rule('notIn', 'color', ['red', 'green', 'blue']);
        $this->assertFalse($v->validate());
    }

    public function testNotInInvalidAltSyntax()
    {
        $v = new Validator(['color' => 'yellow']);
        $v->rules([
            'notIn' => [
                ['color', ['blue', 'green', 'red', 'yellow']],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // ListContains Tests
    public function testListContainsValid()
    {
        $v = new Validator(['color' => ['blue', 'green', 'red', 'yellow']]);
        $v->rule('listContains', 'color', 'red');
        $this->assertTrue($v->validate());
    }

    public function testListContainsValidAltSyntax()
    {
        $v = new Validator(['color' => ['blue', 'green', 'red']]);
        $v->rules([
            'listContains' => [
                ['color', 'red'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testListContainsInvalidAltSyntax()
    {
        $v = new Validator(['color' => ['blue', 'green', 'red']]);
        $v->rules([
            'listContains' => [
                ['color', 'yellow'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // ListContains Edge Case Tests

    public function testListContainsWithEmptyArray()
    {
        $v = new Validator(['tags' => []]);
        $v->rule('listContains', 'tags', 'php');
        $this->assertFalse($v->validate());
    }

    public function testListContainsNonStrictComparison()
    {
        $v = new Validator(['numbers' => [1, 2, 3]]);
        $v->rule('listContains', 'numbers', '1'); // String '1', default non-strict
        $this->assertTrue($v->validate());
    }

    public function testListContainsStrictComparison()
    {
        $v = new Validator(['numbers' => [1, 2, 3]]);
        $v->rule('listContains', 'numbers', '1', true); // true = strict
        $this->assertFalse($v->validate());
    }

    public function testListContainsWithIntegers()
    {
        $v = new Validator(['ids' => [10, 20, 30]]);
        $v->rule('listContains', 'ids', 20);
        $this->assertTrue($v->validate());
    }

    public function testListContainsWithAssociativeArray()
    {
        $v = new Validator(['data' => ['name' => 'John', 'email' => 'john@example.com']]);
        $v->rule('listContains', 'data', 'name'); // Should check keys
        $this->assertTrue($v->validate());
    }

    public function testListContainsWithAssociativeArrayValues()
    {
        $v = new Validator(['data' => ['name' => 'John', 'email' => 'john@example.com']]);
        $v->rule('listContains', 'data', 'John'); // Should NOT find values
        $this->assertFalse($v->validate());
    }

    public function testListContainsForceAssociative()
    {
        $v = new Validator(['items' => ['a', 'b', 'c']]);
        $v->rule('listContains', 'items', '0', false, true); // Force check keys
        $this->assertTrue($v->validate());
    }

    public function testListContainsWithBooleans()
    {
        $v1 = new Validator(['flags' => [true, false]]);
        $v1->rule('listContains', 'flags', true);
        $this->assertTrue($v1->validate());

        $v2 = new Validator(['flags' => [true, false]]);
        $v2->rule('listContains', 'flags', false);
        $this->assertTrue($v2->validate());
    }

    public function testListContainsWithZero()
    {
        $v = new Validator(['numbers' => [0, 1, 2]]);
        $v->rule('listContains', 'numbers', 0);
        $this->assertTrue($v->validate());
    }

    public function testListContainsWithNull()
    {
        $v = new Validator(['values' => [null, 'a', 'b']]);
        $v->rule('listContains', 'values', null);
        $this->assertTrue($v->validate());
    }

    public function testListContainsCaseSensitive()
    {
        $v = new Validator(['tags' => ['PHP', 'JavaScript', 'Python']]);
        $v->rule('listContains', 'tags', 'php'); // lowercase
        $this->assertFalse($v->validate());
    }

    public function testListContainsWithFloats()
    {
        $v = new Validator(['prices' => [9.99, 19.99, 29.99]]);
        $v->rule('listContains', 'prices', 19.99);
        $this->assertTrue($v->validate());
    }

    // Subset Tests
    public function testSubsetValid()
    {
        // numeric values
        $v = new Validator(['test_field' => [81, 3, 15]]);
        $v->rule('subset', 'test_field', [45, 15, 3, 7, 28, 81]);
        $this->assertTrue($v->validate());

        // string values
        $v = new Validator(['test_field' => ['white', 'green', 'blue']]);
        $v->rule('subset', 'test_field', ['green', 'orange', 'blue', 'yellow', 'white', 'brown']);
        $this->assertTrue($v->validate());

        // mixed values
        $v = new Validator(['test_field' => [81, false, 'orange']]);
        $v->rule('subset', 'test_field', [45, 'green', true, 'orange', null, 81, false]);
        $this->assertTrue($v->validate());

        // string value and validation target cast to array
        $v = new Validator(['test_field' => 'blue']);
        $v->rule('subset', 'test_field', 'blue');
        $this->assertTrue($v->validate());
    }

    public function testSubsetValidAltSyntax()
    {
        $v = new Validator(['colors' => ['green', 'blue']]);
        $v->rules([
            'subset' => [
                ['colors', ['orange', 'green', 'blue', 'red']],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testSubsetInvalid()
    {
        $v = new Validator(['test_field' => [81, false, 'orange']]);
        $v->rule('subset', 'test_field', [45, 'green', true, 'orange', null, false, 7]);
        $this->assertFalse($v->validate());
    }

    public function testSubsetInvalidAltSyntax()
    {
        $v = new Validator(['colors' => ['purple', 'blue']]);
        $v->rules([
            'subset' => [
                ['colors', ['orange', 'green', 'blue', 'red']],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testSubsetInvalidValue()
    {
        $v = new Validator(['test_field' => 'black 45']);
        $v->rule('subset', 'test_field', ['black', 45]);
        $this->assertFalse($v->validate());
    }

    public function testSubsetInvalidRule()
    {
        // rule value has invalid type
        $v = new Validator(['test_field' => ['black', 45]]);
        $v->rule('subset', 'test_field', 'black 45');
        $this->assertFalse($v->validate());

        // rule value not specified
        $v = new Validator(['test_field' => ['black', 45]]);
        $v->rule('subset', 'test_field');
        $this->assertFalse($v->validate());
    }

    public function testSubsetAcceptNullValue()
    {
        // rule value equals null
        $v = new Validator(['test_field' => null]);
        $v->rule('required', 'test_field');
        $v->rule('subset', 'test_field', ['black', 45]);
        $this->assertFalse($v->validate());
    }

    // ContainsUnique Tests
    public function testContainsUniqueValid()
    {
        // numeric values
        $v = new Validator(['test_field' => [81, 3, 15]]);
        $v->rule('containsUnique', 'test_field');
        $this->assertTrue($v->validate());

        // string values
        $v = new Validator(['test_field' => ['white', 'green', 'blue']]);
        $v->rule('containsUnique', 'test_field');
        $this->assertTrue($v->validate());

        // mixed values
        $v = new Validator(['test_field' => [81, false, 'orange']]);
        $v->rule('containsUnique', 'test_field');
        $this->assertTrue($v->validate());
    }

    public function testContainsUniqueValidAltSyntax()
    {
        $v = new Validator(['colors' => ['purple', 'blue']]);
        $v->rules([
            'containsUnique' => [
                ['colors'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testContainsUniqueInvalid()
    {
        $v = new Validator(['test_field' => [81, false, 'orange', false]]);
        $v->rule('containsUnique', 'test_field');
        $this->assertFalse($v->validate());
    }

    public function testContainsUniqueInvalidAltSyntax()
    {
        $v = new Validator(['colors' => ['purple', 'purple']]);
        $v->rules([
            'containsUnique' => [
                ['colors'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testContainsUniqueInvalidValue()
    {
        $v = new Validator(['test_field' => 'lorem ipsum']);
        $v->rule('containsUnique', 'test_field');
        $this->assertFalse($v->validate());
    }

    // ArrayHasKeys Tests
    public function testArrayHasKeysTrueIfAllFieldsExist()
    {
        $v = new Validator([
            'address' => [
                'name' => 'Jane Doe',
                'street' => 'Doe Square',
                'city' => 'Doe D.C.',
            ],
        ]);
        $v->rule('arrayHasKeys', 'address', ['name', 'street', 'city']);
        $this->assertTrue($v->validate());
    }

    public function testArrayHasKeysFalseOnMissingField()
    {
        $v = new Validator([
            'address' => [
                'name' => 'Jane Doe',
                'street' => 'Doe Square',
            ],
        ]);
        $v->rule('arrayHasKeys', 'address', ['name', 'street', 'city']);
        $this->assertFalse($v->validate());
    }

    public function testArrayHasKeysFalseOnNonArray()
    {
        $v = new Validator([
            'address' => ['Jane Doe, Doe Square'],
        ]);
        $v->rule('arrayHasKeys', 'address', ['name', 'street', 'city']);
        $this->assertFalse($v->validate());
    }

    public function testArrayHasKeysFalseOnEmptyRequiredFields()
    {
        $v = new Validator([
            'address' => [
                'lat' => 77.547,
                'lon' => 16.337,
            ],
        ]);
        $v->rule('arrayHasKeys', 'address', []);
        $this->assertFalse($v->validate());
    }

    public function testArrayHasKeysFalseOnUnspecifiedRequiredFields()
    {
        $v = new Validator([
            'address' => [
                'lat' => 77.547,
                'lon' => 16.337,
            ],
        ]);
        $v->rule('arrayHasKeys', 'address');
        $this->assertFalse($v->validate());
    }

    public function testArrayHasKeysTrueIfMissingAndOptional()
    {
        $v = new Validator([]);
        $v->rule('arrayHasKeys', 'address', ['name', 'street', 'city']);
        $v->rule('optional', 'address');
        $this->assertTrue($v->validate());
    }

    // Optional Array Parts Tests
    /**
     * @see https://github.com/vlucas/valitron/issues/262
     */
    public function testOptionalArrayPartsAreIgnored()
    {
        $v = new Validator(
            [
            'data' => [
                ['foo' => '2018-01-01'],
                ['bar' => 1],
            ],
        ],
        );
        $v->rule('date', 'data.*.foo');
        $this->assertTrue($v->validate());
    }

    /**
     * @see https://github.com/vlucas/valitron/issues/262
     */
    public function testRequiredArrayPartsAreNotIgnored()
    {
        $v = new Validator(
            [
            'data' => [
                ['foo' => '2018-01-01'],
                ['bar' => 1],
            ],
        ],
        );
        $v->rule('required', 'data.*.foo');
        $v->rule('date', 'data.*.foo');
        $this->assertFalse($v->validate());
    }

    // Edge Cases - Type Testing
    public function testContainsUniqueWithVariousTypes(): void
    {
        // Test with strings
        $v1 = new Validator(['items' => ['a', 'b', 'c']]);
        $v1->rule('containsUnique', 'items');
        $this->assertTrue($v1->validate());

        // Test with integers
        $v2 = new Validator(['numbers' => [1, 2, 3, 2]]);
        $v2->rule('containsUnique', 'numbers');
        $this->assertFalse($v2->validate(), 'Duplicate numbers should fail');

        // Test with mixed types (type coercion with SORT_REGULAR)
        $v3 = new Validator(['mixed' => [1, '1', 2, 3]]);
        $v3->rule('containsUnique', 'mixed');
        // SORT_REGULAR treats 1 == '1', so this should fail
        $this->assertFalse($v3->validate(), 'Type-coerced duplicates should fail');
    }
}
