<?php

declare(strict_types=1);

namespace Valicomb\Tests;

use function in_array;
use function is_numeric;
use function preg_match;
use function strrchr;
use function strtolower;
use function substr;

use Valicomb\Validator;

function validateStrongPassword($field, $value)
{
    // Password must contain at least one uppercase, one lowercase, and one number
    return preg_match('/[A-Z]/', $value) && preg_match('/[a-z]/', $value) && preg_match('/[0-9]/', $value);
}

class ValidateAddInstanceRuleTest extends BaseTestCase
{
    /**
     * @param Validator $v
     */
    protected function assertValid($v)
    {
        $msg = "\tErrors:\n";
        $status = $v->validate();
        foreach ($v->errors() as $label => $messages) {
            foreach ($messages as $theMessage) {
                $msg .= "\n\t{$label}: {$theMessage}";
            }
        }

        $this->assertTrue($v->validate(), $msg);
    }

    public function testAddInstanceRule()
    {
        $v = new Validator([
            "username" => "john_doe",
            "email" => "john@example.com",
        ]);

        // Instance-specific rule: username cannot be 'admin'
        $v->addInstanceRule("notAdmin", function ($field, $value) {
            return strtolower($value) !== "admin";
        });

        // Global static rule: email domain must be allowed
        Validator::addRule("allowedDomain", function ($field, $value) {
            $allowedDomains = ['example.com', 'test.com'];
            $domain = substr(strrchr($value, "@"), 1);
            return in_array($domain, $allowedDomains, true);
        });

        $v->rule("required", ["username", "email"]);
        $v->rule("allowedDomain", "email");
        $v->rule("notAdmin", "username");

        $this->assertValid($v);
    }

    public function testAddInstanceRuleFail()
    {
        $v = new Validator(["username" => "admin"]);
        $v->addInstanceRule("notReserved", function ($field, $value) {
            $reserved = ['admin', 'root', 'administrator'];
            return !in_array(strtolower($value), $reserved, true);
        });
        $v->rule("notReserved", "username");
        $this->assertFalse($v->validate());
    }

    public function testAddAddRuleWithCallback()
    {
        $v = new Validator(["age" => "25"]);
        $v->rule(function ($field, $value) {
            // Age must be between 18 and 120
            return is_numeric($value) && $value >= 18 && $value <= 120;
        }, "age");

        $this->assertValid($v);
    }

    public function testAddAddRuleWithCallbackFail()
    {
        $v = new Validator(["age" => "15"]);
        $v->rule(function ($field, $value) {
            // Age must be 18 or older
            return is_numeric($value) && $value >= 18;
        }, "age");

        $this->assertFalse($v->validate());
    }

    public function testAddAddRuleWithCallbackFailMessage()
    {
        $v = new Validator(["coupon_code" => "INVALID"]);
        $v->rule(function ($field, $value) {
            $validCoupons = ['SAVE10', 'DISCOUNT20', 'FREESHIP'];
            return in_array($value, $validCoupons, true);
        }, "coupon_code", "is not a valid coupon code");

        $this->assertFalse($v->validate());
        $errors = $v->errors();
        $this->assertArrayHasKey("coupon_code", $errors);
        $this->assertCount(1, $errors["coupon_code"]);
        $this->assertEquals("Coupon Code is not a valid coupon code", $errors["coupon_code"][0]);
    }

    public function testAddRuleWithNamedCallbackOk()
    {
        $v = new Validator(["password" => "weakpass"]);
        $v->rule('Valicomb\Tests\validateStrongPassword', "password");
        $this->assertFalse($v->validate());
    }

    public function testAddRuleWithNamedCallbackErr()
    {
        $v = new Validator(["password" => "StrongPass123"]);
        $v->rule('Valicomb\Tests\validateStrongPassword', "password");
        $this->assertTrue($v->validate());
    }

    public function testUniqueRuleName()
    {
        $v = new Validator([]);
        $args = ["username", "email"];
        $this->assertEquals("username_email_rule", $v->getUniqueRuleName($args));
        $this->assertEquals("username_rule", $v->getUniqueRuleName("username"));

        // When a rule name already exists, it should append a unique number
        $v->addInstanceRule("username_rule", function () {
        });
        $uniqueName = $v->getUniqueRuleName("username");
        $this->assertMatchesRegularExpression("/^username_rule_[0-9]{1,5}$/", $uniqueName);
    }
}
