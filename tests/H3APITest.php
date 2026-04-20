<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\H3;
use H3\ValueObject\LatLng;
use H3\Type\Cell;
use PHPUnit\Framework\TestCase;

class H3APITest extends TestCase
{
    public function testLatLngToCellRes0(): void
    {
        $testCases = [
            ['name' => 'Atlantic', 'lat' => 0.0, 'lng' => 0.0, 'expectedBase' => 58],
            ['name' => 'North Pole', 'lat' => 90.0, 'lng' => 0.0, 'expectedBase' => 0],
            ['name' => 'Pacific', 'lat' => 0.0, 'lng' => 180.0, 'expectedBase' => 63],
            ['name' => 'SF', 'lat' => 37.775938728915946, 'lng' => -122.41795063018799, 'expectedBase' => 20],
            ['name' => 'South Pole', 'lat' => -90.0, 'lng' => 0.0, 'expectedBase' => 121],
            ['name' => 'Greenwich', 'lat' => 51.48, 'lng' => 0.0, 'expectedBase' => 12],
        ];

        foreach ($testCases as $tc) {
            $latLng = LatLng::fromDegrees($tc['lat'], $tc['lng']);
            $cell = H3::latLngToCell($latLng, 0);
            $this->assertNotNull($cell);
            $this->assertSame($tc['expectedBase'], $cell->baseCellNumber(), $tc['name']);
        }
    }

    public function testRes0Cells(): void
    {
        $cells = H3::res0Cells();
        $this->assertCount(122, $cells);
    }

    public function testNumCells(): void
    {
        $this->assertSame(122, H3::numCells(0));
        $this->assertSame(842, H3::numCells(1));
    }

    public function testPentagons(): void
    {
        $pentagons = H3::pentagons(0);
        $this->assertCount(12, $pentagons);
    }

    public function testGridDiskRes0(): void
    {
        $latLng = LatLng::fromDegrees(0, 0);
        $origin = H3::latLngToCell($latLng, 0);
        $this->assertNotNull($origin);

        $disk0 = H3::gridDisk($origin, 0);
        $this->assertCount(1, $disk0);

        $disk1 = H3::gridDisk($origin, 1);
        $this->assertGreaterThan(1, count($disk1));
    }

    public function testGridDistanceRes0(): void
    {
        $latLng = LatLng::fromDegrees(37.7749, -122.4194);
        $cell = H3::latLngToCell($latLng, 0);
        $this->assertNotNull($cell);

        $neighbors = H3::gridDisk($cell, 1);
        $this->assertGreaterThanOrEqual(2, count($neighbors));

        $distance = H3::gridDistance($neighbors[0], $neighbors[1]);
        $this->assertSame(1, $distance);

        $same = H3::gridDistance($cell, $cell);
        $this->assertSame(0, $same);
    }

    public function testGetIcosahedronFacesRes0(): void
    {
        $latLng = LatLng::fromDegrees(0, 0);
        $cell = H3::latLngToCell($latLng, 0);
        $this->assertNotNull($cell);

        $faces = H3::icosahedronFaces($cell);
        $this->assertNotEmpty($faces);

        $baseCell = $cell->baseCellNumber();
        if ($baseCell === 58) {
            $this->assertContains(13, $faces, 'Face 13 should contain base cell 58');
        }
    }

    public function testDirectedEdgesRes0(): void
    {
        $latLng = LatLng::fromDegrees(0, 0);
        $cell = H3::latLngToCell($latLng, 0);
        $this->assertNotNull($cell);

        $edges = H3::originToDirectedEdges($cell);
        $expectedEdges = $cell->isPentagon() ? 5 : 6;

        $this->assertCount($expectedEdges, $edges);
    }
}
