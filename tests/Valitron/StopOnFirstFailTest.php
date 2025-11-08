<?php
declare(strict_types=1);

namespace Valitron\Tests;

use Valitron\Validator;

class StopOnFirstFailTest extends BaseTestCase
{
    public function testStopOnFirstFail()
    {
        $rules = [
            'website' => [
                ['lengthMin', 10, 'message' => 'Website URL must be at least 10 characters'],
                ['url', 'message' => 'Website must be a valid URL'],
                ['urlActive', 'message' => 'Website URL must be active']
            ]
        ];

        $v = new Validator([
            'website' => 'short'
        ]);

        $v->mapFieldsRules($rules);
        $v->stopOnFirstFail(true);
        $this->assertFalse($v->validate());

        $errors = $v->errors();
        // Should only have 1 error (lengthMin), not all 3
        $this->assertCount(1, $errors['website']);
    }
}
