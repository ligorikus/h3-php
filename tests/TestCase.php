<?php

declare(strict_types=1);

namespace H3\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected function assertEqual(int $expected, int $actual, string $message = ''): void
    {
        $this->assertSame($expected, $actual, $message ?: "Expected {$expected}, got {$actual}");
    }

    protected function assertEqualFloat(float $expected, float $actual, float $epsilon = EPSILON, string $message = ''): void
    {
        $this->assertEqualsWithDelta($expected, $actual, $epsilon, $message ?: "Expected {$expected}, got {$actual}");
    }

    protected function assertEqualLatLng(array $expected, array $actual, string $message = ''): void
    {
        $this->assertEqualsWithDelta($expected[0], $actual[0], EPSILON, $message ?: 'Lat mismatch');
        $this->assertEqualsWithDelta($expected[1], $actual[1], EPSILON, $message ?: 'Lng mismatch');
    }

    protected function assertEqualLatLngs(array $expected, array $actual, string $message = ''): void
    {
        $this->assertCount(count($expected), $actual, $message ?: 'Count mismatch');
        foreach ($expected as $i => $exp) {
            $this->assertEqualLatLng($exp, $actual[$i], $message ?: "Index {$i} mismatch");
        }
    }

    protected function assertEqualCells(array $expected, array $actual, string $message = ''): void
    {
        $this->assertCount(count($expected), $actual, $message ?: 'Count mismatch');
        foreach ($expected as $i => $exp) {
            $this->assertSame($exp, $actual[$i], $message ?: "Index {$i} mismatch");
        }
    }

    protected function assertNoError($result, string $message = ''): void
    {
        $this->assertNotNull($result, $message ?: 'Result should not be null');
    }

    protected function assertErr($result, string $message = ''): void
    {
        $this->assertNull($result, $message ?: 'Result should be null for error case');
    }
}