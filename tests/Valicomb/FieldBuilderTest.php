<?php

declare(strict_types=1);

namespace Tests\Valicomb;

use DateTime;
use Frostybee\Valicomb\FieldBuilder;
use Frostybee\Valicomb\Validator;
use PHPUnit\Framework\TestCase;

use function str_repeat;
use function strlen;

/**
 * Test cases for the FieldBuilder fluent API.
 */
class FieldBuilderTest extends TestCase
{
    // ===========================================
    // Phase 1: Core Infrastructure Tests
    // ===========================================

    public function testFieldMethodReturnsFieldBuilder(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $builder = $v->field('email');

        $this->assertInstanceOf(FieldBuilder::class, $builder);
    }

    public function testFieldBuilderStoresFieldName(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $builder = $v->field('email');

        $this->assertSame('email', $builder->getFieldName());
    }

    public function testFieldBuilderStoresValidator(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $builder = $v->field('email');

        $this->assertSame($v, $builder->getValidator());
    }

    public function testFieldChainingCreatesNewBuilder(): void
    {
        $v = new Validator(['email' => 'test@example.com', 'name' => 'John']);
        $builder1 = $v->field('email');
        $builder2 = $builder1->field('name');

        $this->assertNotSame($builder1, $builder2);
        $this->assertSame('email', $builder1->getFieldName());
        $this->assertSame('name', $builder2->getFieldName());
    }

    public function testEndReturnsValidator(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $builder = $v->field('email');
        $returnedValidator = $builder->end();

        $this->assertSame($v, $returnedValidator);
    }

    public function testCustomCallableRule(): void
    {
        $v = new Validator(['code' => 'ABC123']);
        $v->field('code')
            ->rule(fn ($field, $value) => strlen($value) === 6);

        $this->assertTrue($v->validate());
    }

    public function testCustomCallableRuleFails(): void
    {
        $v = new Validator(['code' => 'ABC']);
        $v->field('code')
            ->rule(fn ($field, $value) => strlen($value) === 6);

        $this->assertFalse($v->validate());
    }

    public function testCustomMessageOnRule(): void
    {
        $v = new Validator(['email' => '']);
        $v->field('email')
            ->rule('required')
            ->message('Please provide your email');

        $v->validate();
        $errors = $v->errors('email');

        $this->assertIsArray($errors);
        $this->assertStringContainsString('Please provide your email', $errors[0]);
    }

    public function testLabelMethod(): void
    {
        $v = new Validator(['email_address' => '']);
        $v->field('email_address')
            ->label('Email Address')
            ->rule('required');

        $v->validate();
        $errors = $v->errors('email_address');

        $this->assertIsArray($errors);
        $this->assertStringContainsString('Email Address', $errors[0]);
    }

    public function testMultipleFieldsWithChaining(): void
    {
        $v = new Validator([
            'email' => 'test@example.com',
            'name' => 'John',
        ]);

        $v->field('email')
            ->rule('required')
            ->rule('email')
            ->field('name')
            ->rule('required');

        $this->assertTrue($v->validate());
    }

