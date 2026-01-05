<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use Frostybee\Valicomb\Validator;

use function in_array;
use function is_numeric;
use function preg_match;
use function strlen;
use function strrchr;
use function strtolower;
use function substr;

function validateStrongPassword($field, $value): bool
{
    // Password must contain at least one uppercase, one lowercase, and one number
    return preg_match('/[A-Z]/', (string) $value) && preg_match('/[a-z]/', (string) $value) && preg_match('/\d/', (string) $value);
}

class ValidateAddInstanceRuleTest extends BaseTestCase
{
    /**
     * @param Validator $v
     */
    protected function assertValid($v)
    {
        $msg = "\tErrors:\n";
        $v->validate();
        foreach ($v->errors() as $label => $messages) {
            foreach ($messages as $theMessage) {
                $msg .= "\n\t{$label}: {$theMessage}";
            }
        }

        $this->assertTrue($v->validate(), $msg);
    }

    public function testAddInstanceRule(): void
    {
        $v = new Validator([
            "username" => "john_doe",
            "email" => "john@example.com",
        ]);

        // Instance-specific rule: username cannot be 'admin'
        $v->addInstanceRule("notAdmin", fn ($field, $value): bool => strtolower((string) $value) !== "admin");

        // Global static rule: email domain must be allowed
        Validator::addRule("allowedDomain", function ($field, $value): bool {
            $allowedDomains = ['example.com', 'test.com'];
            $domain = substr(strrchr($value, "@"), 1);
            return in_array($domain, $allowedDomains, true);
        });

        $v->rule("required", ["username", "email"]);
        $v->rule("allowedDomain", "email");
        $v->rule("notAdmin", "username");

        $this->assertValid($v);
    }

    public function testAddInstanceRuleFail(): void
    {
        $v = new Validator(["username" => "admin"]);
        $v->addInstanceRule("notReserved", function ($field, $value): bool {
            $reserved = ['admin', 'root', 'administrator'];
            return !in_array(strtolower($value), $reserved, true);
        });
        $v->rule("notReserved", "username");
        $this->assertFalse($v->validate());
    }

    public function testAddAddRuleWithCallback(): void
    {
        $v = new Validator(["age" => "25"]);
        $v->rule(fn ($field, $value): bool =>
            // Age must be between 18 and 120
            is_numeric($value) && $value >= 18 && $value <= 120, "age");

        $this->assertValid($v);
    }

    public function testAddAddRuleWithCallbackFail(): void
    {
        $v = new Validator(["age" => "15"]);
        $v->rule(fn ($field, $value): bool =>
            // Age must be 18 or older
            is_numeric($value) && $value >= 18, "age");

        $this->assertFalse($v->validate());
    }

    public function testAddAddRuleWithCallbackFailMessage(): void
    {
        $v = new Validator(["coupon_code" => "INVALID"]);
        $v->rule(function ($field, $value): bool {
            $validCoupons = ['SAVE10', 'DISCOUNT20', 'FREESHIP'];
            return in_array($value, $validCoupons, true);
        }, "coupon_code", "is not a valid coupon code");

        $this->assertFalse($v->validate());
        $errors = $v->errors();
        $this->assertArrayHasKey("coupon_code", $errors);
        $this->assertCount(1, $errors["coupon_code"]);
        $this->assertEquals("Coupon Code is not a valid coupon code", $errors["coupon_code"][0]);
    }

    public function testAddRuleWithNamedCallbackOk(): void
    {
        $v = new Validator(["password" => "weakpass"]);
        $v->rule('Frostybee\Valicomb\Tests\validateStrongPassword', "password");
        $this->assertFalse($v->validate());
    }

    public function testAddRuleWithNamedCallbackErr(): void
    {
        $v = new Validator(["password" => "StrongPass123"]);
        $v->rule('Frostybee\Valicomb\Tests\validateStrongPassword', "password");
        $this->assertTrue($v->validate());
    }

    public function testUniqueRuleName(): void
    {
        $v = new Validator([]);
        $args = ["username", "email"];
        $this->assertEquals("username_email_rule", $v->getUniqueRuleName($args));
        $this->assertEquals("username_rule", $v->getUniqueRuleName("username"));

        // When a rule name already exists, it should append a unique number
        $v->addInstanceRule("username_rule", function (): void {
        });
        $uniqueName = $v->getUniqueRuleName("username");
        $this->assertMatchesRegularExpression("/^username_rule_\\d{1,5}\$/", $uniqueName);
    }

    // ===========================================
    // Custom Messages from Custom Rules (#379)
    // ===========================================

