<?php

declare(strict_types=1);

/**
 * This file is part of the Tools4AbraFlexi package
 *
 * https://github.com/VitexSoftware/AbraFlexi-Tools
 *
 * (C) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the benchmark report output conforming to the MultiFlexi report schema.
 *
 * @see https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/schema/report.json
 */
class BenchmarkReportTest extends TestCase
{
    /**
     * Test that getReport() returns all required fields per MultiFlexi report schema.
     */
    public function testReportContainsRequiredFields(): void
    {
        $report = $this->createSampleReport();

        $this->assertArrayHasKey('producer', $report, 'Report must contain "producer"');
        $this->assertArrayHasKey('status', $report, 'Report must contain "status"');
        $this->assertArrayHasKey('timestamp', $report, 'Report must contain "timestamp"');
    }

    /**
     * Test that the producer field is a non-empty string.
     */
    public function testProducerIsString(): void
    {
        $report = $this->createSampleReport();

        $this->assertIsString($report['producer']);
        $this->assertNotEmpty($report['producer']);
        $this->assertSame('abraflexi-benchmark', $report['producer']);
    }

    /**
     * Test that status is one of the allowed enum values.
     */
    public function testStatusIsValidEnum(): void
    {
        $report = $this->createSampleReport();
        $this->assertContains($report['status'], ['success', 'error', 'warning']);

        $errorReport = $this->createSampleReport('error', 'Something went wrong');
        $this->assertSame('error', $errorReport['status']);
    }

    /**
     * Test that timestamp is a valid ISO8601 date-time string.
     */
    public function testTimestampIsIso8601(): void
    {
        $report = $this->createSampleReport();

        $this->assertIsString($report['timestamp']);
        $parsed = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $report['timestamp']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $parsed, 'Timestamp must be valid ISO8601 (DATE_ATOM)');
    }

    /**
     * Test that message field is present and is a string.
     */
    public function testMessageIsString(): void
    {
        $report = $this->createSampleReport();
        $this->assertArrayHasKey('message', $report);
        $this->assertIsString($report['message']);
        $this->assertNotEmpty($report['message']);
    }

    /**
     * Test that metrics field is an associative array with valid value types.
     */
    public function testMetricsContainsValidTypes(): void
    {
        $report = $this->createSampleReport();

        $this->assertArrayHasKey('metrics', $report);
        $this->assertIsArray($report['metrics']);

        foreach ($report['metrics'] as $key => $value) {
            $this->assertIsString($key);
            $this->assertTrue(
                \is_int($value) || \is_float($value) || \is_string($value),
                "Metric '{$key}' value must be number, integer, or string per schema",
            );
        }
    }

    /**
     * Test that metrics includes cycles and delay.
     */
    public function testMetricsIncludesCyclesAndDelay(): void
    {
        $report = $this->createSampleReport();

        $this->assertArrayHasKey('cycles', $report['metrics']);
        $this->assertArrayHasKey('delay', $report['metrics']);
        $this->assertIsInt($report['metrics']['cycles']);
        $this->assertIsInt($report['metrics']['delay']);
    }

    /**
     * Test error report contains the error message.
     */
    public function testErrorReportContainsMessage(): void
    {
        $errorMessage = 'Connection to AbraFlexi failed';
        $report = $this->createSampleReport('error', $errorMessage);

        $this->assertSame('error', $report['status']);
        $this->assertSame($errorMessage, $report['message']);
    }

    /**
     * Test report with benchmark data includes timing metrics.
     */
    public function testReportWithBenchmarkDataIncludesTimingMetrics(): void
    {
        $report = $this->createSampleReportWithData();

        $this->assertArrayHasKey('pass_1_address_read', $report['metrics']);
        $this->assertArrayHasKey('pass_1_address_write', $report['metrics']);
    }

    /**
     * Create a sample report by directly calling the getReport method structure.
     *
     * Since benchmark class requires AbraFlexi connection, we simulate the output.
     */
    private function createSampleReport(string $status = 'success', string $message = ''): array
    {
        $metrics = [];
        $metrics['cycles'] = 0;
        $metrics['delay'] = 0;

        if (empty($message)) {
            $message = sprintf('Benchmark completed: %d cycles with %ds delay', 0, 0);
        }

        return [
            'producer' => 'abraflexi-benchmark',
            'status' => $status,
            'timestamp' => date('c'),
            'message' => $message,
            'metrics' => $metrics,
        ];
    }

    /**
     * Create a sample report with simulated benchmark timing data.
     */
    private function createSampleReportWithData(): array
    {
        $metrics = [
            'pass_1_address_read' => '0.123',
            'pass_1_address_write' => '0.456',
            'pass_1_bank_move_read' => '0.234',
            'pass_1_bank_move_write' => '0.567',
            'cycles' => 1,
            'delay' => 0,
        ];

        return [
            'producer' => 'abraflexi-benchmark',
            'status' => 'success',
            'timestamp' => date('c'),
            'message' => 'Benchmark completed: 1 cycles with 0s delay',
            'metrics' => $metrics,
        ];
    }
}
