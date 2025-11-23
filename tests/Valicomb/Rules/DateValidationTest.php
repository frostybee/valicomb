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

    // Past Date Tests
    public function testPastDateValid(): void
    {
        // A date from 2020 should be in the past
        $v = new Validator(['birth_date' => '2020-01-01']);
        $v->rule('past', 'birth_date');
        $this->assertTrue($v->validate());
    }

    public function testPastDateValidWithDateTime(): void
    {
        // DateTime from 2020 should be in the past
        $v = new Validator(['event_date' => new DateTime('2020-06-15')]);
        $v->rule('past', 'event_date');
        $this->assertTrue($v->validate());
    }

    public function testPastDateValidWithReferenceDate(): void
    {
        // 2024-01-01 is before 2024-12-31
        $v = new Validator(['date' => '2024-01-01']);
        $v->rule('past', 'date', '2024-12-31');
        $this->assertTrue($v->validate());
    }

    public function testPastDateInvalidWithFutureDate(): void
    {
        // A date far in the future should not be in the past
        $v = new Validator(['appointment' => '2030-01-01']);
        $v->rule('past', 'appointment');
        $this->assertFalse($v->validate());
    }

    public function testPastDateInvalidWithTodayDate(): void
    {
        // Use current timestamp to ensure we're testing "now"
        // A date/time exactly now or in the future should not pass
        $now = new DateTime();
        $v = new Validator(['date' => $now]);
        $v->rule('past', 'date');
        $this->assertFalse($v->validate());
    }

    public function testPastDateHandlesInvalidDate(): void
    {
        $v = new Validator(['date' => 'invalid-date']);
        $v->rule('past', 'date');
        $this->assertFalse($v->validate());
    }

    public function testPastDateHandlesInvalidReferenceDate(): void
    {
        $v = new Validator(['date' => '2024-01-01']);
        $v->rule('past', 'date', 'not-a-date');
        $this->assertFalse($v->validate());
    }

    public function testPastDateWithDateTimeReference(): void
    {
        $v = new Validator(['date' => new DateTime('2024-01-01')]);
        $v->rule('past', 'date', new DateTime('2024-12-31'));
        $this->assertTrue($v->validate());
    }

    // Future Date Tests
    public function testFutureDateValid(): void
    {
        // A date far in the future should be valid
        $v = new Validator(['appointment' => '2030-12-31']);
        $v->rule('future', 'appointment');
        $this->assertTrue($v->validate());
    }

    public function testFutureDateValidWithDateTime(): void
    {
        // DateTime in the future should be valid
        $v = new Validator(['expiry_date' => new DateTime('2030-06-15')]);
        $v->rule('future', 'expiry_date');
        $this->assertTrue($v->validate());
    }

    public function testFutureDateValidWithReferenceDate(): void
    {
        // 2024-12-31 is after 2024-01-01
        $v = new Validator(['date' => '2024-12-31']);
        $v->rule('future', 'date', '2024-01-01');
        $this->assertTrue($v->validate());
    }

    public function testFutureDateInvalidWithPastDate(): void
    {
        // A date from 2020 should not be in the future
        $v = new Validator(['event_date' => '2020-01-01']);
        $v->rule('future', 'event_date');
        $this->assertFalse($v->validate());
    }

    public function testFutureDateInvalidWithTodayDate(): void
    {
        // Use current timestamp to ensure we're testing "now"
        // A date/time exactly now or in the past should not pass
        $now = new DateTime();
        $v = new Validator(['date' => $now]);
        $v->rule('future', 'date');
        $this->assertFalse($v->validate());
    }

    public function testFutureDateHandlesInvalidDate(): void
    {
        $v = new Validator(['date' => 'invalid-date']);
        $v->rule('future', 'date');
        $this->assertFalse($v->validate());
    }

    public function testFutureDateHandlesInvalidReferenceDate(): void
    {
        $v = new Validator(['date' => '2030-01-01']);
        $v->rule('future', 'date', 'not-a-date');
        $this->assertFalse($v->validate());
    }

    public function testFutureDateWithDateTimeReference(): void
    {
        $v = new Validator(['date' => new DateTime('2024-12-31')]);
        $v->rule('future', 'date', new DateTime('2024-01-01'));
        $this->assertTrue($v->validate());
    }

    // Alternative Syntax Tests
    public function testPastDateValidAltSyntax(): void
    {
        $v = new Validator(['birth_date' => '2020-01-01']);
        $v->rules([
            'past' => [
                ['birth_date'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testFutureDateValidAltSyntax(): void
    {
        $v = new Validator(['appointment' => '2030-01-01']);
        $v->rules([
            'future' => [
                ['appointment'],
            ],
        ]);
        $this->assertTrue($v->validate());
    }

    public function testPastDateInvalidAltSyntax(): void
    {
        $v = new Validator(['date' => '2030-01-01']);
        $v->rules([
            'past' => [
                ['date'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    public function testFutureDateInvalidAltSyntax(): void
    {
        $v = new Validator(['date' => '2020-01-01']);
        $v->rules([
            'future' => [
                ['date'],
            ],
        ]);
        $this->assertFalse($v->validate());
    }

    // Empty value tests (should pass when not required)
    public function testPastDateValidWhenEmptyButNotRequired(): void
    {
        $v = new Validator(['date' => '']);
        $v->rule('past', 'date');
        $this->assertTrue($v->validate());
    }

    public function testFutureDateValidWhenEmptyButNotRequired(): void
    {
        $v = new Validator(['date' => '']);
        $v->rule('future', 'date');
        $this->assertTrue($v->validate());
    }
}
