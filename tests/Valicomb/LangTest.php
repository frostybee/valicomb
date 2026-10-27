<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests;

use function array_diff_key;
use function array_keys;
use function basename;

use Frostybee\Valicomb\Validator;

use function glob;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

use function preg_match_all;
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

    public function testMissingTranslationFallsBackToEnglish(): void
    {
        // Load a full language first so its messages could leak into the next one
        new Validator([], [], 'de', $this->getLangDir());

        $v = new Validator(['name' => '', 'id' => 'x'], [], 'fr', __DIR__ . '/../lang');
        $v->rule('required', 'name');
        $v->rule('uuid', 'id');
        $v->validate();

        $this->assertEquals('Name est obligatoire', $v->errors('name')[0]);
        $this->assertEquals('Id must be a valid UUID', $v->errors('id')[0]);
    }

    #[DataProvider('bundledLanguages')]
    public function testBundledLanguageLoads(string $lang): void
    {
        $v = new Validator(['test' => ''], [], $lang, $this->getLangDir());
        $v->rule('required', 'test');

        $this->assertFalse($v->validate());
    }

    #[DataProvider('bundledLanguages')]
    public function testBundledLanguageTranslatesEveryRule(string $lang): void
    {
        $english = include $this->getLangDir() . '/en.php';
        $messages = include $this->getLangDir() . "/$lang.php";

        $this->assertSame([], array_keys(array_diff_key($english, $messages)), "Missing messages in $lang.php");

        foreach ($english as $rule => $message) {
            $this->assertSame(
                $this->placeholders($message),
                $this->placeholders($messages[$rule]),
                "Placeholders differ for '$rule' in $lang.php",
            );
        }
    }

    public static function bundledLanguages(): array
    {
        $languages = [];
        foreach (glob(__DIR__ . '/../../src/lang/*.php') ?: [] as $file) {
            $languages[basename($file, '.php')] = [basename($file, '.php')];
        }

        return $languages;
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $message): array
    {
        preg_match_all('/%[sd]|\{field\}|\{value\}/', $message, $matches);

        return $matches[0];
    }
}
