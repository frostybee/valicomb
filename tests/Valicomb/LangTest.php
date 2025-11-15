<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use Frostybee\Valicomb\Validator;
use InvalidArgumentException;

use function realpath;

class LangTest extends BaseTestCase
{
    protected function getLangDir()
    {
        return __DIR__ . '/../../lang';
    }

    /**
     * Lang defined statically should not be override by constructor default
     */
    public function testLangDefinedStatically()
    {
        $lang = 'ar';
        Validator::lang($lang);
        $this->assertEquals($lang, Validator::lang());
        Validator::lang('en');
    }

    /**
     * LangDir defined statically should not be override by constructor default
     */
    public function testLangDirDefinedStatically()
    {
        $langDir = $this->getLangDir();
        Validator::langDir($langDir);
        $validator = new Validator([]);
        $this->assertEquals($langDir, Validator::langDir());
    }

    public function testDefaultLangShouldBeEn()
    {
        $validator = new Validator([]);
        $this->assertEquals('en', Validator::lang());
    }

    public function testDefaultLangDirShouldBePackageLangDir()
    {
        $validator = new Validator([]);
        $this->assertEquals(realpath($this->getLangDir()), realpath(Validator::langDir()));
    }

    public function testLangException()
    {
        try {
            new Validator([], [], 'en', '/this/dir/does/not/exists');
        } catch (InvalidArgumentException $exception) {
            $this->assertInstanceOf("InvalidArgumentException", $exception);
            $this->assertEquals("Fail to load language file '/this/dir/does/not/exists/en.php'", $exception->getMessage());
        }
    }

    public function testLoadingNorwegianLoadsNNVariant()
    {
        $validator = new Validator([], [], 'no', $this->getLangDir());
        $validator->rule('required', 'test');
        $validator->validate();
        $errors = $validator->errors('test');
        $this->assertEquals('Test er nødvendig', $errors[0]);
    }
}
