<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;

class PhoneValidationTest extends BaseTestCase
{
    // Basic Phone Validation Tests (General)
    public function testPhoneValid(): void
    {
        $v = new Validator(['phone' => '+1234567890']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneValidWithFormatting(): void
    {
        $v = new Validator(['phone' => '+1 (234) 567-8900']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneValidWithSpaces(): void
    {
        $v = new Validator(['phone' => '+44 20 1234 5678']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneValidWithDashes(): void
    {
        $v = new Validator(['phone' => '123-456-7890']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneValidWithDots(): void
    {
        $v = new Validator(['phone' => '123.456.7890']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneValidWithParentheses(): void
    {
        $v = new Validator(['phone' => '(123) 456-7890']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneValidMinimumLength(): void
    {
        // 7 digits is minimum
        $v = new Validator(['phone' => '1234567']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneValidMaximumLength(): void
    {
        // 15 digits is maximum (international standard)
        $v = new Validator(['phone' => '123456789012345']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneInvalidTooShort(): void
    {
        // Less than 7 digits
        $v = new Validator(['phone' => '123456']);
        $v->rule('phone', 'phone');
        $this->assertFalse($v->validate());
    }

    public function testPhoneInvalidTooLong(): void
    {
        // More than 15 digits
        $v = new Validator(['phone' => '1234567890123456']);
        $v->rule('phone', 'phone');
        $this->assertFalse($v->validate());
    }

    public function testPhoneInvalidWithLetters(): void
    {
        $v = new Validator(['phone' => '123-ABC-7890']);
        $v->rule('phone', 'phone');
        $this->assertFalse($v->validate());
    }

    public function testPhoneInvalidWithInvalidCharacters(): void
    {
        $v = new Validator(['phone' => '123#456@7890']);
        $v->rule('phone', 'phone');
        $this->assertFalse($v->validate());
    }

    public function testPhoneInvalidWithSpacesOnly(): void
    {
        $v = new Validator(['phone' => '   ']);
        $v->rule('phone', 'phone');
        $this->assertFalse($v->validate());
    }

    public function testPhoneInvalidNonString(): void
    {
        $v = new Validator(['phone' => 1234567890]);
        $v->rule('phone', 'phone');
        $this->assertFalse($v->validate());
    }

    // US/Canada Phone Validation Tests
    public function testPhoneUS_Valid10Digits(): void
    {
        $v = new Validator(['phone' => '2125551234']);
        $v->rule('phone', 'phone', 'US');
        $this->assertTrue($v->validate());
    }

    public function testPhoneUS_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+1 212 555 1234']);
        $v->rule('phone', 'phone', 'US');
        $this->assertTrue($v->validate());
    }

    public function testPhoneUS_ValidFormatted(): void
    {
        $v = new Validator(['phone' => '(212) 555-1234']);
        $v->rule('phone', 'phone', 'US');
        $this->assertTrue($v->validate());
    }

    public function testPhoneUS_Invalid9Digits(): void
    {
        $v = new Validator(['phone' => '212555123']);
        $v->rule('phone', 'phone', 'US');
        $this->assertFalse($v->validate());
    }

    public function testPhoneCA_Valid(): void
    {
        $v = new Validator(['phone' => '+1 416 555 1234']);
        $v->rule('phone', 'phone', 'CA');
        $this->assertTrue($v->validate());
    }

    // UK Phone Validation Tests
    public function testPhoneUK_Valid10Digits(): void
    {
        $v = new Validator(['phone' => '2012345678']);
        $v->rule('phone', 'phone', 'UK');
        $this->assertTrue($v->validate());
    }

    public function testPhoneUK_Valid11Digits(): void
    {
        $v = new Validator(['phone' => '20123456789']);
        $v->rule('phone', 'phone', 'UK');
        $this->assertTrue($v->validate());
    }

    public function testPhoneUK_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+44 20 1234 5678']);
        $v->rule('phone', 'phone', 'UK');
        $this->assertTrue($v->validate());
    }

    public function testPhoneGB_Valid(): void
    {
        // GB is alias for UK
        $v = new Validator(['phone' => '+44 20 1234 5678']);
        $v->rule('phone', 'phone', 'GB');
        $this->assertTrue($v->validate());
    }

    // Australia Phone Validation Tests
    public function testPhoneAU_Valid9Digits(): void
    {
        $v = new Validator(['phone' => '212345678']);
        $v->rule('phone', 'phone', 'AU');
        $this->assertTrue($v->validate());
    }

    public function testPhoneAU_Valid10Digits(): void
    {
        $v = new Validator(['phone' => '0212345678']);
        $v->rule('phone', 'phone', 'AU');
        $this->assertTrue($v->validate());
    }

    public function testPhoneAU_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+61 2 1234 5678']);
        $v->rule('phone', 'phone', 'AU');
        $this->assertTrue($v->validate());
    }

    // India Phone Validation Tests
    public function testPhoneIN_Valid10Digits(): void
    {
        $v = new Validator(['phone' => '9876543210']);
        $v->rule('phone', 'phone', 'IN');
        $this->assertTrue($v->validate());
    }

    public function testPhoneIN_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+91 98765 43210']);
        $v->rule('phone', 'phone', 'IN');
        $this->assertTrue($v->validate());
    }

    public function testPhoneIN_Invalid9Digits(): void
    {
        $v = new Validator(['phone' => '987654321']);
        $v->rule('phone', 'phone', 'IN');
        $this->assertFalse($v->validate());
    }

    // Germany Phone Validation Tests
    public function testPhoneDE_Valid10Digits(): void
    {
        $v = new Validator(['phone' => '3012345678']);
        $v->rule('phone', 'phone', 'DE');
        $this->assertTrue($v->validate());
    }

    public function testPhoneDE_Valid11Digits(): void
    {
        $v = new Validator(['phone' => '30123456789']);
        $v->rule('phone', 'phone', 'DE');
        $this->assertTrue($v->validate());
    }

    public function testPhoneDE_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+49 30 1234 5678']);
        $v->rule('phone', 'phone', 'DE');
        $this->assertTrue($v->validate());
    }

    // France Phone Validation Tests
    public function testPhoneFR_Valid9Digits(): void
    {
        $v = new Validator(['phone' => '123456789']);
        $v->rule('phone', 'phone', 'FR');
        $this->assertTrue($v->validate());
    }

    public function testPhoneFR_Valid10Digits(): void
    {
        $v = new Validator(['phone' => '0123456789']);
        $v->rule('phone', 'phone', 'FR');
        $this->assertTrue($v->validate());
    }

    public function testPhoneFR_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+33 1 23 45 67 89']);
        $v->rule('phone', 'phone', 'FR');
        $this->assertTrue($v->validate());
    }

    // Spain Phone Validation Tests
    public function testPhoneES_Valid9Digits(): void
    {
        $v = new Validator(['phone' => '612345678']);
        $v->rule('phone', 'phone', 'ES');
        $this->assertTrue($v->validate());
    }

    public function testPhoneES_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+34 612 345 678']);
        $v->rule('phone', 'phone', 'ES');
        $this->assertTrue($v->validate());
    }

    public function testPhoneES_Invalid10Digits(): void
    {
        $v = new Validator(['phone' => '6123456789']);
        $v->rule('phone', 'phone', 'ES');
        $this->assertFalse($v->validate());
    }

    // Brazil Phone Validation Tests
    public function testPhoneBR_Valid10Digits(): void
    {
        $v = new Validator(['phone' => '1123456789']);
        $v->rule('phone', 'phone', 'BR');
        $this->assertTrue($v->validate());
    }

    public function testPhoneBR_Valid11Digits(): void
    {
        $v = new Validator(['phone' => '11234567890']);
        $v->rule('phone', 'phone', 'BR');
        $this->assertTrue($v->validate());
    }

    public function testPhoneBR_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+55 11 2345 6789']);
        $v->rule('phone', 'phone', 'BR');
        $this->assertTrue($v->validate());
    }

    // Mexico Phone Validation Tests
    public function testPhoneMX_Valid10Digits(): void
    {
        $v = new Validator(['phone' => '5512345678']);
        $v->rule('phone', 'phone', 'MX');
        $this->assertTrue($v->validate());
    }

    public function testPhoneMX_ValidWithCountryCode(): void
    {
        $v = new Validator(['phone' => '+52 55 1234 5678']);
        $v->rule('phone', 'phone', 'MX');
        $this->assertTrue($v->validate());
    }

    // Alternative Syntax Tests
    public function testPhoneValidAltSyntax(): void
    {
        $v = new Validator(['mobile' => '+1234567890']);
        $v->rules([
            'phone' => [
                ['mobile'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testPhoneValidWithCountryAltSyntax(): void
    {
        $v = new Validator(['mobile' => '+1 (212) 555-1234']);
        $v->rules([
            'phone' => [
                ['mobile', 'US'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testPhoneInvalidAltSyntax(): void
    {
        $v = new Validator(['mobile' => 'not-a-phone']);
        $v->rules([
            'phone' => [
                ['mobile'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Edge Cases
    public function testPhoneEmptyStringWhenNotRequired(): void
    {
        $v = new Validator(['phone' => '']);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneNullWhenNotRequired(): void
    {
        $v = new Validator(['phone' => null]);
        $v->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    public function testPhoneRequiredWithEmpty(): void
    {
        $v = new Validator(['phone' => '']);
        $v->rule('required', 'phone')
          ->rule('phone', 'phone');
        $this->assertFalse($v->validate());
    }

    public function testPhoneWithInvalidCountryCode(): void
    {
        // Non-string country code parameter
        $v = new Validator(['phone' => '+1234567890']);
        $v->rule('phone', 'phone', 123);
        $this->assertFalse($v->validate());
    }

    public function testPhoneWithUnknownCountryCode(): void
    {
        // Unknown country code falls back to general validation
        $v = new Validator(['phone' => '+1234567890']);
        $v->rule('phone', 'phone', 'ZZ');
        $this->assertTrue($v->validate());
    }

    public function testPhoneCaseInsensitiveCountryCode(): void
    {
        // Country code should work in lowercase
        $v = new Validator(['phone' => '+1 212 555 1234']);
        $v->rule('phone', 'phone', 'us');
        $this->assertTrue($v->validate());
    }

    // Error Messages
    public function testPhoneErrorMessage(): void
    {
        $v = new Validator(['phone' => 'invalid']);
        $v->rule('phone', 'phone');
        $this->assertFalse($v->validate());
        $errors = $v->errors();
        $this->assertArrayHasKey('phone', $errors);
        $this->assertStringContainsString('valid phone number', $errors['phone'][0]);
    }

    public function testPhoneWithLabelErrorMessage(): void
    {
        $v = new Validator(['mobile' => 'invalid']);
        $v->rule('phone', 'mobile')
          ->label('Mobile Number');
        $this->assertFalse($v->validate());
        $errors = $v->errors();
        $this->assertStringContainsString('Mobile Number', $errors['mobile'][0]);
    }

    public function testPhoneWithCustomMessage(): void
    {
        $v = new Validator(['phone' => 'invalid']);
        $v->rule('phone', 'phone')
          ->message('Please enter a valid phone number');
        $this->assertFalse($v->validate());
        $errors = $v->errors();
        $this->assertEquals('Please enter a valid phone number', $errors['phone'][0]);
    }

    // Combined with Other Rules
    public function testPhoneCombinedWithRequired(): void
    {
        $v = new Validator(['phone' => '+1 212 555 1234']);
        $v->rule('required', 'phone')
          ->rule('phone', 'phone', 'US');
        $this->assertTrue($v->validate());
    }

    public function testPhoneCombinedWithOptional(): void
    {
        $v = new Validator(['phone' => '']);
        $v->rule('optional', 'phone')
          ->rule('phone', 'phone');
        $this->assertTrue($v->validate());
    }

    // Real World Examples
    public function testPhoneRealWorldUS_Examples(): void
    {
        $validNumbers = [
            '(555) 123-4567',
            '555-123-4567',
            '555.123.4567',
            '5551234567',
            '+1 555 123 4567',
            '+1 (555) 123-4567',
            '+15551234567',
        ];

        foreach ($validNumbers as $number) {
            $v = new Validator(['phone' => $number]);
            $v->rule('phone', 'phone', 'US');
            $this->assertTrue($v->validate(), "Failed for: $number");
        }
    }

    public function testPhoneRealWorldInternational_Examples(): void
    {
        $examples = [
            ['+44 20 7946 0958', 'UK'],
            ['+61 2 1234 5678', 'AU'],
            ['+91 98765 43210', 'IN'],
            ['+49 30 1234 5678', 'DE'],
            ['+33 1 23 45 67 89', 'FR'],
            ['+39 06 1234 5678', 'IT'],
            ['+34 612 345 678', 'ES'],
            ['+55 11 91234 5678', 'BR'],
            ['+52 55 1234 5678', 'MX'],
        ];

        foreach ($examples as [$number, $country]) {
            $v = new Validator(['phone' => $number]);
            $v->rule('phone', 'phone', $country);
            $this->assertTrue($v->validate(), "Failed for $country: $number");
        }
    }
}
