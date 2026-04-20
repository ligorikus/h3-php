<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\H3;
use H3\Type\Cell;
use H3\ValueObject\LatLng;
use H3\Internal\BaseCells;
use PHPUnit\Framework\TestCase;

class CellTypeTest extends TestCase
{
    private const VALID_CELL = 0x850dab63fffffff;

    public function testBaseCellNumber(): void
    {
        $cell = Cell::fromIndex(self::VALID_CELL);
        $baseCell = $cell->baseCellNumber();

        $this->assertGreaterThanOrEqual(0, $baseCell);
        $this->assertLessThan(122, $baseCell);
    }

    public function testPentagonBaseCellNumber(): void
    {
        foreach (BaseCells::IS_PENTAGON as $baseCellNum => $_) {
            $index = (1 << 59) | ($baseCellNum << 45) | 1;
            $cell = Cell::fromIndex($index);
            $this->assertTrue($cell->isPentagon(), "Base cell {$baseCellNum} should be pentagon");
        }
    }

    public function testResolution(): void
    {
        $cell = Cell::fromIndex(self::VALID_CELL);
        $this->assertSame(5, $cell->resolution());

        $latLng = LatLng::fromDegrees(0, 0);
        $res0Cell = H3::latLngToCell($latLng, 0);
        $this->assertNotNull($res0Cell);
        $this->assertSame(0, $res0Cell->resolution());
    }

    public function testIsPentagon(): void
    {
        $latLng = LatLng::fromDegrees(0, 0);
        $pentagon = H3::latLngToCell($latLng, 0);
        $this->assertNotNull($pentagon);
        $this->assertTrue($pentagon->isPentagon(), 'Atlantic should be pentagon');

        $regularLatLng = LatLng::fromDegrees(45, 45);
        $regular = H3::latLngToCell($regularLatLng, 0);
        $this->assertNotNull($regular);
        $this->assertFalse($regular->isPentagon(), 'Coordinates (45, 45) should not be pentagon');
    }

    public function testIsValid(): void
    {
        $validCell = Cell::fromIndex(self::VALID_CELL);
        $this->assertTrue(Cell::isValidCell($validCell->index()));

        $invalidCell = Cell::fromIndex(0);
        $this->assertFalse($invalidCell->isValid());
    }

    public function testFromString(): void
    {
        $cell = Cell::fromString('850dab63fffffff');
        $this->assertNotNull($cell);
        $this->assertSame(self::VALID_CELL, $cell->index());

        $invalidCell = Cell::fromString('invalid');
        $this->assertNull($invalidCell);
    }

    public function testIndexRounding(): void
    {
        $original = 0x850dab63fffffff;
        $cell = Cell::fromIndex($original);

        $this->assertSame($original, $cell->index());
    }
}
