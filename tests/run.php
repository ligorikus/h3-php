#!/usr/bin/env php
<?php
/**
 * Simple test runner for php-h3
 * Run: php tests/run.php
 */

define('EPSILON', 1e-4);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/H3APITest.php';
require_once __DIR__ . '/CellTypeTest.php';

$h3 = new \H3\Tests\H3APITest();
$cell = new \H3\Tests\CellTypeTest();

$tests = [
    [$h3, 'testLatLngToCellRes0'],
    [$h3, 'testRes0Cells'],
    [$h3, 'testPentagons'],
    [$h3, 'testGridDiskRes0'],
    [$h3, 'testGetIcosahedronFacesRes0'],
    [$h3, 'testDirectedEdgesRes0'],
    [$cell, 'testResolution'],
    [$cell, 'testIsValid'],
    [$cell, 'testIndexRounding'],
];

$passed = 0;
$failed = 0;

foreach ($tests as [$test, $method]) {
    try {
        if ($test->$method()) {
            $passed++;
        } else {
            $failed++;
        }
    } catch (Exception $e) {
        echo 'ERROR: ' . get_class($test) . '::' . $method . ' - ' . $e->getMessage() . PHP_EOL;
        $failed++;
    }
}

echo PHP_EOL . "Results: {$passed} passed, {$failed} failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);