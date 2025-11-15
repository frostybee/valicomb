<?php

declare(strict_types=1);

namespace Valicomb\Benchmarks;

use Valicomb\Validator;

/**
 * Comprehensive benchmark script to measure validation performance accurately.
 *
 * This benchmark separates different performance aspects:
 * - Pure validation execution (primary metric)
 * - Instance creation overhead
 * - Rule setup overhead
 * - End-to-end performance
 *
 * Run with: php benchmarks/ValidationBenchmark.php
 * Or: composer benchmark
 */
class ValidationBenchmark
{
    private const ITERATIONS = 10000;
    private const WARMUP_ITERATIONS = 1000;
    private const LARGE_DATASET_ITERATIONS = 1000;

    public function run(): void
    {
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║         Valicomb Performance Benchmark (Accurate)           ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";

        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Opcache: " . (ini_get('opcache.enable') ? 'Enabled' : 'Disabled') . "\n";
        echo "JIT: " . (ini_get('opcache.jit') ?: 'Disabled') . "\n";
        echo "Iterations: " . number_format(self::ITERATIONS) . "\n\n";

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  PURE VALIDATION PERFORMANCE (Primary Metric)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $this->benchmarkSimpleValidation();
        $this->benchmarkComplexValidation();
        $this->benchmarkNestedValidation();
        $this->benchmarkLargeDataset();

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  OVERHEAD ANALYSIS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $this->benchmarkInstanceCreation();
        $this->benchmarkRuleSetup();

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  END-TO-END PERFORMANCE (Real-World Usage)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $this->benchmarkEndToEnd();

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        echo "💡 TIP: For best performance, reuse Validator instances and\n";
        echo "   configure rules once, then call validate() multiple times.\n\n";
    }

    /**
     * Benchmark pure validation execution (setup done once outside loop)
     */
    private function benchmarkSimpleValidation(): void
    {
        $data = ['email' => 'test@example.com', 'age' => 25];

        // Setup outside timing loop
        $v = new Validator($data);
        $v->rule('required', ['email', 'age'])
          ->rule('email', 'email')
          ->rule('integer', 'age');

        // Warmup
        $this->warmup($v);

        // Measure only validation execution
        $memStart = memory_get_usage();
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $v->validate();
        }
        $end = microtime(true);
        $memEnd = memory_get_usage();

