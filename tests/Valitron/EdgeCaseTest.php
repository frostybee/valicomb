<?php

declare(strict_types=1);

namespace Valitron\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function str_repeat;

use Valitron\Validator;

/**
 * Edge case tests for cross-cutting concerns
 *
 * Tests in this file cover edge cases that don't fit into specific rule categories,
 * such as security concerns (ReDoS) and integration scenarios.
 */
class EdgeCaseTest extends TestCase
{
    /**
     * Test ReDoS protection with reduced limits
     */
    public function testReDoSProtectionWithReducedLimits(): void
    {
        // Use a longer string to ensure backtrack limit is hit even with reduced limits
        $v = new Validator(['field' => str_repeat('a', 1000) . 'x']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/(Backtrack|Recursion) limit/');

        // Catastrophic backtracking pattern
        $v->rule('regex', 'field', '/^(a+)+$/');
        $v->validate();
    }

    /**
     * Test parameter validation doesn't break existing functionality
     */
    public function testParameterValidationDoesntBreakExistingBehavior(): void
    {
        // Test that valid parameters still work
        $v = new Validator([
            'password' => 'secret123',
            'password_confirm' => 'secret123',
            'name' => 'John Doe',
            'birthdate' => '1990-01-01',
        ]);

        $v->rule('equals', 'password', 'password_confirm')
          ->rule('lengthBetween', 'password', 8, 20)
          ->rule('lengthMin', 'name', 3)
          ->rule('dateFormat', 'birthdate', 'Y-m-d')
          ->rule('dateBefore', 'birthdate', '2010-01-01');

        $this->assertTrue($v->validate(), 'Valid parameters should still work correctly');
    }
}