    public function testMultipleFieldsWithChainingFails(): void
    {
        $v = new Validator([
            'email' => 'invalid-email',
            'name' => '',
        ]);

        $v->field('email')
            ->rule('required')
            ->rule('email')
            ->field('name')
            ->rule('required');

        $this->assertFalse($v->validate());

        $errors = $v->errors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testMixedWithTraditionalSyntax(): void
    {
        $v = new Validator([
            'email' => 'test@example.com',
            'name' => 'John',
            'age' => 25,
        ]);

        // Fluent syntax
        $v->field('email')->rule('required')->rule('email');

        // Traditional syntax
        $v->rule('required', 'name');
        $v->forFields([
            'age' => [['required'], ['integer']],
        ]);

        $this->assertTrue($v->validate());
    }

    public function testRuleMethodReturnsSelf(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $builder = $v->field('email');
        $returned = $builder->rule('required');

        $this->assertSame($builder, $returned);
    }

    public function testMessageMethodReturnsSelf(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $builder = $v->field('email')->rule('required');
        $returned = $builder->message('Custom message');

        $this->assertInstanceOf(FieldBuilder::class, $returned);
    }

    public function testLabelMethodReturnsSelf(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $builder = $v->field('email');
        $returned = $builder->label('Email');

        $this->assertSame($builder, $returned);
    }

    public function testComplexChainingScenario(): void
    {
        $v = new Validator([
            'email' => 'user@example.com',
            'password' => 'secret123',
            'confirm' => 'secret123',
        ]);

        $v->field('email')
            ->label('Email Address')
            ->rule('required')->message('Email is required')
            ->rule('email')->message('Please enter a valid email')
            ->field('password')
            ->label('Password')
            ->rule('required')
            ->rule('lengthMin', 8)
            ->field('confirm')
            ->rule('required')
            ->rule('equals', 'password');

        $this->assertTrue($v->validate());
    }

    public function testValidationAfterEndMethod(): void
    {
        $v = new Validator(['email' => 'test@example.com']);

        $result = $v->field('email')
            ->rule('required')
            ->rule('email')
            ->end()
            ->validate();

        $this->assertTrue($result);
    }

    // ===========================================
    // Phase 2: Conditional & Comparison Rules Tests
    // ===========================================

    public function testRequiredMethod(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $v->field('email')->required();

        $this->assertTrue($v->validate());
    }

    public function testRequiredMethodFails(): void
    {
        $v = new Validator(['email' => '']);
        $v->field('email')->required();

        $this->assertFalse($v->validate());
    }

    public function testRequiredAllowEmpty(): void
    {
        $v = new Validator(['email' => '']);
        $v->field('email')->required(true);

        $this->assertTrue($v->validate());
    }

    public function testOptionalMethod(): void
    {
        $v = new Validator([]);
        $v->field('nickname')->optional()->rule('alphaNum');

        $this->assertTrue($v->validate());
    }

    public function testOptionalWithValuePresent(): void
    {
        $v = new Validator(['nickname' => 'john123']);
        $v->field('nickname')->optional()->rule('alphaNum');

        $this->assertTrue($v->validate());
    }

    public function testOptionalWithInvalidValuePresent(): void
    {
        $v = new Validator(['nickname' => 'john@123']);
        $v->field('nickname')->optional()->rule('alphaNum');

        $this->assertFalse($v->validate());
    }

    public function testNullableMethod(): void
    {
        $v = new Validator(['middle_name' => null]);
        $v->field('middle_name')->nullable()->rule('alpha');

        $this->assertTrue($v->validate());
    }

    public function testNullableWithValuePresent(): void
    {
        $v = new Validator(['middle_name' => 'John']);
        $v->field('middle_name')->nullable()->rule('alpha');

        $this->assertTrue($v->validate());
    }

    public function testRequiredWithMethod(): void
    {
        $v = new Validator(['address' => '123 Main St', 'city' => 'NYC']);
        $v->field('city')->requiredWith('address');

        $this->assertTrue($v->validate());
    }

    public function testRequiredWithMethodFails(): void
    {
        $v = new Validator(['address' => '123 Main St']);
        $v->field('city')->requiredWith('address');

        $this->assertFalse($v->validate());
    }

    public function testRequiredWithMethodNotTriggered(): void
    {
        $v = new Validator([]);
        $v->field('city')->requiredWith('address');

        $this->assertTrue($v->validate());
    }

    public function testRequiredWithMultipleFieldsAny(): void
    {
        $v = new Validator(['address' => '123 Main St', 'zip' => '10001']);
        $v->field('zip')->requiredWith(['address', 'city']);

        $this->assertTrue($v->validate());
    }

    public function testRequiredWithMultipleFieldsStrict(): void
    {
        $v = new Validator(['address' => '123 Main St', 'city' => 'NYC', 'country' => 'USA']);
        $v->field('country')->requiredWith(['address', 'city'], true);

        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutMethod(): void
    {
        $v = new Validator(['phone' => '1234567890']);
        $v->field('phone')->requiredWithout('email');

        $this->assertTrue($v->validate());
    }

    public function testRequiredWithoutMethodFails(): void
    {
        $v = new Validator([]);
        $v->field('phone')->requiredWithout('email');

        $this->assertFalse($v->validate());
    }

    public function testRequiredWithoutNotTriggered(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $v->field('phone')->requiredWithout('email');

        $this->assertTrue($v->validate());
    }

    public function testEqualsMethod(): void
    {
        $v = new Validator(['password' => 'secret', 'confirm' => 'secret']);
        $v->field('confirm')->equals('password');

        $this->assertTrue($v->validate());
    }

    public function testEqualsMethodFails(): void
    {
        $v = new Validator(['password' => 'secret', 'confirm' => 'different']);
        $v->field('confirm')->equals('password');

        $this->assertFalse($v->validate());
    }

    public function testDifferentMethod(): void
    {
        $v = new Validator(['old_password' => 'old', 'new_password' => 'new']);
        $v->field('new_password')->different('old_password');

        $this->assertTrue($v->validate());
    }

    public function testDifferentMethodFails(): void
    {
        $v = new Validator(['old_password' => 'same', 'new_password' => 'same']);
        $v->field('new_password')->different('old_password');

        $this->assertFalse($v->validate());
    }

    public function testAcceptedMethod(): void
    {
        $v = new Validator(['terms' => true]);
        $v->field('terms')->accepted();

        $this->assertTrue($v->validate());
    }

    public function testAcceptedMethodWithYes(): void
    {
        $v = new Validator(['terms' => 'yes']);
        $v->field('terms')->accepted();

        $this->assertTrue($v->validate());
    }

    public function testAcceptedMethodWithOn(): void
    {
        $v = new Validator(['terms' => 'on']);
        $v->field('terms')->accepted();

        $this->assertTrue($v->validate());
    }

    public function testAcceptedMethodWith1(): void
    {
        $v = new Validator(['terms' => 1]);
        $v->field('terms')->accepted();

        $this->assertTrue($v->validate());
    }

    public function testAcceptedMethodFails(): void
    {
        $v = new Validator(['terms' => false]);
        $v->field('terms')->accepted();

        $this->assertFalse($v->validate());
    }

    public function testConditionalRulesChaining(): void
    {
        $v = new Validator([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'confirm' => 'secret123',
            'terms' => true,
        ]);

        $v->field('email')
            ->required()
            ->rule('email')
            ->field('password')
            ->required()
            ->field('confirm')
            ->required()
            ->equals('password')
            ->field('terms')
            ->accepted();

        $this->assertTrue($v->validate());
    }

    // ===========================================
    // Phase 3: String & Length Rules Tests
    // ===========================================

    public function testAlphaMethod(): void
    {
        $v = new Validator(['name' => 'John']);
        $v->field('name')->alpha();

        $this->assertTrue($v->validate());
    }

    public function testAlphaMethodFails(): void
    {
        $v = new Validator(['name' => 'John123']);
        $v->field('name')->alpha();

        $this->assertFalse($v->validate());
    }

    public function testAlphaNumMethod(): void
    {
        $v = new Validator(['username' => 'john123']);
        $v->field('username')->alphaNum();

        $this->assertTrue($v->validate());
    }

    public function testAlphaNumMethodFails(): void
    {
        $v = new Validator(['username' => 'john@123']);
        $v->field('username')->alphaNum();

        $this->assertFalse($v->validate());
    }

    public function testAsciiMethod(): void
    {
        $v = new Validator(['code' => 'ABC123!@#']);
        $v->field('code')->ascii();

        $this->assertTrue($v->validate());
    }

    public function testAsciiMethodFails(): void
    {
        $v = new Validator(['code' => 'ABCé123']);
        $v->field('code')->ascii();

        $this->assertFalse($v->validate());
    }

    public function testSlugMethod(): void
    {
        $v = new Validator(['url_slug' => 'my-blog-post']);
        $v->field('url_slug')->slug();

        $this->assertTrue($v->validate());
    }

    public function testSlugMethodFails(): void
    {
        $v = new Validator(['url_slug' => 'My Blog Post']);
        $v->field('url_slug')->slug();

        $this->assertFalse($v->validate());
    }

    public function testContainsMethod(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $v->field('email')->contains('@');

        $this->assertTrue($v->validate());
    }

    public function testContainsMethodFails(): void
    {
        $v = new Validator(['text' => 'hello world']);
        $v->field('text')->contains('xyz');

        $this->assertFalse($v->validate());
    }

    public function testContainsMethodStrict(): void
    {
        $v = new Validator(['text' => 'Hello ABC World']);
        $v->field('text')->contains('ABC', true);

        $this->assertTrue($v->validate());
    }

    public function testContainsMethodStrictFails(): void
    {
        $v = new Validator(['text' => 'Hello abc World']);
        $v->field('text')->contains('ABC', true);

        $this->assertFalse($v->validate());
    }

    public function testRegexMethod(): void
    {
        $v = new Validator(['phone' => '1234567890']);
        $v->field('phone')->regex('/^[0-9]{10}$/');

        $this->assertTrue($v->validate());
    }

    public function testRegexMethodFails(): void
    {
        $v = new Validator(['phone' => '12345']);
        $v->field('phone')->regex('/^[0-9]{10}$/');

        $this->assertFalse($v->validate());
    }

    public function testStartsWithMethod(): void
    {
        $v = new Validator(['url' => 'https://example.com']);
        $v->field('url')->startsWith('https://');

        $this->assertTrue($v->validate());
    }

    public function testStartsWithMethodFails(): void
    {
        $v = new Validator(['url' => 'ftp://example.com']);
        $v->field('url')->startsWith('http');

        $this->assertFalse($v->validate());
    }

    public function testStartsWithMultiple(): void
    {
        $v = new Validator(['url' => 'https://example.com']);
        $v->field('url')->startsWith(['http://', 'https://']);

        $this->assertTrue($v->validate());
    }

    public function testEndsWithMethod(): void
    {
        $v = new Validator(['filename' => 'document.pdf']);
        $v->field('filename')->endsWith('.pdf');

        $this->assertTrue($v->validate());
    }

    public function testEndsWithMethodFails(): void
    {
        $v = new Validator(['filename' => 'document.doc']);
        $v->field('filename')->endsWith('.pdf');

        $this->assertFalse($v->validate());
    }

    public function testEndsWithMultiple(): void
    {
        $v = new Validator(['filename' => 'image.png']);
        $v->field('filename')->endsWith(['.jpg', '.png', '.gif']);

        $this->assertTrue($v->validate());
    }

    public function testUuidMethod(): void
    {
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->field('id')->uuid();

        $this->assertTrue($v->validate());
    }

    public function testUuidMethodFails(): void
    {
        $v = new Validator(['id' => 'not-a-uuid']);
        $v->field('id')->uuid();

        $this->assertFalse($v->validate());
    }

    public function testUuidMethodWithVersion(): void
    {
        $v = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $v->field('id')->uuid(4);

        $this->assertTrue($v->validate());
    }

    public function testPasswordStrengthMethodWithScore(): void
    {
        $v = new Validator(['password' => 'SecureP@ss123']);
        $v->field('password')->passwordStrength(3);

        $this->assertTrue($v->validate());
    }

    public function testPasswordStrengthMethodWithArray(): void
    {
        $v = new Validator(['password' => 'Secure@123']);
        $v->field('password')->passwordStrength([
            'min' => 8,
            'uppercase' => 1,
            'number' => 1,
            'special' => 1,
        ]);

        $this->assertTrue($v->validate());
    }

    public function testLengthMethod(): void
    {
        $v = new Validator(['pin' => '1234']);
        $v->field('pin')->length(4);

        $this->assertTrue($v->validate());
    }

    public function testLengthMethodFails(): void
    {
        $v = new Validator(['pin' => '12345']);
        $v->field('pin')->length(4);

        $this->assertFalse($v->validate());
    }

    public function testLengthBetweenMethod(): void
    {
        $v = new Validator(['username' => 'john']);
        $v->field('username')->lengthBetween(3, 20);

        $this->assertTrue($v->validate());
    }

    public function testLengthBetweenMethodFailsTooShort(): void
    {
        $v = new Validator(['username' => 'jo']);
        $v->field('username')->lengthBetween(3, 20);

        $this->assertFalse($v->validate());
    }

    public function testLengthBetweenMethodFailsTooLong(): void
    {
        $v = new Validator(['username' => 'johndoewithverylongusername']);
        $v->field('username')->lengthBetween(3, 20);

        $this->assertFalse($v->validate());
    }

    public function testLengthMinMethod(): void
    {
        $v = new Validator(['password' => 'secretpass']);
        $v->field('password')->lengthMin(8);

        $this->assertTrue($v->validate());
    }

    public function testLengthMinMethodFails(): void
    {
        $v = new Validator(['password' => 'short']);
        $v->field('password')->lengthMin(8);

        $this->assertFalse($v->validate());
    }

    public function testLengthMaxMethod(): void
    {
        $v = new Validator(['bio' => 'Short bio']);
        $v->field('bio')->lengthMax(500);

        $this->assertTrue($v->validate());
    }

    public function testLengthMaxMethodFails(): void
    {
        $v = new Validator(['bio' => str_repeat('a', 501)]);
        $v->field('bio')->lengthMax(500);

        $this->assertFalse($v->validate());
    }

    public function testStringRulesChaining(): void
    {
        $v = new Validator([
            'username' => 'johndoe123',
            'slug' => 'my-post-title',
            'password' => 'Secure@123',
        ]);

        $v->field('username')
            ->required()
            ->alphaNum()
            ->lengthBetween(3, 20)
            ->field('slug')
            ->required()
            ->slug()
            ->field('password')
            ->required()
            ->lengthMin(8);

        $this->assertTrue($v->validate());
    }

    // ===========================================
    // Phase 4: Numeric & Date Rules Tests
    // ===========================================

    public function testNumericMethod(): void
    {
        $v = new Validator(['price' => '19.99']);
        $v->field('price')->numeric();

        $this->assertTrue($v->validate());
    }

    public function testNumericMethodFails(): void
    {
        $v = new Validator(['price' => 'not-a-number']);
        $v->field('price')->numeric();

        $this->assertFalse($v->validate());
    }

    public function testIntegerMethod(): void
    {
        $v = new Validator(['quantity' => '123']);
        $v->field('quantity')->integer();

        $this->assertTrue($v->validate());
    }

    public function testIntegerMethodFails(): void
    {
        $v = new Validator(['quantity' => '12.5']);
        $v->field('quantity')->integer();

        $this->assertFalse($v->validate());
    }

    public function testIntegerMethodStrict(): void
    {
        $v = new Validator(['count' => 123]);
        $v->field('count')->integer(true);

        $this->assertTrue($v->validate());
    }

    public function testIntegerMethodStrictFails(): void
    {
        // Strict mode rejects floats and arrays
        $v = new Validator(['count' => 12.5]);
        $v->field('count')->integer(true);

        $this->assertFalse($v->validate());
    }

    public function testMinMethod(): void
    {
        $v = new Validator(['age' => 25]);
        $v->field('age')->min(18);

        $this->assertTrue($v->validate());
    }

    public function testMinMethodFails(): void
    {
        $v = new Validator(['age' => 15]);
        $v->field('age')->min(18);

        $this->assertFalse($v->validate());
    }

    public function testMaxMethod(): void
    {
        $v = new Validator(['age' => 100]);
        $v->field('age')->max(120);

        $this->assertTrue($v->validate());
    }

    public function testMaxMethodFails(): void
    {
        $v = new Validator(['age' => 150]);
        $v->field('age')->max(120);

        $this->assertFalse($v->validate());
    }

    public function testBetweenMethod(): void
    {
        $v = new Validator(['rating' => 3]);
        $v->field('rating')->between(1, 5);

        $this->assertTrue($v->validate());
    }

    public function testBetweenMethodFails(): void
    {
        $v = new Validator(['rating' => 6]);
        $v->field('rating')->between(1, 5);

        $this->assertFalse($v->validate());
    }

    public function testBooleanMethod(): void
    {
        $v = new Validator(['is_active' => true]);
        $v->field('is_active')->boolean();

        $this->assertTrue($v->validate());
    }

    public function testBooleanMethodWithFalse(): void
    {
        $v = new Validator(['is_active' => false]);
        $v->field('is_active')->boolean();

        $this->assertTrue($v->validate());
    }

    public function testPositiveMethod(): void
    {
        $v = new Validator(['quantity' => 5]);
        $v->field('quantity')->positive();

        $this->assertTrue($v->validate());
    }

    public function testPositiveMethodFails(): void
    {
        $v = new Validator(['quantity' => -5]);
        $v->field('quantity')->positive();

        $this->assertFalse($v->validate());
    }

    public function testDecimalPlacesMethod(): void
    {
        $v = new Validator(['price' => 19.99]);
        $v->field('price')->decimalPlaces(2);

        $this->assertTrue($v->validate());
    }

    public function testDecimalPlacesMethodFails(): void
    {
        $v = new Validator(['price' => 19.999]);
        $v->field('price')->decimalPlaces(2);

        $this->assertFalse($v->validate());
    }

    public function testDateMethod(): void
    {
        $v = new Validator(['birthday' => '2000-01-15']);
        $v->field('birthday')->date();

        $this->assertTrue($v->validate());
    }

    public function testDateMethodFails(): void
    {
        $v = new Validator(['birthday' => 'not-a-date']);
        $v->field('birthday')->date();

        $this->assertFalse($v->validate());
    }

    public function testDateFormatMethod(): void
    {
        $v = new Validator(['event_date' => '2024-12-25']);
        $v->field('event_date')->dateFormat('Y-m-d');

        $this->assertTrue($v->validate());
    }

    public function testDateFormatMethodFails(): void
    {
        $v = new Validator(['event_date' => '12/25/2024']);
        $v->field('event_date')->dateFormat('Y-m-d');

        $this->assertFalse($v->validate());
    }

    public function testDateBeforeMethod(): void
    {
        $v = new Validator(['start_date' => '2024-01-01']);
        $v->field('start_date')->dateBefore('2025-01-01');

        $this->assertTrue($v->validate());
    }

    public function testDateBeforeMethodFails(): void
    {
        $v = new Validator(['start_date' => '2026-01-01']);
        $v->field('start_date')->dateBefore('2025-01-01');

        $this->assertFalse($v->validate());
    }

    public function testDateAfterMethod(): void
    {
        $v = new Validator(['start_date' => '2025-06-01']);
        $v->field('start_date')->dateAfter('2024-01-01');

        $this->assertTrue($v->validate());
    }

    public function testDateAfterMethodFails(): void
    {
        $v = new Validator(['start_date' => '2023-01-01']);
        $v->field('start_date')->dateAfter('2024-01-01');

        $this->assertFalse($v->validate());
    }

    public function testPastMethod(): void
    {
        $v = new Validator(['birthday' => '2000-01-01']);
        $v->field('birthday')->past();

        $this->assertTrue($v->validate());
    }

    public function testPastMethodFails(): void
    {
        $v = new Validator(['birthday' => '2030-01-01']);
        $v->field('birthday')->past();

        $this->assertFalse($v->validate());
    }

    public function testFutureMethod(): void
    {
        $v = new Validator(['appointment' => '2030-01-01']);
        $v->field('appointment')->future();

        $this->assertTrue($v->validate());
    }

    public function testFutureMethodFails(): void
    {
        $v = new Validator(['appointment' => '2020-01-01']);
        $v->field('appointment')->future();

        $this->assertFalse($v->validate());
    }

    public function testNumericRulesChaining(): void
    {
        $v = new Validator([
            'age' => 25,
            'price' => 19.99,
            'quantity' => 5,
        ]);

        $v->field('age')
            ->required()
            ->integer()
            ->min(18)
            ->max(120)
            ->field('price')
            ->required()
            ->numeric()
            ->positive()
            ->field('quantity')
            ->required()
            ->integer()
            ->between(1, 100);

        $this->assertTrue($v->validate());
    }

    public function testDateRulesChaining(): void
    {
        $v = new Validator([
            'birthday' => '2000-01-01',
            'appointment' => '2030-06-15',
        ]);

        $v->field('birthday')
            ->required()
            ->date()
            ->past()
            ->field('appointment')
            ->required()
            ->dateFormat('Y-m-d')
            ->future();

        $this->assertTrue($v->validate());
    }

    // ===========================================
    // Phase 5: Network, Array & Type Rules Tests
    // ===========================================

    public function testIpMethod(): void
    {
        $v = new Validator(['server_ip' => '192.168.1.1']);
        $v->field('server_ip')->ip();

        $this->assertTrue($v->validate());
    }

    public function testIpMethodFails(): void
    {
        $v = new Validator(['server_ip' => 'not-an-ip']);
        $v->field('server_ip')->ip();

        $this->assertFalse($v->validate());
    }

    public function testIpv4Method(): void
    {
        $v = new Validator(['server_ip' => '192.168.1.1']);
        $v->field('server_ip')->ipv4();

        $this->assertTrue($v->validate());
    }

    public function testIpv4MethodFails(): void
    {
        $v = new Validator(['server_ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        $v->field('server_ip')->ipv4();

        $this->assertFalse($v->validate());
    }

    public function testIpv6Method(): void
    {
        $v = new Validator(['server_ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        $v->field('server_ip')->ipv6();

        $this->assertTrue($v->validate());
    }

    public function testIpv6MethodFails(): void
    {
        $v = new Validator(['server_ip' => '192.168.1.1']);
        $v->field('server_ip')->ipv6();

        $this->assertFalse($v->validate());
    }

    public function testEmailMethod(): void
    {
        $v = new Validator(['email' => 'test@example.com']);
        $v->field('email')->email();

        $this->assertTrue($v->validate());
    }

    public function testEmailMethodFails(): void
    {
        $v = new Validator(['email' => 'not-an-email']);
        $v->field('email')->email();

        $this->assertFalse($v->validate());
    }

    public function testUrlMethod(): void
    {
        $v = new Validator(['website' => 'https://example.com']);
        $v->field('website')->url();

        $this->assertTrue($v->validate());
    }

    public function testUrlMethodFails(): void
    {
        $v = new Validator(['website' => 'not-a-url']);
        $v->field('website')->url();

        $this->assertFalse($v->validate());
    }

    public function testArrayMethod(): void
    {
        $v = new Validator(['tags' => ['php', 'validation']]);
        $v->field('tags')->array();

        $this->assertTrue($v->validate());
    }

    public function testArrayMethodFails(): void
    {
        $v = new Validator(['tags' => 'not-an-array']);
        $v->field('tags')->array();

        $this->assertFalse($v->validate());
    }

    public function testInMethod(): void
    {
        $v = new Validator(['status' => 'active']);
        $v->field('status')->in(['active', 'pending', 'inactive']);

        $this->assertTrue($v->validate());
    }

    public function testInMethodFails(): void
    {
        $v = new Validator(['status' => 'unknown']);
        $v->field('status')->in(['active', 'pending', 'inactive']);

        $this->assertFalse($v->validate());
    }

    public function testNotInMethod(): void
    {
        $v = new Validator(['username' => 'john']);
        $v->field('username')->notIn(['admin', 'root', 'system']);

        $this->assertTrue($v->validate());
    }

    public function testNotInMethodFails(): void
    {
        $v = new Validator(['username' => 'admin']);
        $v->field('username')->notIn(['admin', 'root', 'system']);

        $this->assertFalse($v->validate());
    }

    public function testListContainsMethod(): void
    {
        $v = new Validator(['permissions' => ['read', 'write', 'admin']]);
        $v->field('permissions')->listContains('admin');

        $this->assertTrue($v->validate());
    }

    public function testListContainsMethodFails(): void
    {
        $v = new Validator(['permissions' => ['read', 'write']]);
        $v->field('permissions')->listContains('admin');

        $this->assertFalse($v->validate());
    }

    public function testSubsetMethod(): void
    {
        $v = new Validator(['roles' => ['admin', 'editor']]);
        $v->field('roles')->subset(['admin', 'editor', 'viewer']);

        $this->assertTrue($v->validate());
    }

    public function testSubsetMethodFails(): void
    {
        $v = new Validator(['roles' => ['admin', 'superuser']]);
        $v->field('roles')->subset(['admin', 'editor', 'viewer']);

        $this->assertFalse($v->validate());
    }

    public function testContainsUniqueMethod(): void
    {
        $v = new Validator(['emails' => ['a@test.com', 'b@test.com', 'c@test.com']]);
        $v->field('emails')->containsUnique();

        $this->assertTrue($v->validate());
    }

    public function testContainsUniqueMethodFails(): void
    {
        $v = new Validator(['emails' => ['a@test.com', 'b@test.com', 'a@test.com']]);
        $v->field('emails')->containsUnique();

        $this->assertFalse($v->validate());
    }

    public function testArrayHasKeysMethod(): void
    {
        $v = new Validator(['address' => ['street' => '123 Main', 'city' => 'NYC', 'zip' => '10001']]);
        $v->field('address')->arrayHasKeys(['street', 'city', 'zip']);

        $this->assertTrue($v->validate());
    }

    public function testArrayHasKeysMethodFails(): void
    {
        $v = new Validator(['address' => ['street' => '123 Main', 'city' => 'NYC']]);
        $v->field('address')->arrayHasKeys(['street', 'city', 'zip']);

        $this->assertFalse($v->validate());
    }

    public function testInstanceOfMethod(): void
    {
        $v = new Validator(['date' => new DateTime()]);
        $v->field('date')->instanceOf(DateTime::class);

        $this->assertTrue($v->validate());
    }

    public function testInstanceOfMethodFails(): void
    {
        $v = new Validator(['date' => '2024-01-01']);
        $v->field('date')->instanceOf(DateTime::class);

        $this->assertFalse($v->validate());
    }

    public function testCreditCardMethod(): void
    {
        // Test Visa card number
        $v = new Validator(['card' => '4111111111111111']);
        $v->field('card')->creditCard();

        $this->assertTrue($v->validate());
    }

    public function testCreditCardMethodFails(): void
    {
        $v = new Validator(['card' => '1234567890123456']);
        $v->field('card')->creditCard();

        $this->assertFalse($v->validate());
    }

    public function testNetworkRulesChaining(): void
    {
        $v = new Validator([
            'email' => 'test@example.com',
            'website' => 'https://example.com',
            'server_ip' => '192.168.1.1',
        ]);

        $v->field('email')
            ->required()
            ->email()
            ->field('website')
            ->required()
            ->url()
            ->field('server_ip')
            ->required()
            ->ipv4();

        $this->assertTrue($v->validate());
    }

    public function testArrayRulesChaining(): void
    {
        $v = new Validator([
            'tags' => ['php', 'validation', 'library'],
            'status' => 'active',
            'roles' => ['admin', 'editor'],
        ]);

        $v->field('tags')
            ->required()
            ->array()
            ->containsUnique()
            ->field('status')
            ->required()
            ->in(['active', 'pending', 'inactive'])
            ->field('roles')
            ->required()
            ->subset(['admin', 'editor', 'viewer']);

        $this->assertTrue($v->validate());
    }

    public function testCompleteFluentApiExample(): void
    {
        $v = new Validator([
            'email' => 'user@example.com',
            'password' => 'Secure@123',
            'confirm_password' => 'Secure@123',
            'age' => 25,
            'terms' => true,
            'tags' => ['php', 'validation'],
            'status' => 'active',
        ]);


        $v->field('email')
            ->label('Email Address')
            ->required()->message('Email is required')
            ->email()->message('Invalid email format')
            ->lengthMax(254)
            ->field('password')
            ->label('Password')
            ->required()
            ->lengthMin(8)
            ->field('confirm_password')
            ->required()
            ->equals('password')
            ->field('age')
            ->required()
            ->integer()
            ->min(18)
            ->field('terms')
            ->accepted()
            ->field('tags')
            ->optional()
            ->array()
            ->containsUnique()
            ->field('status')
            ->required()
            ->in(['active', 'pending', 'inactive']);

        $this->assertTrue($v->validate());
    }
}
