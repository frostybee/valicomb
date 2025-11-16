<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use Frostybee\Valicomb\Validator;
use InvalidArgumentException;

use function realpath;

class LangTest extends BaseTestCase
{
    protected function getLangDir(): string
    {
        return __DIR__ . '/../../src/lang';
    }

    /**
     * Lang defined statically should not be override by constructor default
     */
    public function testLangDefinedStatically(): void
    {
        $lang = 'ar';
        Validator::lang($lang);
        $this->assertEquals($lang, Validator::lang());
        Validator::lang('en');
    }

    /**
     * LangDir defined statically should not be override by constructor default
     */
    public function testLangDirDefinedStatically(): void
    {
        $langDir = $this->getLangDir();
        Validator::langDir($langDir);
        new Validator([]);
        $this->assertEquals($langDir, Validator::langDir());
    }

    public function testDefaultLangShouldBeEn(): void
    {
        new Validator([]);
        $this->assertEquals('en', Validator::lang());
    }

    public function testDefaultLangDirShouldBePackageLangDir(): void
    {
        new Validator([]);
        $this->assertEquals(realpath($this->getLangDir()), realpath(Validator::langDir()));
    }

    public function testLangException(): void
    {
        try {
            new Validator([], [], 'en', '/this/dir/does/not/exists');
        } catch (InvalidArgumentException $exception) {
            $this->assertInstanceOf("InvalidArgumentException", $exception);
            $this->assertEquals("Fail to load language file '/this/dir/does/not/exists/en.php'", $exception->getMessage());
        }
    }

    public function testLoadingNorwegianLoadsNNVariant(): void
    {
        $validator = new Validator([], [], 'no', $this->getLangDir());
        $validator->rule('required', 'test');
        $validator->validate();
        $errors = $validator->errors('test');
        $this->assertEquals('Test er nødvendig', $errors[0]);
    }
}
