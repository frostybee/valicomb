<?php

declare(strict_types=1);

$original = include __DIR__ . '/../../src/lang/en.php';
$original['required'] = 'A value is required for {field}';
return $original;
