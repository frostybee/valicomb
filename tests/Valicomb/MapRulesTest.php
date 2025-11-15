<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use function count;

use Frostybee\Valicomb\Validator;

class MapRulesTest extends BaseTestCase
{
    public function testMapSingleFieldRules()
    {
        $rules = [
            'required',
            ['lengthMin', 4],
        ];

        $v = new Validator([]);
        $v->mapOneFieldToRules('username', $rules);
        $this->assertFalse($v->validate());
        $this->assertEquals(2, count($v->errors('username')));

        $v2 = new Validator(['username' => 'john']);
        $v2->mapOneFieldToRules('username', $rules);
        $this->assertTrue($v2->validate());
    }

    public function testSingleFieldDot()
    {
        $v = new Validator([
            'settings' => [
                ['threshold' => 50],
                ['threshold' => 90],
            ],
        ]);
        $v->mapOneFieldToRules('settings.*.threshold', [
            ['max', 50],
        ]);

        $this->assertFalse($v->validate());
    }

    public function testMapMultipleFieldsRules()
    {
        $rules = [
            'username' => [
                'required',
                ['lengthMin', 4],
            ],
            'password' => [
                'required',
                ['lengthMin', 8],
            ],
        ];

        $v = new Validator([
            'username' => 'john',
        ]);
        $v->mapManyFieldsToRules($rules);

        $this->assertFalse($v->validate());
        $this->assertFalse($v->errors('username'));
        $this->assertEquals(2, count($v->errors('password')));
    }

    public function testCustomMessageSingleField()
    {
        $rules = [
            ['lengthMin', 14, 'message' => 'Credit card number must be at least 14 digits'],
        ];

        $v = new Validator([
            'card_number' => '12345',
        ]);
        $v->mapOneFieldToRules('card_number', $rules);
        $this->assertFalse($v->validate());
        $errors = $v->errors('card_number');
        $this->assertEquals('Credit card number must be at least 14 digits', $errors[0]);
    }

    public function testCustomMessageMultipleFields()
    {
        $rules = [
            'email' => [
                ['lengthMin', 14, 'message' => 'Email must be at least 14 characters'],
            ],
            'phone' => [
                ['lengthMin', 10, 'message' => 'Phone number must be at least 10 digits'],
            ],
        ];

        $v = new Validator([
            'email' => 'test@ex.co',
            'phone' => '555',
        ]);

        $v->mapManyFieldsToRules($rules);
        $this->assertFalse($v->validate());

        $emailErrors = $v->errors('email');
        $this->assertEquals('Email must be at least 14 characters', $emailErrors[0]);

        $phoneErrors = $v->errors('phone');
        $this->assertEquals('Phone number must be at least 10 digits', $phoneErrors[0]);
    }
}
