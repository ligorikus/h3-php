<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\H3;
use H3\Type\Cell;
use H3\ValueObject\LatLng;
use H3\Internal\BaseCells;

class CellTypeTest
{
    private const VALID_CELL = 0x850dab63fffffff;
    private const PENTAGON_CELL = 0x821c07fffffffff;

    public function testBaseCellNumber(): bool
    {
        $cell = Cell::fromIndex(self::VALID_CELL);
        $baseCell = $cell->baseCellNumber();
        
        if ($baseCell < 0 || $baseCell >= 122) {
            echo "FAIL: Base cell {$baseCell} out of range\n";
            return false;
        }
        echo "PASS: testBaseCellNumber\n";
        return true;
    }

    public function testPentagonBaseCellNumber(): bool
    {
        // Test known pentagon base cells using IS_PENTAGON constant
        foreach (BaseCells::IS_PENTAGON as $baseCellNum => $_) {
            $cell = Cell::fromIndex(0x8000000000000000 | ($baseCellNum << 4) | 1);
            if (!$cell->isPentagon()) {
                echo "FAIL: Base cell {$baseCellNum} should be pentagon\n";
                return false;
            }
        }
        echo "PASS: testPentagonBaseCellNumber\n";
        return true;
    }

    public function testResolution(): bool
    {
        $cell = Cell::fromIndex(self::VALID_CELL);
        if ($cell->resolution() !== 5) {
            echo "FAIL: Expected resolution 5\n";
            return false;
        }

        $latLng = LatLng::fromDegrees(0, 0);
        $res0Cell = H3::latLngToCell($latLng, 0);
        if ($res0Cell->resolution() !== 0) {
            echo "FAIL: Expected resolution 0\n";
            return false;
        }
        echo "PASS: testResolution\n";
        return true;
    }

    public function testIsPentagon(): bool
    {
        $latLng = LatLng::fromDegrees(0, 0);  // Atlantic - pentagon base 58
        $pentagon = H3::latLngToCell($latLng, 0);
        
        $regularLatLng = LatLng::fromDegrees(0, 1);  // Regular cell
        $regular = H3::latLngToCell($regularLatLng, 0);
        
        if (!$pentagon->isPentagon()) {
            echo "FAIL: Atlantic should be pentagon\n";
            return false;
        }
        
        if ($regular->isPentagon()) {
            echo "FAIL: Base 1 should not be pentagon\n";
            return false;
        }
        
        echo "PASS: testIsPentagon\n";
        return true;
    }

    public function testIsValid(): bool
    {
        $validCell = Cell::fromIndex(self::VALID_CELL);
        if (!Cell::isValidCell($validCell->index())) {
            echo "FAIL: Valid cell should be valid\n";
            return false;
        }

        $invalidCell = Cell::fromIndex(0);
        if ($invalidCell->isValid()) {
            echo "FAIL: Zero should not be valid\n";
            return false;
        }
        echo "PASS: testIsValid\n";
        return true;
    }

    public function testFromString(): bool
    {
        $cell = Cell::fromString('850dab63fffffff');
        if ($cell === null || $cell->index() !== self::VALID_CELL) {
            echo "FAIL: fromString failed\n";
            return false;
        }

        $invalidCell = Cell::fromString('invalid');
        if ($invalidCell !== null) {
            echo "FAIL: Invalid string should return null\n";
            return false;
        }
        echo "PASS: testFromString\n";
        return true;
    }

    public function testIndexRounding(): bool
    {
        $original = 0x850dab63fffffff;
        $cell = Cell::fromIndex($original);
        
        if ($cell->index() !== $original) {
            echo "FAIL: Index should round-trip\n";
            return false;
        }
        echo "PASS: testIndexRounding\n";
        return true;
    }
}