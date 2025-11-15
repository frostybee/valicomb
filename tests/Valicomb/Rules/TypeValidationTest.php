<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use function compact;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;
use InvalidArgumentException;
use stdClass;

class TypeValidationTest extends BaseTestCase
{
    // Boolean Tests
    public function testBooleanValid()
    {
        $v = new Validator(['test' => true]);
        $v->rule('boolean', 'test');
        $this->assertTrue($v->validate());
    }

    public function testBooleanValidAltSyntax()
    {
        $v = new Validator(['remember_me' => true]);
        $v->rules([
            'boolean' => [
                ['remember_me'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testBooleanInvalid()
    {
        $v = new Validator(['test' => 'true']);
        $v->rule('boolean', 'test');
        $this->assertFalse($v->validate());
    }

    public function testBooleanInvalidAltSyntax()
    {
        $v = new Validator(['remember_me' => 'lobster']);
        $v->rules([
            'boolean' => [
                ['remember_me'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // IP Tests
    public function testIpValid()
    {
        $v = new Validator(['ip' => '127.0.0.1']);
        $v->rule('ip', 'ip');
        $this->assertTrue($v->validate());
    }

    public function testIpValidAltSyntax()
    {
        $v = new Validator(['user_ip' => '127.0.0.1']);
        $v->rules([
            'ip' => [
                ['user_ip'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testIpInvalid()
    {
        $v = new Validator(['ip' => 'buy viagra now!']);
        $v->rule('ip', 'ip');
        $this->assertFalse($v->validate());
    }

    public function testIpInvalidAltSyntax()
    {
        $v = new Validator(['user_ip' => '127.0.0.1.345']);
        $v->rules([
            'ip' => [
                ['user_ip'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // IPv4 Tests
    public function testIpv4Valid()
    {
        $v = new Validator(['ip' => '127.0.0.1']);
        $v->rule('ipv4', 'ip');
        $this->assertTrue($v->validate());
    }

    public function testIpv4ValidAltSyntax()
    {
        $v = new Validator(['user_ip' => '127.0.0.1']);
        $v->rules([
            'ipv4' => [
                ['user_ip'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testIpv4Invalid()
    {
        $v = new Validator(['ip' => 'FE80::0202:B3FF:FE1E:8329']);
        $v->rule('ipv4', 'ip');
        $this->assertFalse($v->validate());
    }

    public function testIpv4InvalidAltSyntax()
    {
        $v = new Validator(['user_ip' => '127.0.0.1.234']);
        $v->rules([
            'ipv4' => [
                ['user_ip'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // IPv6 Tests
    public function testIpv6Valid()
    {
        $v = new Validator(['ip' => 'FE80::0202:B3FF:FE1E:8329']);
        $v->rule('ipv6', 'ip');
        $this->assertTrue($v->validate());
    }

    public function testIpv6ValidAltSyntax()
    {
        $v = new Validator(['user_ipv6' => '0:0:0:0:0:0:0:1']);
        $v->rules([
            'ipv6' => [
                ['user_ipv6'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testIpv6Invalid()
    {
        $v = new Validator(['ip' => '127.0.0.1']);
        $v->rule('ipv6', 'ip');
        $this->assertFalse($v->validate());
    }

    public function testIpv6InvalidAltSyntax()
    {
        $v = new Validator(['user_ipv6' => '0:0:0:0:0:0:0:1:3:4:5']);
        $v->rules([
            'ipv6' => [
                ['user_ipv6'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Credit Card Tests
    public function testCreditCardValid()
    {
        $visa = [4539511619543489, 4532949059629052, 4024007171194938, 4929646403373269, 4539135861690622];
        $mastercard = [5162057048081965, 5382687859049349, 5484388880142230, 5464941521226434, 5473481232685965, 2223000048400011, 2223520043560014];
        $amex = [371442067262027, 340743030537918, 345509167493596, 343665795576848, 346087552944316];
        $dinersclub = [30363194756249, 30160097740704, 38186521192206, 38977384214552, 38563220301454];
        $discover = [6011712400392605, 6011536340491809, 6011785775263015, 6011984124619056, 6011320958064251];

        foreach (compact('visa', 'mastercard', 'amex', 'dinersclub', 'discover') as $type => $numbers) {
            foreach ($numbers as $number) {
                $v = new Validator(['test' => $number]);
                $v->rule('creditCard', 'test');
                $this->assertTrue($v->validate());
                $v->rule('creditCard', 'test', [$type, 'mastercard', 'visa']);
                $this->assertTrue($v->validate());
                $v->rule('creditCard', 'test', $type);
                $this->assertTrue($v->validate());
                $v->rule('creditCard', 'test', $type, [$type, 'mastercard', 'visa']);
                $this->assertTrue($v->validate());
                unset($v);
            }
        }
    }

    public function testCreditCardInvalid()
    {
        $visa = [3539511619543489, 3532949059629052, 3024007171194938, 3929646403373269, 3539135861690622];
        $mastercard = [4162057048081965, 4382687859049349, 4484388880142230, 4464941521226434, 4473481232685965];
        $amex = [271442067262027, 240743030537918, 245509167493596, 243665795576848, 246087552944316];
        $dinersclub = [20363194756249, 20160097740704, 28186521192206, 28977384214552, 28563220301454];
        $discover = [5011712400392605, 5011536340491809, 5011785775263015, 5011984124619056, 5011320958064251];

        foreach (compact('visa', 'mastercard', 'amex', 'dinersclub', 'discover') as $type => $numbers) {
            foreach ($numbers as $number) {
                $v = new Validator(['test' => $number]);
                $v->rule('creditCard', 'test');
                $this->assertFalse($v->validate());
                $v->rule('creditCard', 'test', [$type, 'mastercard', 'visa']);
                $this->assertFalse($v->validate());
                $v->rule('creditCard', 'test', $type);
                $this->assertFalse($v->validate());
                $v->rule('creditCard', 'test', $type, [$type, 'mastercard', 'visa']);
                $this->assertFalse($v->validate());
                $v->rule('creditCard', 'test', 'invalidCardName');
                $this->assertFalse($v->validate());
                $v->rule('creditCard', 'test', 'invalidCardName', ['invalidCardName', 'mastercard', 'visa']);
                $this->assertFalse($v->validate());
                unset($v);
            }
        }
    }

    // InstanceOf Tests
    public function testInstanceOfValidWithString()
    {
        $v = new Validator(['attributeName' => new stdClass()]);
        $v->rule('instanceOf', 'attributeName', '\stdClass');
        $this->assertTrue($v->validate());
    }

    public function testInstanceOfValidAltSyntax()
    {
        $v = new Validator(['date' => new DateTime()]);
        $existingDateObject = new DateTime();
        $v->rules([
            'instanceOf' => [
                ['date', 'DateTime'],
                ['date', $existingDateObject],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testInstanceOfInvalidWithInstance()
    {
        $v = new Validator(['attributeName' => new stdClass()]);
        $v->rule('instanceOf', 'attributeName', new Validator([]));
        $this->assertFalse($v->validate());
    }

    public function testInstanceOfInvalidAltSyntax()
    {
        $v = new Validator(['date' => new DateTime()]);
        $v->rules([
            'instanceOf' => [
                ['date', '\stdClass'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testInstanceOfValidWithInstance()
    {
        $v = new Validator(['attributeName' => new stdClass()]);
        $v->rule('instanceOf', 'attributeName', new stdClass());
        $this->assertTrue($v->validate());
    }

    public function testInstanceOfErrorMessageShowsInstanceName()
    {
        $v = new Validator(['attributeName' => new Validator([])]);
        $v->rule('instanceOf', 'attributeName', new stdClass());
        $v->validate();
        $expected_error = [
            "attributeName" => [
                "AttributeName must be an instance of '\stdClass'",
            ],
        ];
        $this->assertEquals($expected_error, $v->errors());
    }

    public function testInstanceOfInvalidWithString()
    {
        $v = new Validator(['attributeName' => new stdClass()]);
        $v->rule('instanceOf', 'attributeName', 'SomeOtherClass');
        $this->assertFalse($v->validate());
    }

    public function testInstanceOfWithAlternativeSyntaxValid()
    {
        $v = new Validator(['attributeName' => new stdClass()]);
        $v->rules([
            'instanceOf' => [
                ['attributeName', '\stdClass'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testInstanceOfWithAlternativeSyntaxInvalid()
    {
        $v = new Validator(['attributeName' => new stdClass()]);
        $v->rules([
            'instanceOf' => [
                ['attributeName', 'SomeOtherClassInAlternativeSyntaxInvalid'],
            ],
        ]);
        $v->validate();
        $this->assertFalse($v->validate());
    }

    // Edge Cases
    public function testInstanceOfSimplifiedLogic(): void
    {
        $dateTime = new DateTime();
        $dateTimeImmutable = new DateTimeImmutable();

        // Test exact class match
        $v1 = new Validator(['obj' => $dateTime]);
        $v1->rule('instanceOf', 'obj', DateTime::class);
        $this->assertTrue($v1->validate());

        // Test inheritance/interface (DateTimeInterface)
        $v2 = new Validator(['obj' => $dateTime]);
        $v2->rule('instanceOf', 'obj', DateTimeInterface::class);
        $this->assertTrue($v2->validate());

        // Test different class
        $v3 = new Validator(['obj' => $dateTime]);
        $v3->rule('instanceOf', 'obj', DateTimeImmutable::class);
        $this->assertFalse($v3->validate());
    }

    // Parameter Validation Tests
    public function testInstanceOfRequiresParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class name or object required for instanceOf validation');

        $v = new Validator(['obj' => new DateTime()]);
        $v->rule('instanceOf', 'obj'); // Missing parameter
        $v->validate();
    }

    public function testInstanceOfRequiresStringClassName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected class name must be a string');

        $v = new Validator(['obj' => new DateTime()]);
        $v->rule('instanceOf', 'obj', 123); // Integer instead of class name
        $v->validate();
    }
}
