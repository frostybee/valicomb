<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use Frostybee\Valicomb\Validator;

use function ucfirst;

class StaticVsInstanceTest extends BaseTestCase
{
    public function testInstanceOverrideStaticLang(): void
    {
        Validator::lang('ar');
        new Validator([], [], 'en');
        $this->assertEquals(
            'ar',
            Validator::lang(),
            'instance defined lang should not replace static global lang',
        );
        Validator::lang('en');
    }

    /**
     * Fix bug where rules messages added with Validator::addRule were replaced after creating validator instance
     */
    public function testRuleMessagesReplacedAfterConstructor(): void
    {
        $customMessage = 'custom message';
        $ruleName = 'customRule';
        $fieldName = 'fieldName';
        Validator::addRule($ruleName, function (): void {}, $customMessage);
        $v = new Validator([$fieldName => $fieldName]);
        $v->rule($ruleName, $fieldName);
        $v->validate();
        $messages = $v->errors();
        $this->assertArrayHasKey($fieldName, $messages);
        $this->assertEquals(ucfirst("$fieldName $customMessage"), $messages[$fieldName][0]);
    }
}