        $this->printResults('Simple Validation (3 rules)', $start, $end, $memStart, $memEnd, self::ITERATIONS);
    }

    private function benchmarkComplexValidation(): void
    {
        $data = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'age' => 30,
            'website' => 'https://example.com',
        ];

        // Setup outside timing loop
        $v = new Validator($data);
        $v->rule('required', ['username', 'email', 'password'])
          ->rule('lengthBetween', 'username', 3, 20)
          ->rule('email', 'email')
          ->rule('lengthMin', 'password', 8)
          ->rule('regex', 'password', '/^(?=.*[A-Z])(?=.*[0-9])/')
          ->rule('integer', 'age')
          ->rule('min', 'age', 18)
          ->rule('url', 'website');

        // Warmup
        $this->warmup($v);

        // Measure only validation execution
        $memStart = memory_get_usage();
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $v->validate();
        }
        $end = microtime(true);
        $memEnd = memory_get_usage();

        $this->printResults('Complex Validation (8 rules)', $start, $end, $memStart, $memEnd, self::ITERATIONS);
    }

    private function benchmarkNestedValidation(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'email' => 'test@example.com',
                    'age' => 25,
                ],
            ],
        ];

        // Setup outside timing loop
        $v = new Validator($data);
        $v->rule('required', 'user.profile.email')
          ->rule('email', 'user.profile.email')
          ->rule('integer', 'user.profile.age');

        // Warmup
        $this->warmup($v);

        // Measure only validation execution
        $memStart = memory_get_usage();
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $v->validate();
        }
        $end = microtime(true);
        $memEnd = memory_get_usage();

        $this->printResults('Nested Array Validation (3 rules)', $start, $end, $memStart, $memEnd, self::ITERATIONS);
    }

    private function benchmarkLargeDataset(): void
    {
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data["field_$i"] = "value_$i";
        }

        // Setup outside timing loop
        $v = new Validator($data);
        foreach (array_keys($data) as $field) {
            $v->rule('required', $field);
        }

        // Warmup
        $this->warmup($v, 100);

        // Measure only validation execution
        $memStart = memory_get_usage();
        $start = microtime(true);
        for ($i = 0; $i < self::LARGE_DATASET_ITERATIONS; $i++) {
            $v->validate();
        }
        $end = microtime(true);
        $memEnd = memory_get_usage();

        $this->printResults('Large Dataset (100 fields)', $start, $end, $memStart, $memEnd, self::LARGE_DATASET_ITERATIONS);
    }

    /**
     * Benchmark instance creation overhead
     */
    private function benchmarkInstanceCreation(): void
    {
        $data = ['email' => 'test@example.com', 'age' => 25];

        // Warmup
        for ($i = 0; $i < self::WARMUP_ITERATIONS; $i++) {
            new Validator($data);
        }

        // Measure only instance creation
        $memStart = memory_get_usage();
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            new Validator($data);
        }
        $end = microtime(true);
        $memEnd = memory_get_usage();

        $this->printResults('Instance Creation Overhead', $start, $end, $memStart, $memEnd, self::ITERATIONS);
    }

    /**
     * Benchmark rule setup overhead
     */
    private function benchmarkRuleSetup(): void
    {
        $data = ['email' => 'test@example.com', 'age' => 25];

        // Warmup
        for ($i = 0; $i < self::WARMUP_ITERATIONS; $i++) {
            $v = new Validator($data);
            $v->rule('required', ['email', 'age'])
              ->rule('email', 'email')
              ->rule('integer', 'age');
        }

        // Measure rule setup (excluding instantiation)
        $memStart = memory_get_usage();
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $v = new Validator($data);
            $v->rule('required', ['email', 'age'])
              ->rule('email', 'email')
              ->rule('integer', 'age');
        }
        $end = microtime(true);
        $memEnd = memory_get_usage();

        $this->printResults('Rule Setup (3 rules)', $start, $end, $memStart, $memEnd, self::ITERATIONS);
    }

    /**
     * Benchmark end-to-end (instance + setup + validation)
     */
    private function benchmarkEndToEnd(): void
    {
        $data = ['email' => 'test@example.com', 'age' => 25];

        // Warmup
        for ($i = 0; $i < self::WARMUP_ITERATIONS; $i++) {
            $v = new Validator($data);
            $v->rule('required', ['email', 'age'])
              ->rule('email', 'email')
              ->rule('integer', 'age');
            $v->validate();
        }

        // Measure everything (like original benchmark)
        $memStart = memory_get_usage();
        $start = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $v = new Validator($data);
            $v->rule('required', ['email', 'age'])
              ->rule('email', 'email')
              ->rule('integer', 'age');
            $v->validate();
        }
        $end = microtime(true);
        $memEnd = memory_get_usage();

        $this->printResults('End-to-End (create + setup + validate)', $start, $end, $memStart, $memEnd, self::ITERATIONS);
    }

    /**
     * Warmup phase to ensure opcache/JIT is active
     */
    private function warmup(Validator $v, int $iterations = self::WARMUP_ITERATIONS): void
    {
        for ($i = 0; $i < $iterations; $i++) {
            $v->validate();
        }
    }

    /**
     * Print benchmark results with detailed metrics
     */
    private function printResults(string $name, float $start, float $end, int $memStart, int $memEnd, int $iterations): void
    {
        $time = $end - $start;
        $perIteration = ($time / $iterations) * 1000;
        $throughput = $iterations / $time;
        $memUsed = $memEnd - $memStart;
        $memPerIteration = $memUsed / $iterations;

        printf("📊 %s\n", $name);
        printf("   Total time:      %.4fs\n", $time);
        printf("   Per iteration:   %.4fms\n", $perIteration);
        printf("   Throughput:      %s validations/sec\n", number_format($throughput, 0));
        printf("   Memory per iter: %s\n", $this->formatBytes($memPerIteration));
        printf("   Total memory:    %s\n\n", $this->formatBytes($memUsed));
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(float $bytes): string
    {
        if ($bytes < 1024) {
            return sprintf('%.0f B', $bytes);
        }
        if ($bytes < 1048576) {
            return sprintf('%.2f KB', $bytes / 1024);
        }
        return sprintf('%.2f MB', $bytes / 1048576);
    }
}

// Run benchmark
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $benchmark = new ValidationBenchmark();
    $benchmark->run();
}