    /**
     * Test custom rule returning array with custom message
     */
    public function testCustomRuleReturnsArrayWithMessage(): void
    {
        $v = new Validator(['password' => 'weak']);
        $v->addInstanceRule('strongPassword', function ($field, $value): array {
            if (strlen($value) < 8) {
                return [false, 'Password must be at least 8 characters'];
            }
            if (!preg_match('/[A-Z]/', $value)) {
                return [false, 'Password must contain an uppercase letter'];
            }
            if (!preg_match('/[0-9]/', $value)) {
                return [false, 'Password must contain a number'];
            }
            return [true];
        });
        $v->rule('strongPassword', 'password');

        $this->assertFalse($v->validate());
        $errors = $v->errors('password');
        $this->assertSame('Password must be at least 8 characters', $errors[0]);
    }

    /**
     * Test custom rule returns different messages based on failure reason
     */
    public function testCustomRuleReturnsDifferentMessagesPerCondition(): void
    {
        $v = new Validator(['password' => 'longpasswordwithoutnumbers']);
        $v->addInstanceRule('strongPassword', function ($field, $value): array {
            if (strlen($value) < 8) {
                return [false, 'Password must be at least 8 characters'];
            }
            if (!preg_match('/[A-Z]/', $value)) {
                return [false, 'Password must contain an uppercase letter'];
            }
            if (!preg_match('/[0-9]/', $value)) {
                return [false, 'Password must contain a number'];
            }
            return [true];
        });
        $v->rule('strongPassword', 'password');

        $this->assertFalse($v->validate());
        $errors = $v->errors('password');
        $this->assertSame('Password must contain an uppercase letter', $errors[0]);
    }

    /**
     * Test custom rule returning array with true passes validation
     */
    public function testCustomRuleReturnsArrayTruePasses(): void
    {
        $v = new Validator(['password' => 'StrongPass123']);
        $v->addInstanceRule('strongPassword', function ($field, $value): array {
            if (strlen($value) < 8) {
                return [false, 'Password must be at least 8 characters'];
            }
            return [true];
        });
        $v->rule('strongPassword', 'password');

        $this->assertTrue($v->validate());
    }

    /**
     * Test custom rule returning boolean still works (backward compatibility)
     */
    public function testCustomRuleReturnsBoolBackwardCompatible(): void
    {
        $v = new Validator(['value' => 'test']);
        $v->addInstanceRule('alwaysFail', function ($field, $value): bool {
            return false;
        }, 'Default error message');
        $v->rule('alwaysFail', 'value');

        $this->assertFalse($v->validate());
        $errors = $v->errors('value');
        $this->assertSame('Value Default error message', $errors[0]);
    }

    /**
     * Test custom rule returning array without message uses default
     */
    public function testCustomRuleReturnsArrayWithoutMessageUsesDefault(): void
    {
        $v = new Validator(['value' => 'test']);
        $v->addInstanceRule('checkValue', function ($field, $value): array {
            return [false]; // No message provided
        }, 'Default message for this rule');
        $v->rule('checkValue', 'value');

        $this->assertFalse($v->validate());
        $errors = $v->errors('value');
        $this->assertSame('Value Default message for this rule', $errors[0]);
    }

    /**
     * Test inline callable rule with array return
     */
    public function testInlineCallableWithArrayReturn(): void
    {
        $v = new Validator(['age' => 15]);
        $v->rule(function ($field, $value): array {
            if (!is_numeric($value)) {
                return [false, 'Age must be a number'];
            }
            if ($value < 18) {
                return [false, 'You must be 18 or older'];
            }
            return [true];
        }, 'age');

        $this->assertFalse($v->validate());
        $errors = $v->errors('age');
        $this->assertSame('You must be 18 or older', $errors[0]);
    }

    /**
     * Test global rule with array return
     */
    public function testGlobalRuleWithArrayReturn(): void
    {
        Validator::addRule('customValidation', function ($field, $value): array {
            if ($value === 'forbidden') {
                return [false, 'This value is forbidden'];
            }
            return [true];
        });

        $v = new Validator(['input' => 'forbidden']);
        $v->rule('customValidation', 'input');

        $this->assertFalse($v->validate());
        $errors = $v->errors('input');
        $this->assertSame('This value is forbidden', $errors[0]);
    }

    /**
     * Test custom message with {value} placeholder
     */
    public function testCustomMessageWithValuePlaceholder(): void
    {
        $v = new Validator(['code' => 'INVALID']);
        $v->addInstanceRule('validCode', function ($field, $value): array {
            $validCodes = ['ABC123', 'XYZ789'];
            if (!in_array($value, $validCodes, true)) {
                return [false, '{field} "{value}" is not a valid code'];
            }
            return [true];
        });
        $v->rule('validCode', 'code');

        $this->assertFalse($v->validate());
        $errors = $v->errors('code');
        $this->assertSame('Code "INVALID" is not a valid code', $errors[0]);
    }
}
