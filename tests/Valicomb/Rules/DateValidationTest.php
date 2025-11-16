<?php

declare(strict_types=1);

namespace Frostybee\Valicomb\Tests\Rules;

use DateTime;
use Frostybee\Valicomb\Tests\BaseTestCase;
use Frostybee\Valicomb\Validator;
use InvalidArgumentException;

class DateValidationTest extends BaseTestCase
{
    // Date Tests
    public function testDateValid(): void
    {
        $v = new Validator(['date' => '2013-01-27']);
        $v->rule('date', 'date');
        $this->assertTrue($v->validate());
    }

    public function testDateValidAltSyntax(): void
    {
        $v = new Validator(['created_at' => '2018-10-13']);
        $v->rules([
            'date' => [
                ['created_at'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testDateValidWithDateTimeObject(): void
    {
        $v = new Validator(['date' => new DateTime()]);
        $v->rule('date', 'date');
        $this->assertTrue($v->validate());
    }

    public function testDateInvalid(): void
    {
        $v = new Validator(['date' => 'no thanks']);
        $v->rule('date', 'date');
        $this->assertFalse($v->validate());
    }

    public function testDateInvalidAltSyntax(): void
    {
        $v = new Validator(['created_at' => 'bananas']);
        $v->rules([
            'date' => [
                ['created_at'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    /**
     * @group issue-13
     */
    public function testDateValidWhenEmptyButNotRequired(): void
    {
        $v = new Validator(['date' => '']);
        $v->rule('date', 'date');
        $this->assertTrue($v->validate());
    }

    // DateFormat Tests
    public function testDateFormatValid(): void
    {
        $v = new Validator(['date' => '2013-01-27']);
        $v->rule('dateFormat', 'date', 'Y-m-d');
        $this->assertTrue($v->validate());
    }

    public function testDateFormatValidAltSyntax(): void
    {
        $v = new Validator(['created_at' => '2018-10-13']);
        $v->rules([
            'dateFormat' => [
                ['created_at', 'Y-m-d'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testDateFormatInvalid(): void
    {
        $v = new Validator(['date' => 'no thanks']);
        $v->rule('dateFormat', 'date', 'Y-m-d');
        $this->assertFalse($v->validate());

        $v = new Validator(['date' => '2013-27-01']);
        $v->rule('dateFormat', 'date', 'Y-m-d');
        $this->assertFalse($v->validate());
    }

    public function testDateFormatInvalidAltSyntax(): void
    {
        $v = new Validator(['created_at' => '10-13-2018']);
        $v->rules([
            'dateFormat' => [
                ['created_at', 'Y-m-d'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // DateBefore Tests
    public function testDateBeforeValid(): void
    {
        $v = new Validator(['date' => '2013-01-27']);
        $v->rule('dateBefore', 'date', new DateTime('2013-01-28'));
        $this->assertTrue($v->validate());
    }

    public function testDateBeforeValidAltSyntax(): void
    {
        $v = new Validator(['created_at' => '2018-09-01']);
        $v->rules([
            'dateBefore' => [
                ['created_at', '2018-10-13'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testDateWarningsWithObjectParams(): void
    {
        $v = new Validator(['startDate' => '2013-01-27', 'endDate' => '2013-05-08']);
        $v->rule(
            'date',
            ['startDate', 'endDate'],
        );

        $v->rule(
            'dateBefore',
            'endDate',
            new DateTime('2013-04-08'),
        )->label('End date')->message('{field} must be before the end of the fiscal year, %s.');

        $v->rule(
            'dateAfter',
            'startDate',
            new DateTime('2013-02-17'),
        )->label('Start date')->message('{field} must be after the beginning of the fiscal year, %s.');

        $this->assertFalse($v->validate());
    }

    public function testDateBeforeInvalid(): void
    {
        $v = new Validator(['date' => '2013-01-27']);
        $v->rule('dateBefore', 'date', '2013-01-26');
        $this->assertFalse($v->validate());
    }

    public function testDateBeforeInvalidAltSyntax(): void
    {
        $v = new Validator(['created_at' => '2018-11-01']);
        $v->rules([
            'dateBefore' => [
                ['created_at', '2018-10-13'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // DateAfter Tests
    public function testDateAfterValid(): void
    {
        $v = new Validator(['date' => '2013-01-27']);
        $v->rule('dateAfter', 'date', new DateTime('2013-01-26'));
        $this->assertTrue($v->validate());
    }

    public function testDateAfterValidAltSyntax(): void
    {
        $v = new Validator(['created_at' => '2018-09-01']);
        $v->rules([
            'dateAfter' => [
                ['created_at', '2018-01-01'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testDateAfterInvalid(): void
    {
        $v = new Validator(['date' => '2013-01-27']);
        $v->rule('dateAfter', 'date', '2013-01-28');
        $this->assertFalse($v->validate());
    }

    public function testDateAfterInvalidAltSyntax(): void
    {
        $v = new Validator(['created_at' => '2017-09-01']);
        $v->rules([
            'dateAfter' => [
                ['created_at', '2018-01-01'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Edge Cases
    public function testDateBeforeHandlesStrtotimeFailure(): void
    {
        // Invalid date string that strtotime cannot parse
        $v = new Validator(['date' => 'not-a-valid-date']);
        $v->rule('dateBefore', 'date', '2025-12-31');
        $this->assertFalse($v->validate(), 'Invalid date should fail validation');
    }

    public function testDateBeforeHandlesInvalidComparisonDate(): void
    {
        $v = new Validator(['date' => '2025-01-01']);
        $v->rule('dateBefore', 'date', 'invalid-date-string');
        $this->assertFalse($v->validate(), 'Invalid comparison date should fail');
    }

    public function testDateAfterHandlesStrtotimeFailure(): void
    {
        // Invalid date string that strtotime cannot parse
        $v = new Validator(['date' => 'invalid-date']);
        $v->rule('dateAfter', 'date', '2025-01-01');
        $this->assertFalse($v->validate(), 'Invalid date should fail validation');
    }

    public function testDateAfterHandlesInvalidComparisonDate(): void
    {
        $v = new Validator(['date' => '2025-12-31']);
        $v->rule('dateAfter', 'date', 'not-a-date');
        $this->assertFalse($v->validate(), 'Invalid comparison date should fail');
    }

    public function testDateBeforeWithDateTimeObjects(): void
    {
        $v = new Validator(['date' => new DateTime('2025-01-01')]);
        $v->rule('dateBefore', 'date', new DateTime('2025-12-31'));
        $this->assertTrue($v->validate(), 'Earlier DateTime should be before later DateTime');
    }

    public function testDateAfterWithDateTimeObjects(): void
    {
        $v = new Validator(['date' => new DateTime('2025-12-31')]);
        $v->rule('dateAfter', 'date', new DateTime('2025-01-01'));
        $this->assertTrue($v->validate(), 'Later DateTime should be after earlier DateTime');
    }

    // Parameter Validation Tests
    public function testDateFormatRequiresStringParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Date format parameter must be a string');

        $v = new Validator(['date' => '2025-01-01']);
        $v->rule('dateFormat', 'date', 123); // Integer instead of string
        $v->validate();
    }

    public function testDateFormatRequiresParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Date format parameter must be a string');

        $v = new Validator(['date' => '2025-01-01']);
        $v->rule('dateFormat', 'date'); // Missing parameter
        $v->validate();
    }

    public function testDateBeforeRequiresParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Comparison date required for dateBefore validation');

        $v = new Validator(['date' => '2025-01-01']);
        $v->rule('dateBefore', 'date'); // Missing parameter
        $v->validate();
    }

    public function testDateAfterRequiresParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Comparison date required for dateAfter validation');

        $v = new Validator(['date' => '2025-01-01']);
        $v->rule('dateAfter', 'date'); // Missing parameter
        $v->validate();
    }
}
