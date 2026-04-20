<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\H3;
use H3\ValueObject\LatLng;
use H3\Type\Cell;

class H3APITest
{
    public function testLatLngToCellRes0(): bool
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
            $actual = $cell->baseCellNumber();
            if ($actual !== $tc['expectedBase']) {
                echo "FAIL: {$tc['name']} expected {$tc['expectedBase']}, got {$actual}\n";
                return false;
            }
        }
        echo "PASS: testLatLngToCellRes0\n";
        return true;
    }

    public function testRes0Cells(): bool
    {
        $cells = H3::res0Cells();
        if (count($cells) !== 122) {
            echo "FAIL: Expected 122 cells, got " . count($cells) . "\n";
            return false;
        }
        echo "PASS: testRes0Cells\n";
        return true;
    }

    public function testNumCells(): bool
    {
        $numRes0 = H3::numCells(0);
        if ($numRes0 !== 122) {
            echo "FAIL: numCells(0) expected 122, got {$numRes0}\n";
            return false;
        }

        $numRes1 = H3::numCells(1);
        if ($numRes1 !== 122 * 7) {
            echo "FAIL: numCells(1) expected " . (122 * 7) . ", got {$numRes1}\n";
            return false;
        }
        echo "PASS: testNumCells\n";
        return true;
    }

    public function testPentagons(): bool
    {
        $pentagons = H3::pentagons(0);
        if (count($pentagons) !== 12) {
            echo "FAIL: Expected 12 pentagons, got " . count($pentagons) . "\n";
            return false;
        }
        echo "PASS: testPentagons\n";
        return true;
    }

    public function testGridDiskRes0(): bool
    {
        $latLng = LatLng::fromDegrees(0, 0);
        $origin = H3::latLngToCell($latLng, 0);
        
        $disk0 = H3::gridDisk($origin, 0);
        if (count($disk0) !== 1) {
            echo "FAIL: Disk 0 should have 1 cell\n";
            return false;
        }

        $disk1 = H3::gridDisk($origin, 1);
        if (count($disk1) <= 1) {
            echo "FAIL: Disk 1 should have more than 1 cell\n";
            return false;
        }
        echo "PASS: testGridDiskRes0\n";
        return true;
    }

    public function testGridDistanceRes0(): bool
    {
        $cell = Cell::fromIndex((int)0x8000000000000001);
        $neighbors = H3::gridDisk($cell, 1);
        
        if (count($neighbors) >= 2) {
            $distance = H3::gridDistance($neighbors[0], $neighbors[1]);
            if ($distance !== 1) {
                echo "FAIL: Grid distance should be 1\n";
                return false;
            }
        }

        $same = H3::gridDistance($cell, $cell);
        if ($same !== 0) {
            echo "FAIL: Grid distance to self should be 0\n";
            return false;
        }
        echo "PASS: testGridDistanceRes0\n";
        return true;
    }

    public function testGetIcosahedronFacesRes0(): bool
    {
        $latLng = LatLng::fromDegrees(0, 0);
        $cell = H3::latLngToCell($latLng, 0);
        
        $faces = H3::icosahedronFaces($cell);
        if (empty($faces)) {
            echo "FAIL: Faces should not be empty\n";
            return false;
        }
        
        // Base cell 58 should be in face 13
        $baseCell = $cell->baseCellNumber();
        if ($baseCell === 58 && !in_array(13, $faces)) {
            echo "FAIL: Face 13 should contain base cell 58\n";
            return false;
        }
        echo "PASS: testGetIcosahedronFacesRes0\n";
        return true;
    }

    public function testDirectedEdgesRes0(): bool
    {
        $latLng = LatLng::fromDegrees(0, 0);
        $cell = H3::latLngToCell($latLng, 0);
        
        $edges = H3::originToDirectedEdges($cell);
        $expectedEdges = $cell->isPentagon() ? 5 : 6;
        
        if (count($edges) !== $expectedEdges) {
            echo "FAIL: Expected {$expectedEdges} edges, got " . count($edges) . "\n";
            return false;
        }
        echo "PASS: testDirectedEdgesRes0\n";
        return true;
    }
}