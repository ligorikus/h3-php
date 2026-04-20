<?php

declare(strict_types=1);

namespace H3;

use H3\InternalConstants as C;
use H3\Internal\MathUtils;
use H3\Internal\FaceProjection;
use H3\Internal\BaseCells;
use H3\Type\Cell;
use H3\Type\Vertex;
use H3\Type\DirectedEdge;
use H3\ValueObject\LatLng;
use H3\ValueObject\CellBoundary;
use H3\ValueObject\GeoPolygon;
use H3\ValueObject\GeoLoop;
use H3\ValueObject\CoordIJ;

final class H3
{
    public const MAX_RESOLUTION = 15;
    public const NUM_BASE_CELLS = 122;
    public const NUM_ICOSA_FACES = 20;
    public const NUM_PENTAGONS = 12;
    public const INVALID_H3_INDEX = 0;
    public const MAX_CELL_BNDRY_VERTS = 10;
    public const DEGS_TO_RADS = 0.01745329251994329576;
    public const RADS_TO_DEGS = 57.29577951308232087679;

    private const UNIT_VECS = [
        0 => [0, 0, 0],
        1 => [0, 0, 1],
        2 => [0, 1, 0],
        3 => [0, 1, 1],
        4 => [1, 0, 0],
        5 => [1, 0, 1],
        6 => [1, 1, 0],
    ];

    private const PENTAGON_BASES = [4, 14, 24, 38, 49, 58, 63, 72, 83, 97, 107, 117];

    public static function latLngToCell(LatLng $latLng, int $resolution): ?Cell
    {
        if ($resolution < 0 || $resolution > self::MAX_RESOLUTION) {
            return null;
        }

        if (!$latLng->isValid()) {
            return null;
        }

        $hex2d = self::geoToHex2d($latLng, $resolution);
        $face = $hex2d['face'];
        $x = $hex2d['x'];
        $y = $hex2d['y'];

        $coord = self::vec2dToIJK($x, $y);

        for ($r = 0; $r < $resolution; $r++) {
            $coord = self::upAp7($coord, $r + 1);
        }

        $faceIJK = ['face' => $face, 'i' => $coord[0], 'j' => $coord[1], 'k' => $coord[2]];
        $baseCell = BaseCells::faceIjkToBaseCell($face, $coord[0], $coord[1], $coord[2]);

        if ($baseCell === 127) {
            $faceIJK = self::findValidFaceIJK($latLng, $face, $resolution, $x, $y);
            if ($faceIJK === null) {
                return null;
            }
        }

        $h3Index = self::faceIJKToH3Index($faceIJK, $resolution);

        return Cell::fromIndex($h3Index);
    }

    private static function findValidFaceIJK(LatLng $g, int $startFace, int $res, float $origX, float $origY): ?array
    {
        $adjacentFaces = FaceProjection::getAdjacentFaces($startFace);
        array_unshift($adjacentFaces, $startFace);

        $gVec = [
            cos($g->latRadians()) * cos($g->lngRadians()),
            cos($g->latRadians()) * sin($g->lngRadians()),
            sin($g->latRadians()),
        ];

        foreach ($adjacentFaces as $face) {
            $faceVec = FaceProjection::getFaceCenterPoint($face);
            $sqd = 2 * (1 - ($faceVec[0] * $gVec[0] + $faceVec[1] * $gVec[1] + $faceVec[2] * $gVec[2]));

            $hex2d = self::geoToHex2dOnFace($g, $face, $res, $sqd);
            $x = $hex2d['x'];
            $y = $hex2d['y'];

            $coord = self::vec2dToIJK($x, $y);

            for ($r = 0; $r < $res; $r++) {
                $coord = self::upAp7($coord, $r + 1);
            }

            $baseCell = BaseCells::faceIjkToBaseCell($face, $coord[0], $coord[1], $coord[2]);
            if ($baseCell !== 127) {
                return ['face' => $face, 'i' => $coord[0], 'j' => $coord[1], 'k' => $coord[2]];
            }
        }

        return null;
    }

    private static function geoToHex2dOnFace(LatLng $g, int $face, int $res, float $sqd): array
    {
        $faceGeo = C::FACE_CENTER_GEO[$face];
        $faceLat = $faceGeo[0];
        $faceLng = $faceGeo[1];

        $r = acos(1 - $sqd * 0.5);

        if ($r < C::EPSILON) {
            return ['face' => $face, 'x' => 0.0, 'y' => 0.0];
        }

        $faceLatLng = LatLng::fromRadians($faceLat, $faceLng);
        $azimuth = MathUtils::geoAzimuthRads($faceLatLng, $g);
        $theta = MathUtils::posAngleRads(C::FACE_AXES_AZ_RADS_CII[$face][0] - MathUtils::posAngleRads($azimuth));

        if (MathUtils::isResClassIII($res)) {
            $theta = MathUtils::posAngleRads($theta - C::M_AP7_ROT_RADS);
        }

        $r = tan($r);
        $r *= C::INV_RES0_U_GNOMONIC;
        for ($i = 0; $i < $res; $i++) {
            $r *= C::M_SQRT7;
        }

        return [
            'face' => $face,
            'x' => $r * cos($theta),
            'y' => $r * sin($theta),
        ];
    }

    public static function cellToLatLng(Cell $cell): LatLng
    {
        $index = $cell->index();
        $resolution = $cell->resolution();
        $baseCell = $cell->baseCellNumber();

        $baseData = BaseCells::getBaseCellData($baseCell);
        if ($baseData === null) {
            return LatLng::fromDegrees(0, 0);
        }

        $face = $baseData['face'];
        $i = $baseData['i'];
        $j = $baseData['j'];
        $k = $baseData['k'];

        $faceIJK = [
            'face' => $face,
            'i' => $i,
            'j' => $j,
            'k' => $k,
        ];

        $faceIJK = self::h3IndexToFaceIJK($index, $faceIJK, $resolution);

        $coord = self::downAp7([$faceIJK['i'], $faceIJK['j'], $faceIJK['k']], $resolution);

        $vec = self::ijkToVec2d($coord[0], $coord[1], $coord[2]);

        return self::hex2dToGeo($vec['x'], $vec['y'], $faceIJK['face'], $resolution);
    }

    public static function cellToBoundary(Cell $cell): CellBoundary
    {
        $center = self::cellToLatLng($cell);
        $resolution = $cell->resolution();

        $edgeLen = self::hexagonEdgeLengthAvgKm($resolution) ?? 0.001;
        $edgeLenDeg = $edgeLen / 111.0;

        $vertices = [];
        $numVerts = $cell->isPentagon() ? 5 : 6;

        $lat = $center->lat();
        $lng = $center->lng();

        for ($i = 0; $i < $numVerts; $i++) {
            $angle = C::PI * 2 * $i / $numVerts - C::PI / 2;
            $vLat = $lat + $edgeLenDeg * cos($angle) * 1.5;
            $vLng = $lng + $edgeLenDeg * sin($angle);
            $vertices[] = LatLng::fromDegrees($vLat, $vLng);
        }

        return new CellBoundary($vertices);
    }

    public static function gridDisk(Cell $origin, int $k): array
    {
        if ($k < 0) {
            return [];
        }

        $result = [Cell::fromIndex($origin->index())];
        $visited = [$origin->index() => true];

        $queue = [[$origin, 0]];
        $head = 0;

        while ($head < count($queue)) {
            [$current, $dist] = $queue[$head++];

            if ($dist >= $k) {
                continue;
            }

            $neighbors = self::gridDiskNeighbors($current);
            foreach ($neighbors as $neighbor) {
                $idx = $neighbor->index();
                if ($idx !== 0 && !isset($visited[$idx])) {
                    $visited[$idx] = true;
                    $result[] = $neighbor;
                    $queue[] = [$neighbor, $dist + 1];
                }
            }
        }

        return $result;
    }

    public static function gridRing(Cell $origin, int $k): array
    {
        if ($k < 0) {
            return [];
        }

        if ($k === 0) {
            return [$origin];
        }

        $visited = [$origin->index() => true];
        $ring = [];
        $currentRing = [$origin];

        for ($ringDist = 0; $ringDist < $k; $ringDist++) {
            $nextRing = [];

            foreach ($currentRing as $cell) {
                $neighbors = self::gridDiskNeighbors($cell);

                foreach ($neighbors as $n) {
                    $idx = $n->index();

                    if ($idx !== 0 && !isset($visited[$idx])) {
                        $visited[$idx] = true;
                        $nextRing[] = $n;

                        if ($ringDist === $k - 1) {
                            $ring[] = $n;
                        }
                    }
                }
            }

            if (empty($nextRing)) {
                break;
            }

            $currentRing = $nextRing;
        }

        return $ring;
    }

    public static function gridDiskDistances(Cell $origin, int $k): array
    {
        if ($k < 0) {
            return [];
        }

        $ringSize = function(int $ring): int {
            if ($ring === 0) {
                return 1;
            }
            return 6 * $ring;
        };

        $maxSize = 3 * $k * ($k + 1) + 1;
        $result = array_fill(0, $k + 1, []);

        $disk = self::gridDisk($origin, $k);

        foreach ($disk as $cell) {
            $dist = self::gridDistance($origin, $cell);
            if ($dist !== null && $dist <= $k) {
                $result[$dist][] = $cell;
            }
        }

        return $result;
    }

    public static function gridDiskDistancesUnsafe(Cell $origin, int $k): array
    {
        if ($k < 0) {
            return [];
        }

        $result = array_fill(0, $k + 1, []);

        $disk = self::gridDisk($origin, $k);

        foreach ($disk as $cell) {
            $dist = self::gridDistance($origin, $cell);
            if ($dist !== null && $dist <= $k) {
                $result[$dist][] = $cell;
            }
        }

        return $result;
    }

    public static function gridDiskDistancesSafe(Cell $origin, int $k): array
    {
        if ($k < 0) {
            return [];
        }

        $result = array_fill(0, $k + 1, []);

        $disk = self::gridDisk($origin, $k);

        foreach ($disk as $cell) {
            $idx = $cell->index();
            if ($idx === 0) {
                continue;
            }

            $baseCell = $cell->baseCellNumber();
            if ($cell->isPentagon()) {
                continue;
            }

            $dist = self::gridDistance($origin, $cell);
            if ($dist !== null && $dist <= $k) {
                $result[$dist][] = $cell;
            }
        }

        return $result;
    }

    public static function gridDisksUnsafe(array $origins, int $k): array
    {
        if (empty($origins)) {
            return [];
        }

        $results = [];
        foreach ($origins as $origin) {
            $results[] = self::gridDisk($origin, $k);
        }

        return $results;
    }

    public static function gridRingUnsafe(Cell $origin, int $k): array
    {
        if ($k < 0) {
            return [];
        }

        if ($k === 0) {
            return [$origin];
        }

        $ring = [];
        $visited = [$origin->index() => true];

        $currentRing = [$origin];
        for ($ringDist = 0; $ringDist < $k; $ringDist++) {
            $nextRing = [];

            foreach ($currentRing as $cell) {
                $neighbors = self::gridDiskNeighbors($cell);
                foreach ($neighbors as $neighbor) {
                    $idx = $neighbor->index();
                    if (!isset($visited[$idx])) {
                        $visited[$idx] = true;
                        $nextRing[] = $neighbor;
                        $ring[] = $neighbor;
                    }
                }
            }

            $currentRing = $nextRing;
        }

        return $ring;
    }

    public static function numCells(int $resolution): int|false
    {
        return MathUtils::numCells($resolution);
    }

    public static function res0Cells(): array
    {
        $cells = [];
        for ($baseCell = 0; $baseCell < self::NUM_BASE_CELLS; $baseCell++) {
            $index = self::makeIndex(0, $baseCell, 7);
            $cells[] = Cell::fromIndex($index);
        }
        return $cells;
    }

    public static function pentagons(int $resolution): array
    {
        return array_map(
            fn(int $base) => Cell::fromIndex(self::makeIndex($resolution, $base, 7)),
            self::PENTAGON_BASES
        );
    }

    public static function greatCircleDistanceRads(LatLng $a, LatLng $b): float
    {
        return MathUtils::greatCircleDistanceRads($a, $b);
    }

    public static function greatCircleDistanceKm(LatLng $a, LatLng $b): float
    {
        return MathUtils::greatCircleDistanceKm($a, $b);
    }

    public static function greatCircleDistanceM(LatLng $a, LatLng $b): float
    {
        return MathUtils::greatCircleDistanceM($a, $b);
    }

    public static function hexagonAreaAvgKm2(int $resolution): float|false
    {
        return MathUtils::hexagonAreaAvgKm2($resolution);
    }

    public static function hexagonAreaAvgM2(int $resolution): float|false
    {
        return MathUtils::hexagonAreaAvgM2($resolution);
    }

    public static function hexagonEdgeLengthAvgKm(int $resolution): float|false
    {
        return MathUtils::hexagonEdgeLengthAvgKm($resolution);
    }

    public static function hexagonEdgeLengthAvgM(int $resolution): float|false
    {
        return MathUtils::hexagonEdgeLengthAvgM($resolution);
    }

    private static function makeIndex(int $res, int $baseCell, int $digit): int
    {
        $index = 0;
        $index |= 1 << 63;  // High bit
        $index |= 1 << 59;  // Mode = 1 (cell)
        $index |= ($res & 0xF) << 52;
        $index |= ($baseCell & 0x7F) << 45;

        // For res > 0, encode digit at position for that resolution
        if ($res > 0) {
            $digitPos = (15 - $res) * 3;
            $index |= ($digit & 0x7) << $digitPos;
        }

        return $index;
    }

    private static function geoToHex2d(LatLng $g, int $res): array
    {
        $closestFaceResult = self::geoToClosestFace($g);
        $face = $closestFaceResult['face'];
        $sqd = $closestFaceResult['sqd'];
        
        $faceGeo = C::FACE_CENTER_GEO[$face];
        $faceLat = $faceGeo[0];
        $faceLng = $faceGeo[1];

        $r = acos(1 - $sqd * 0.5);

        if ($r < C::EPSILON) {
            return ['face' => $face, 'x' => 0.0, 'y' => 0.0];
        }

        $faceLatLng = LatLng::fromRadians($faceLat, $faceLng);
        $azimuth = MathUtils::geoAzimuthRads($faceLatLng, $g);
        $theta = MathUtils::posAngleRads(C::FACE_AXES_AZ_RADS_CII[$face][0] - MathUtils::posAngleRads($azimuth));

        if (MathUtils::isResClassIII($res)) {
            $theta = MathUtils::posAngleRads($theta - C::M_AP7_ROT_RADS);
        }

        $r = tan($r);

        $r *= C::INV_RES0_U_GNOMONIC;
        for ($i = 0; $i < $res; $i++) {
            $r *= C::M_SQRT7;
        }

        return [
            'face' => $face,
            'x' => $r * cos($theta),
            'y' => $r * sin($theta),
        ];
    }

    private static function geoToClosestFace(LatLng $g): array
    {
        $lat = $g->latRadians();
        $lng = $g->lngRadians();

        $x = cos($lat) * cos($lng);
        $y = cos($lat) * sin($lng);
        $z = sin($lat);

        $bestFace = 0;
        $maxDot = -2.0;

        for ($f = 0; $f < 20; $f++) {
            $fc = FaceProjection::getFaceCenterPoint($f);
            $dot = $x * $fc[0] + $y * $fc[1] + $z * $fc[2];
            if ($dot > $maxDot) {
                $maxDot = $dot;
                $bestFace = $f;
            }
        }

        $sqd = 2 * (1 - $maxDot);
        return ['face' => $bestFace, 'sqd' => $sqd];
    }

    private static function hex2dToGeo(float $x, float $y, int $face, int $res): LatLng
    {
        $r = sqrt($x * $x + $y * $y);

        if ($r < C::EPSILON) {
            $fc = C::FACE_CENTER_GEO[$face];
            return LatLng::fromRadians($fc[0], $fc[1]);
        }

        $theta = atan2($y, $x);

        for ($i = 0; $i < $res; $i++) {
            $r *= C::M_RSQRT7;
        }

        $r *= C::RES0_U_GNOMONIC;
        $r = atan($r);

        if (MathUtils::isResClassIII($res)) {
            $theta = MathUtils::posAngleRads($theta + C::M_AP7_ROT_RADS);
        }

        $theta = MathUtils::posAngleRads(C::FACE_AXES_AZ_RADS_CII[$face][0] - $theta);

        $fc = C::FACE_CENTER_GEO[$face];
        $faceLat = $fc[0];
        $faceLng = $fc[1];

        return MathUtils::geoAzDistanceRads(
            LatLng::fromRadians($faceLat, $faceLng),
            $theta,
            $r
        );
    }

    private static function vec2dToIJK(float $x, float $y): array
    {
        $ijk = [0, 0, 0];

        $a1 = abs($x);
        $a2 = abs($y);

        $x2 = $a2 * C::INV_SQRT3;
        $x1 = $a1 + $x2 / 2.0;

        $m1 = (int)$x1;
        $m2 = (int)$x2;

        $r1 = $x1 - $m1;
        $r2 = $x2 - $m2;

        if ($r1 < 0.5) {
            if ($r1 < 1/3) {
                if ($r2 < (1.0 + $r1) / 2.0) {
                    $ijk[0] = $m1;
                    $ijk[1] = $m2;
                } else {
                    $ijk[0] = $m1;
                    $ijk[1] = $m2 + 1;
                }
            } else {
                if ($r2 < (1.0 - $r1)) {
                    $ijk[1] = $m2;
                } else {
                    $ijk[1] = $m2 + 1;
                }

                if ((1.0 - $r1) <= $r2 && $r2 < (2.0 * $r1)) {
                    $ijk[0] = $m1 + 1;
                } else {
                    $ijk[0] = $m1;
                }
            }
        } else {
            if ($r1 < 2/3) {
                if ($r2 < (1.0 - $r1)) {
                    $ijk[1] = $m2;
                } else {
                    $ijk[1] = $m2 + 1;
                }

                if ((2.0 * $r1 - 1.0) < $r2 && $r2 < (1.0 - $r1)) {
                    $ijk[0] = $m1;
                } else {
                    $ijk[0] = $m1 + 1;
                }
            } else {
                if ($r2 < ($r1 / 2.0)) {
                    $ijk[0] = $m1 + 1;
                    $ijk[1] = $m2;
                } else {
                    $ijk[0] = $m1 + 1;
                    $ijk[1] = $m2 + 1;
                }
            }
        }

        if ($x < 0.0) {
            if (($ijk[1] % 2) === 0) {
                $axisi = intdiv($ijk[1], 2);
                $diff = $ijk[0] - $axisi;
                $ijk[0] = $ijk[0] - 2 * $diff;
            } else {
                $axisi = intdiv($ijk[1] + 1, 2);
                $diff = $ijk[0] - $axisi;
                $ijk[0] = $ijk[0] - (2 * $diff + 1);
            }
        }

        if ($y < 0.0) {
            $ijk[0] = $ijk[0] - intdiv(2 * $ijk[1] + 1, 2);
            $ijk[1] = -$ijk[1];
        }

        return self::normalizeIJK($ijk);
    }

    private static function ijkToVec2d(int $i, int $j, int $k): array
    {
        $i2d = $i - $k;
        $j2d = $j - $k;
        $x = $i2d - 0.5 * $j2d;
        $y = $j2d * C::SQRT3_2;
        return ['x' => $x, 'y' => $y];
    }

    private static function normalizeIJK(array $ijk): array
    {
        $i = $ijk[0];
        $j = $ijk[1];
        $k = $ijk[2];

        if ($i < 0) {
            $j -= $i;
            $k -= $i;
            $i = 0;
        }

        if ($j < 0) {
            $i -= $j;
            $k -= $j;
            $j = 0;
        }

        if ($k < 0) {
            $i -= $k;
            $j -= $k;
            $k = 0;
        }

        $min = $i;
        if ($j < $min) $min = $j;
        if ($k < $min) $min = $k;

        if ($min > 0) {
            $i -= $min;
            $j -= $min;
            $k -= $min;
        }

        return [$i, $j, $k];
    }

    private static function normalizeIJKInPlace(int &$i, int &$j, int &$k): void
    {
        if ($i < 0) {
            $j -= $i;
            $k -= $i;
            $i = 0;
        }

        if ($j < 0) {
            $i -= $j;
            $k -= $j;
            $j = 0;
        }

        if ($k < 0) {
            $i -= $k;
            $j -= $k;
            $k = 0;
        }

        $min = $i;
        if ($j < $min) $min = $j;
        if ($k < $min) $min = $k;

        if ($min > 0) {
            $i -= $min;
            $j -= $min;
            $k -= $min;
        }
    }

    private static function upAp7(array $ijk, int $res): array
    {
        $i = $ijk[0];
        $j = $ijk[1];
        $k = $ijk[2];

        if ($res % 2 !== 0) {
            $i = $i + (int)(($j + 2) / 2);
            $j = $j + (int)($k / 2);
            $k = $k + $i + $j - (int)(($i + $j + 1) / 3);
        } else {
            $i = $i + (int)(($j + 1) / 2);
            $j = $j + $k;
            $k = 0;
        }

        return self::normalizeIJK([$i, $j, $k]);
    }

    private static function downAp7(array $ijk, int $res): array
    {
        $i = (int)$ijk[0];
        $j = (int)$ijk[1];
        $k = (int)$ijk[2];

        if ($res % 2 !== 0) {
            $k = $i + $j - intdiv($i + $j + 1, 3);
            $j = $j - intdiv($k + 1, 2);
            $i = $i - intdiv($j + 2, 2);
        } else {
            $j = $j - $k;
            $i = $i - intdiv($j + 1, 2);
            $k = 0;
        }

        return self::normalizeIJK([$i, $j, $k]);
    }

    private static function faceIJKToH3Index(array $faceIJK, int $res): int
    {
        $face = $faceIJK['face'];
        $i = (int)$faceIJK['i'];
        $j = (int)$faceIJK['j'];
        $k = (int)$faceIJK['k'];

        $baseCellRaw = BaseCells::faceIjkToBaseCell($face, $i, $j, $k);
        $baseCell = ($baseCellRaw === 127) ? 0 : $baseCellRaw;

        $digit = self::ijkToDigit($i, $j, $k);

        return self::makeIndex($res, $baseCell, $digit);
    }

    private static function h3IndexToFaceIJK(int $index, array $baseFaceIJK, int $res): array
    {
        $faceIJK = [
            'face' => $baseFaceIJK['face'],
            'i' => (int)$baseFaceIJK['i'],
            'j' => (int)$baseFaceIJK['j'],
            'k' => (int)$baseFaceIJK['k'],
        ];
        $i = &$faceIJK['i'];
        $j = &$faceIJK['j'];
        $k = &$faceIJK['k'];

        for ($r = 1; $r <= $res; $r++) {
            $digit = ($index >> ((15 - $r) * 3)) & 0x7;

            if ($r % 2 !== 0) {
                self::downAp7InPlace($i, $j, $k);
            } else {
                self::downAp7rInPlace($i, $j, $k);
            }

            $vec = self::UNIT_VECS[$digit];
            $i += $vec[0];
            $j += $vec[1];
            $k += $vec[2];
        }

        return $faceIJK;
    }

    private static function downAp7InPlace(&$i, &$j, &$k): void
    {
        $ii = (int)$i;
        $jj = (int)$j;
        $kk = (int)$k;
        
        $newK = $ii + $jj - (int)(($ii + $jj + 1) / 3);
        $newJ = $jj - (int)($newK / 2);
        $newI = $ii - (int)(($newJ + 2) / 2);

        $i = $newI;
        $j = $newJ;
        $k = $newK;

        self::normalizeIJKInPlace($i, $j, $k);
    }

    private static function downAp7rInPlace(&$i, &$j, &$k): void
    {
        $ii = (int)$i;
        $jj = (int)$j;
        $kk = (int)$k;
        
        $newJ = $jj - $kk;
        $newI = $ii - (int)(($newJ + 1) / 2);

        $i = $newI;
        $j = $newJ;
        $k = 0;

        self::normalizeIJKInPlace($i, $j, $k);
    }

    private static function ijkToDigit(int $i, int $j, int $k): int
    {
        return match (true) {
            $i === 0 && $j === 0 && $k === 0 => 0,
            $i > 0 && $j === 0 && $k === 0 => 4,
            $i === 0 && $j > 0 && $k === 0 => 2,
            $i === 0 && $j === 0 && $k > 0 => 1,
            $i === 0 && $j > 0 && $k > 0 => 3,
            $i > 0 && $j === 0 && $k > 0 => 5,
            $i > 0 && $j > 0 && $k === 0 => 6,
            default => 7,
        };
    }

    private static function gridDiskNeighbors(Cell $cell): array
    {
        $index = $cell->index();
        $resolution = $cell->resolution();
        $baseCell = $cell->baseCellNumber();

        $neighbors = [];

        if ($resolution === 0) {
            for ($dir = 0; $dir < 6; $dir++) {
                $neighborBase = BaseCells::getNeighbor($baseCell, $dir);
                if ($neighborBase !== 127) {
                    $neighbors[] = Cell::fromIndex(self::makeIndex(0, $neighborBase, 7));
                }
            }
        } else {
            for ($dir = 1; $dir <= 6; $dir++) {
                $vec = self::UNIT_VECS[$dir];
                $next = self::coordIjkNeighbor($index, $vec[0], $vec[1], $vec[2]);
                if ($next !== 0) {
                    $neighbors[] = Cell::fromIndex($next);
                }
            }
        }

        return $neighbors;
    }

    private static function coordIjkNeighbor(int $index, int $di, int $dj, int $dk): int
    {
        $res = ($index >> 52) & 0xF;
        $baseCell = ($index >> 45) & 0x7F;

        $baseData = BaseCells::getBaseCellData($baseCell);
        if ($baseData === null) {
            return 0;
        }

        $face = $baseData['face'];
        $i = $baseData['i'] + $di;
        $j = $baseData['j'] + $dj;
        $k = $baseData['k'] + $dk;

        $faceIJK = ['face' => $face, 'i' => $i, 'j' => $j, 'k' => $k];

        return self::faceIJKToH3Index($faceIJK, $res);
    }

    public static function polygonToCells(GeoPolygon $polygon, int $resolution): array
    {
        $outerLoop = $polygon->geoLoop->vertices;
        if (empty($outerLoop)) {
            return [];
        }

        $minLat = 90.0;
        $maxLat = -90.0;
        $minLng = 180.0;
        $maxLng = -180.0;

        foreach ($outerLoop as $v) {
            $minLat = min($minLat, $v->lat());
            $maxLat = max($maxLat, $v->lat());
            $minLng = min($minLng, $v->lng());
            $maxLng = max($maxLng, $v->lng());
        }

        $centerLat = ($minLat + $maxLat) / 2;
        $centerLng = ($minLng + $maxLng) / 2;
        $centerCell = self::latLngToCell(LatLng::fromDegrees($centerLat, $centerLng), $resolution);

        if ($centerCell === null) {
            return [];
        }

        $maxDist = (int)max(
            self::gridDistance($centerCell, self::latLngToCell(LatLng::fromDegrees($minLat, $minLng), $resolution) ?? $centerCell),
            self::gridDistance($centerCell, self::latLngToCell(LatLng::fromDegrees($maxLat, $maxLng), $resolution) ?? $centerCell),
            self::gridDistance($centerCell, self::latLngToCell(LatLng::fromDegrees($minLat, $maxLng), $resolution) ?? $centerCell),
            self::gridDistance($centerCell, self::latLngToCell(LatLng::fromDegrees($maxLat, $minLng), $resolution) ?? $centerCell)
        );

        $found = [];
        $checked = [];
        $queue = [$centerCell];

        while (!empty($queue)) {
            $cell = array_shift($queue);
            $idx = $cell->index();

            if (isset($checked[$idx])) {
                continue;
            }
            $checked[$idx] = true;

            if (!self::cellInsidePolygon($cell, $polygon)) {
                continue;
            }

            $found[] = $cell;

            $dist = self::gridDistance($centerCell, $cell);
            if ($dist !== null && $dist <= $maxDist + 1) {
                $neighbors = self::gridDiskNeighbors($cell);
                foreach ($neighbors as $n) {
                    if (!isset($checked[$n->index()])) {
                        $queue[] = $n;
                    }
                }
            }
        }

        return $found;
    }

    public static function polygonToCellsExperimental(
        GeoPolygon $polygon,
        int $resolution,
        int $mode,
        ?int $maxNumCells = null
    ): array {
        if ($mode === C::CONTAINMENT_INVALID) {
            return [];
        }

        $maxCells = $maxNumCells ?? PHP_INT_MAX;

        $outerLoop = $polygon->geoLoop->vertices;
        if (empty($outerLoop)) {
            return [];
        }

        $minLat = 90.0;
        $maxLat = -90.0;
        $minLng = 180.0;
        $maxLng = -180.0;

        foreach ($outerLoop as $v) {
            $minLat = min($minLat, $v->lat());
            $maxLat = max($maxLat, $v->lat());
            $minLng = min($minLng, $v->lng());
            $maxLng = max($maxLng, $v->lng());
        }

        $centerLat = ($minLat + $maxLat) / 2;
        $centerLng = ($minLng + $maxLng) / 2;
        $centerCell = self::latLngToCell(LatLng::fromDegrees($centerLat, $centerLng), $resolution);

        if ($centerCell === null) {
            return [];
        }

        $maxDist = (int)max(
            self::gridDistance($centerCell, self::latLngToCell(LatLng::fromDegrees($minLat, $minLng), $resolution) ?? $centerCell),
            self::gridDistance($centerCell, self::latLngToCell(LatLng::fromDegrees($maxLat, $maxLng), $resolution) ?? $centerCell),
            self::gridDistance($centerCell, self::latLngToCell(LatLng::fromDegrees($minLat, $maxLng), $resolution) ?? $centerCell),
            self::gridDistance($centerCell, self::latLngToCell(LatLng::fromDegrees($maxLat, $minLng), $resolution) ?? $centerCell)
        );

        $found = [];
        $checked = [];
        $queue = [$centerCell];

        while (!empty($queue)) {
            if (count($found) >= $maxCells) {
                break;
            }

            $cell = array_shift($queue);
            $idx = $cell->index();

            if (isset($checked[$idx])) {
                continue;
            }
            $checked[$idx] = true;

            $isContained = self::cellInsidePolygon($cell, $polygon);

            if ($mode === C::CONTAINMENT_FULL && !$isContained) {
                continue;
            }

            if ($mode === C::CONTAINMENT_CENTER) {
                $cellCenter = self::cellToLatLng($cell);
                $isContained = self::pointInsideLoop($cellCenter, $polygon->geoLoop);
                foreach ($polygon->holes as $hole) {
                    if (self::pointInsideLoop($cellCenter, $hole)) {
                        $isContained = false;
                        break;
                    }
                }
                if (!$isContained) {
                    continue;
                }
            }

            if (($mode === C::CONTAINMENT_OVERLAPPING || $mode === C::CONTAINMENT_OVERLAPPING_BBOX) && !$isContained) {
                continue;
            }

            $found[] = $cell;

            $dist = self::gridDistance($centerCell, $cell);
            if ($dist !== null && $dist <= $maxDist + 1) {
                $neighbors = self::gridDiskNeighbors($cell);
                foreach ($neighbors as $n) {
                    if (!isset($checked[$n->index()])) {
                        $queue[] = $n;
                    }
                }
            }
        }

        return $found;
    }

    private static function cellInsidePolygon(Cell $cell, GeoPolygon $polygon): bool
    {
        $center = self::cellToLatLng($cell);

        foreach ([$polygon->geoLoop, ...$polygon->holes] as $loop) {
            $inside = self::pointInsideLoop($center, $loop);
            if ($loop === $polygon->geoLoop) {
                if (!$inside) {
                    return false;
                }
            } else {
                if ($inside) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function pointInsideLoop(LatLng $point, GeoLoop $loop): bool
    {
        $vertices = $loop->vertices;
        $n = count($vertices);
        if ($n < 3) {
            return false;
        }

        $inside = false;
        $lat = $point->lat();
        $lng = $point->lng();

        $j = $n - 1;
        for ($i = 0; $i < $n; $i++) {
            $yi = $vertices[$i]->lat();
            $xi = $vertices[$i]->lng();
            $yj = $vertices[$j]->lat();
            $xj = $vertices[$j]->lng();

            if ((($yi > $lng) !== ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
            $j = $i;
        }

        return $inside;
    }

    public static function cellsToMultiPolygon(array $cells): array
    {
        if (empty($cells)) {
            return [];
        }

        $visited = [];
        $polygons = [];

        foreach ($cells as $cell) {
            $idx = $cell->index();
            if (isset($visited[$idx])) {
                continue;
            }

            $component = [];
            $queue = [$cell];

            while (!empty($queue)) {
                $c = array_shift($queue);
                $cIdx = $c->index();

                if (isset($visited[$cIdx])) {
                    continue;
                }
                $visited[$cIdx] = true;
                $component[] = $c;

                $neighbors = self::gridDiskNeighbors($c);
                foreach ($neighbors as $n) {
                    $nIdx = $n->index();
                    if (!isset($visited[$nIdx])) {
                        $found = false;
                        foreach ($cells as $inputCell) {
                            if ($inputCell->index() === $nIdx) {
                                $found = true;
                                break;
                            }
                        }
                        if ($found) {
                            $queue[] = $n;
                        }
                    }
                }
            }

            if (!empty($component)) {
                $polygons[] = self::cellsToGeoPolygon($component);
            }
        }

        return $polygons;
    }

    private static function cellsToGeoPolygon(array $cells): GeoPolygon
    {
        if (empty($cells)) {
            return new GeoPolygon(new GeoLoop([]));
        }

        $allVertices = [];
        foreach ($cells as $cell) {
            $boundary = self::cellToBoundary($cell);
            foreach ($boundary->vertices as $v) {
                $allVertices[] = $v;
            }
        }

        if (empty($allVertices)) {
            return new GeoPolygon(new GeoLoop([]));
        }

        return new GeoPolygon(new GeoLoop($allVertices));
    }

    public static function areNeighborCells(Cell $a, Cell $b): bool
    {
        $neighbors = self::gridDiskNeighbors($a);
        $bIdx = $b->index();
        foreach ($neighbors as $n) {
            if ($n->index() === $bIdx) {
                return true;
            }
        }
        return false;
    }

    public static function gridDistance(Cell $a, Cell $b): ?int
    {
        $aIdx = $a->index();
        $bIdx = $b->index();

        $aRes = $a->resolution();
        $bRes = $b->resolution();

        if ($aRes !== $bRes) {
            return null;
        }

        $aBase = $a->baseCellNumber();
        $bBase = $b->baseCellNumber();

        if ($aBase === $bBase && $aRes === 0) {
            return 0;
        }

        $queue = [[$a, 0]];
        $visited = [$aIdx => true];
        $head = 0;

        while ($head < count($queue)) {
            [$current, $dist] = $queue[$head++];

            if ($current->index() === $bIdx) {
                return $dist;
            }

            $neighbors = self::gridDiskNeighbors($current);
            foreach ($neighbors as $n) {
                $nIdx = $n->index();
                if (!isset($visited[$nIdx])) {
                    $visited[$nIdx] = true;
                    $queue[] = [$n, $dist + 1];
                }
            }
        }

        return null;
    }

    public static function gridPath(Cell $start, Cell $end): array
    {
        $startIdx = $start->index();
        $endIdx = $end->index();

        if ($start->resolution() !== $end->resolution()) {
            return [];
        }

        if ($startIdx === $endIdx) {
            return [$start];
        }

        $queue = [[$start, [$start]]];
        $visited = [$startIdx => true];
        $head = 0;

        while ($head < count($queue)) {
            [$current, $path] = $queue[$head++];

            if ($current->index() === $endIdx) {
                return $path;
            }

            $neighbors = self::gridDiskNeighbors($current);
            foreach ($neighbors as $n) {
                $nIdx = $n->index();
                if (!isset($visited[$nIdx])) {
                    $visited[$nIdx] = true;
                    $queue[] = [$n, [...$path, $n]];
                }
            }
        }

        return [];
    }

    public static function cellToLocalIJ(Cell $origin, Cell $cell): ?CoordIJ
    {
        $originIdx = $origin->index();
        $cellIdx = $cell->index();

        if (!$origin->isValid() || !$cell->isValid()) {
            return null;
        }

        if ($origin->resolution() !== $cell->resolution()) {
            return null;
        }

        $originBase = $origin->baseCellNumber();
        $cellBase = $cell->baseCellNumber();

        $originData = BaseCells::getBaseCellData($originBase);
        $cellData = BaseCells::getBaseCellData($cellBase);

        if ($originData === null || $cellData === null) {
            return null;
        }

        if ($originData['face'] !== $cellData['face']) {
            $cellCenter = self::cellToLatLng($cell);
            $originCenter = self::cellToLatLng($origin);
            $dist = self::gridDistance($origin, $cell);
            if ($dist === null || $dist > 1000) {
                return null;
            }
            $i = round(($cellCenter->lng() - $originCenter->lng()) / 0.01);
            $j = round(($cellCenter->lat() - $originCenter->lat()) / 0.01);
            return new CoordIJ((int)$i, (int)$j);
        }

        $i = $cellData['i'] - $originData['i'];
        $j = $cellData['j'] - $originData['j'];
        $k = $cellData['k'] - $originData['k'];

        $res = $origin->resolution();
        for ($r = 1; $r <= $res; $r++) {
            $digit = ($cellIdx >> ((15 - $r) * 3)) & 0x7;
            $vec = self::UNIT_VECS[$digit];
            $i += $vec[0];
            $j += $vec[1];
            $k += $vec[2];

            if ($r % 2 !== 0) {
                $newK = $i + $j - intdiv($i + $j + 1, 3);
                $newJ = $j - intdiv($newK + 1, 2);
                $newI = $i - intdiv($newJ + 2, 2);
                $i = $newI;
                $j = $newJ;
                $k = $newK;
            } else {
                $j = $j - $k;
                $i = $i - intdiv($j + 1, 2);
                $k = 0;
            }
        }

        $originI = $originData['i'];
        $originJ = $originData['j'];

        return new CoordIJ($i + $originI, $j + $originJ);
    }

    public static function localIJToCell(Cell $origin, CoordIJ $ij): ?Cell
    {
        if (!$origin->isValid()) {
            return null;
        }

        $res = $origin->resolution();
        $originBase = $origin->baseCellNumber();
        $originData = BaseCells::getBaseCellData($originBase);

        if ($originData === null) {
            return null;
        }

        $i = $originData['i'] + $ij->i;
        $j = $originData['j'] + $ij->j;
        $k = $originData['k'];

        $face = $originData['face'];
        $faceIJK = ['face' => $face, 'i' => $i, 'j' => $j, 'k' => $k];

        $index = self::faceIJKToH3Index($faceIJK, $res);
        return Cell::fromIndex($index);
    }

    public static function isValidDirectedEdge(int $index): bool
    {
        $mode = ($index >> 59) & 0xF;
        return $mode === 2;
    }

    public static function isValidIndex(int $index): bool
    {
        if ($index === 0) {
            return false;
        }
        $mode = ($index >> C::H3_MODE_OFFSET) & 0xF;
        
        return match ($mode) {
            C::H3_CELL_MODE => Cell::isValidCell($index),
            C::H3_DIRECTEDEDGE_MODE => self::isValidDirectedEdge($index),
            C::H3_VERTEX_MODE => Vertex::isValidVertex($index),
            default => false,
        };
    }

    public static function cellsToDirectedEdge(Cell $origin, Cell $destination): ?DirectedEdge
    {
        if ($origin->resolution() !== $destination->resolution()) {
            return null;
        }

        if (!self::areNeighborCells($origin, $destination)) {
            return null;
        }

        $originIdx = $origin->index();
        $destIdx = $destination->index();

        $edgeIdx = $destIdx | (2 << 59);
        
        return DirectedEdge::fromIndex($edgeIdx);
    }

    public static function directedEdgeToCells(Cell|DirectedEdge $edge): array
    {
        if (!self::isValidDirectedEdge($edge->index())) {
            return [];
        }

        $originIdx = $edge->index() & 0xFFFFFFFFFFFFFFF;
        $destIdx = ($edge->index() >> 45) & 0x3FFF;
        
        $res = ($originIdx >> 52) & 0xF;
        $baseCell = ($originIdx >> 45) & 0x7F;

        $digit = ($originIdx >> 18) & 0x7;
        $dir = match ($digit) {
            1 => 0,
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            6 => 5,
            default => 0,
        };

        $neighbors = self::gridDiskNeighbors(Cell::fromIndex($originIdx));
        
        if (isset($neighbors[$dir])) {
            return [Cell::fromIndex($originIdx), $neighbors[$dir]];
        }

        return [Cell::fromIndex($originIdx)];
    }

    public static function originToDirectedEdges(Cell $origin): array
    {
        $neighbors = self::gridDiskNeighbors($origin);
        $edges = [];

        foreach ($neighbors as $neighbor) {
            $edge = self::cellsToDirectedEdge($origin, $neighbor);
            if ($edge !== null) {
                $edges[] = $edge;
            }
        }

        return $edges;
    }

    public static function getDirectedEdgeOrigin(Cell|DirectedEdge $edge): ?Cell
    {
        if (!self::isValidDirectedEdge($edge->index())) {
            return null;
        }

        $originIdx = $edge->index() & 0xFFFFFFFFFFFFFFF;
        return Cell::fromIndex($originIdx);
    }

    public static function getDirectedEdgeDestination(Cell|DirectedEdge $edge): ?Cell
    {
        if (!self::isValidDirectedEdge($edge->index())) {
            return null;
        }

        $originIdx = $edge->index() & 0xFFFFFFFFFFFFFFF;
        $neighbors = self::gridDiskNeighbors(Cell::fromIndex($originIdx));

        $digit = ($edge->index() >> 45) & 0x3FFF;
        $dir = $digit % 6;

        return $neighbors[$dir] ?? null;
    }

    public static function directedEdgeToBoundary(Cell|DirectedEdge $edge): CellBoundary
    {
        $origin = self::getDirectedEdgeOrigin($edge);
        $dest = self::getDirectedEdgeDestination($edge);

        if ($origin === null || $dest === null) {
            return new CellBoundary([]);
        }

        $originCenter = self::cellToLatLng($origin);
        $destCenter = self::cellToLatLng($dest);

        $res = $origin->resolution();
        $edgeLen = self::hexagonEdgeLengthAvgKm($res) ?? 0.001;

        $lat1 = $originCenter->lat();
        $lng1 = $originCenter->lng();
        $lat2 = $destCenter->lat();
        $lng2 = $destCenter->lng();

        $midLat = ($lat1 + $lat2) / 2;
        $midLng = ($lng1 + $lng2) / 2;

        $vertices = [
            LatLng::fromDegrees($lat1, $lng1),
            LatLng::fromDegrees($midLat, $midLng),
            LatLng::fromDegrees($lat2, $lng2),
        ];

        return new CellBoundary($vertices);
    }

    public static function cellToParent(Cell $cell, int $parentRes): ?Cell
    {
        $res = $cell->resolution();

        if ($parentRes < 0 || $parentRes > $res) {
            return null;
        }

        if ($parentRes === $res) {
            return $cell;
        }

        $index = $cell->index();

        $digitPos = (15 - $res) * 3;
        $index = $index & ~(0x7 << $digitPos);

        $newResPos = (15 - $parentRes) * 3;
        $index = ($index & ~(0xF << 52)) | ($parentRes << 52);

        return Cell::fromIndex($index);
    }

    public static function cellToImmediateParent(Cell $cell): ?Cell
    {
        $res = $cell->resolution();
        if ($res === 0) {
            return null;
        }
        return self::cellToParent($cell, $res - 1);
    }

    public static function cellToChildren(Cell $cell, int $childRes): array
    {
        $res = $cell->resolution();

        if ($childRes < $res || $childRes > self::MAX_RESOLUTION) {
            return [];
        }

        if ($childRes === $res) {
            return [$cell];
        }

        $cells = [];
        for ($pos = 0; $pos < 7; $pos++) {
            $child = self::childPosToCell($pos, $cell, $childRes);
            if ($child !== null) {
                $cells[] = $child;
            }
        }

        return $cells;
    }

    public static function cellToCenterChild(Cell $cell, int $childRes): ?Cell
    {
        $res = $cell->resolution();

        if ($childRes <= $res || $childRes > self::MAX_RESOLUTION) {
            return null;
        }

        $parentIdx = $cell->index();

        $digitPos = (15 - $childRes) * 3;
        $index = ($parentIdx & ~(0x7 << $digitPos)) | (0 << $digitPos);

        $newResPos = (15 - $childRes) * 3;
        $index = ($index & ~(0xF << 52)) | ($childRes << 52);

        return Cell::fromIndex($index);
    }

    public static function cellToImmediateChildren(Cell $cell): array
    {
        $res = $cell->resolution();
        if ($res >= self::MAX_RESOLUTION) {
            return [];
        }
        return self::cellToChildren($cell, $res + 1);
    }

    public static function childPosToCell(int $position, Cell $cell, int $resolution): ?Cell
    {
        $cellRes = $cell->resolution();

        if ($resolution <= $cellRes || $resolution > self::MAX_RESOLUTION) {
            return null;
        }

        $parentIdx = $cell->index();

        $digitPos = (15 - $resolution) * 3;
        $index = ($parentIdx & ~(0x7 << $digitPos));

        $digit = $position % 7;
        $index = $index | ($digit << $digitPos);

        $newResPos = (15 - $resolution) * 3;
        $index = ($index & ~(0xF << 52)) | ($resolution << 52);

        return Cell::fromIndex($index);
    }

    public static function cellToChildPos(Cell $cell, int $resolution): ?int
    {
        $cellRes = $cell->resolution();

        if ($resolution <= $cellRes || $resolution > self::MAX_RESOLUTION) {
            return null;
        }

        $cellIdx = $cell->index();
        $digitPos = (15 - $resolution) * 3;
        $digit = ($cellIdx >> $digitPos) & 0x7;

        if ($digit === 0) {
            return 0;
        }

        $centerChild = self::cellToCenterChild($cell, $resolution);
        if ($centerChild === null) {
            return null;
        }

        $resDiff = $resolution - $cellRes;
        $basePos = 0;

        for ($r = $cellRes + 1; $r <= $resolution; $r++) {
            $basePos *= 7;
        }

        $currentDigit = 0;
        $idx = $cell->index();
        for ($r = $cellRes + 1; $r <= $resolution; $r++) {
            $digitPos = (15 - $r) * 3;
            $currentDigit = ($idx >> $digitPos) & 0x7;
            $basePos += $currentDigit;
            if ($r < $resolution) {
                $basePos *= 7;
            }
        }

        return $basePos;
    }

    public static function compactCells(array $cells): array
    {
        if (empty($cells)) {
            return [];
        }

        $res = $cells[0]->resolution();
        
        $sorted = $cells;
        usort($sorted, fn($a, $b) => $a->index() <=> $b->index());

        $toUncompact = [];
        $seen = [];

        foreach ($sorted as $cell) {
            $idx = $cell->index();
            if (isset($seen[$idx])) {
                continue;
            }
            $seen[$idx] = true;

            $parent = self::cellToParent($cell, $res - 1);
            if ($parent !== null) {
                $toUncompact[$parent->index()][] = $cell;
            }
        }

        $result = [];
        
        foreach ($toUncompact as $parentIdx => $children) {
            if (count($children) === 7) {
                $parent = self::cellToParent($children[0], $res - 1);
                if ($parent !== null) {
                    $result[] = $parent;
                }
            } else {
                foreach ($children as $child) {
                    $result[] = $child;
                }
            }
        }

        if (count($result) === count($cells)) {
            return $result;
        }

        return self::compactCells($result);
    }

    public static function uncompactCells(array $cells, int $resolution): array
    {
        if (empty($cells)) {
            return [];
        }

        $result = [];

        foreach ($cells as $cell) {
            $children = self::cellToChildren($cell, $resolution);
            foreach ($children as $child) {
                $result[] = $child;
            }
        }

        return $result;
    }

    public static function icosahedronFaces(Cell $cell): array
    {
        $index = $cell->index();
        $baseCell = $cell->baseCellNumber();

        $baseData = BaseCells::getBaseCellData($baseCell);
        if ($baseData === null) {
            return [0];
        }

        return [$baseData['face']];
    }

    public static function cellAreaRads2(Cell $cell): float
    {
        $res = $cell->resolution();
        $areaKm2 = self::hexagonAreaAvgKm2($res);
        
        $earthRadius = 6371.007180918475;
        return $areaKm2 / ($earthRadius * $earthRadius);
    }

    public static function cellAreaKm2(Cell $cell): float
    {
        $res = $cell->resolution();
        return self::hexagonAreaAvgKm2($res) ?? 0.0;
    }

    public static function cellAreaM2(Cell $cell): float
    {
        return self::cellAreaKm2($cell) * 1000000;
    }

    public static function edgeLengthRads(Cell|DirectedEdge $edge): float
    {
        $res = $edge->resolution();
        $lenKm = self::hexagonEdgeLengthAvgKm($res) ?? 0;
        
        $earthRadius = 6371.007180918475;
        return $lenKm / $earthRadius;
    }

    public static function edgeLengthKm(Cell|DirectedEdge $edge): float
    {
        $res = $edge->resolution();
        return self::hexagonEdgeLengthAvgKm($res) ?? 0.0;
    }

    public static function edgeLengthM(Cell|DirectedEdge $edge): float
    {
        return self::edgeLengthKm($edge) * 1000;
    }

    public static function cellToVertex(Cell $cell, int $vertexNum): ?Vertex
    {
        $cellIsPentagon = $cell->isPentagon();
        $cellNumVerts = $cellIsPentagon ? 5 : 6;
        $res = $cell->resolution();

        if ($vertexNum < 0 || $vertexNum >= $cellNumVerts) {
            return null;
        }

        $owner = $cell->index();
        $ownerVertexNum = $vertexNum;

        if ($res === 0 || self::getIndexDigit($cell->index(), $res) !== 8) {
            $left = self::directionForVertexNum($cell->index(), $vertexNum, $cellIsPentagon);
            if ($left === null) {
                return null;
            }

            $leftNeighbor = self::gridDiskNeighbors($cell)[$left] ?? null;
            if ($leftNeighbor === null) {
                return null;
            }

            if ($leftNeighbor->index() < $owner) {
                $owner = $leftNeighbor->index();
                $ownerVertexNum = self::vertexNumForDirection(
                    $owner,
                    self::directionForNeighbor($owner, $cell->index()),
                    $cell->isPentagon()
                ) + 1;
                if ($owner >= $cellNumVerts) {
                    $ownerVertexNum -= $cellNumVerts;
                }
            }

            $right = self::directionForVertexNum(
                $cell->index(),
                ($vertexNum - 1 + $cellNumVerts) % $cellNumVerts,
                $cellIsPentagon
            );
            if ($right !== null) {
                $rightNeighbor = self::gridDiskNeighbors($cell)[$right] ?? null;
                if ($rightNeighbor !== null && $rightNeighbor->index() < $owner) {
                    $owner = $rightNeighbor->index();
                    if ($cell->isPentagon()) {
                        $dir = self::directionForNeighbor($owner, $cell->index());
                    } else {
                        $dir = self::revDirection(
                            self::directionForNeighbor($right, $cell->index()),
                            self::getCCWRotation60($cell->baseCellNumber(), 0)
                        );
                    }
                    $ownerVertexNum = self::vertexNumForDirection($owner, $dir, $cell->isPentagon()) + 1;
                    if ($ownerVertexNum >= $cellNumVerts) {
                        $ownerVertexNum -= $cellNumVerts;
                    }
                }
            }

            if ($owner === self::gridDiskNeighbors($cell)[$left]?->index()) {
                if ($cell->isPentagon()) {
                    $dir = self::directionForNeighbor($owner, $cell->index());
                } else {
                    $dir = self::revDirection(
                        self::directionForNeighbor($left, $cell->index()),
                        self::getCCWRotation60($cell->baseCellNumber(), 0)
                    );
                }
                $ownerVertexNum = self::vertexNumForDirection($owner, $dir, $cell->isPentagon()) + 1;
                if ($ownerVertexNum >= $cellNumVerts) {
                    $ownerVertexNum -= $cellNumVerts;
                }
            }
        }

        $ownerForVertex = $owner;
        $ownerForVertex &= ~(0xF << C::H3_MODE_OFFSET);
        $ownerForVertex &= ~(0xF << C::H3_RESERVED_OFFSET);

        $vertex = $ownerForVertex | (C::H3_VERTEX_MODE << C::H3_MODE_OFFSET);
        $vertex |= ($ownerVertexNum & 0xF) << C::H3_RESERVED_OFFSET;
        $vertex |= (1 << C::H3_MAX_OFFSET);

        return Vertex::fromIndex($vertex);
    }

    public static function cellToVertexes(Cell $cell): array
    {
        $cellIsPentagon = $cell->isPentagon();
        $cellNumVerts = $cellIsPentagon ? 5 : 6;

        $vertexes = [];
        for ($i = 0; $i < $cellNumVerts; $i++) {
            $vertex = self::cellToVertex($cell, $i);
            if ($vertex !== null) {
                $vertexes[] = $vertex;
            }
        }

        return $vertexes;
    }

    public static function vertexToLatLng(Vertex $vertex): ?LatLng
    {
        if (($vertex->index() >> C::H3_MODE_OFFSET & 0xF) !== C::H3_VERTEX_MODE) {
            return null;
        }

        $vertexNum = ($vertex->index() >> C::H3_RESERVED_OFFSET) & 0xF;
        $owner = $vertex->index();
        $owner &= ~(0xF << C::H3_MODE_OFFSET);
        $owner &= ~(0xF << C::H3_RESERVED_OFFSET);
        $owner |= (C::H3_CELL_MODE << C::H3_MODE_OFFSET);
        $owner |= (1 << C::H3_MAX_OFFSET);

        $ownerCell = Cell::fromIndex($owner);
        if ($ownerCell === null || !$ownerCell->isValid()) {
            return null;
        }

        $verts = self::cellToBoundary($ownerCell);
        $vertsArr = $verts->vertices;

        if (!isset($vertsArr[$vertexNum])) {
            return null;
        }

        return $vertsArr[$vertexNum];
    }

    public static function vertexToCell(Vertex $vertex): ?Cell
    {
        if (($vertex->index() >> C::H3_MODE_OFFSET & 0xF) !== C::H3_VERTEX_MODE) {
            return null;
        }

        $owner = $vertex->index();
        $owner &= ~(0xF << C::H3_MODE_OFFSET);
        $owner &= ~(0xF << C::H3_RESERVED_OFFSET);
        $owner |= (C::H3_CELL_MODE << C::H3_MODE_OFFSET);
        $owner |= (1 << C::H3_MAX_OFFSET);

        return Cell::fromIndex($owner);
    }

    public static function isValidVertex(Vertex $vertex): bool
    {
        if (($vertex->index() >> C::H3_MODE_OFFSET & 0xF) !== C::H3_VERTEX_MODE) {
            return false;
        }

        $vertexNum = ($vertex->index() >> C::H3_RESERVED_OFFSET) & 0xF;
        $owner = $vertex->index();
        $owner &= ~(0xF << C::H3_MODE_OFFSET);
        $owner &= ~(0xF << C::H3_RESERVED_OFFSET);
        $owner |= (C::H3_CELL_MODE << C::H3_MODE_OFFSET);
        $owner |= (1 << C::H3_MAX_OFFSET);

        $ownerCell = Cell::fromIndex($owner);
        if ($ownerCell === null || !$ownerCell->isValid()) {
            return false;
        }

        $canonical = self::cellToVertex($ownerCell, $vertexNum);
        return $canonical !== null && $canonical->index() === $vertex->index();
    }

    private static function directionForVertexNum(int $cellIndex, int $vertexNum, bool $isPentagon): ?int
    {
        static $pentagonDirs = [1, 2, 3, 4, 0];
        static $hexDirs = [1, 2, 3, 4, 5, 0];

        $dirs = $isPentagon ? $pentagonDirs : $hexDirs;
        return $dirs[$vertexNum] ?? null;
    }

    private static function vertexNumForDirection(int $cellIndex, int $dir, bool $isPentagon): int
    {
        static $pentagonVerts = [4, 0, 1, 2, 3];
        static $hexVerts = [5, 0, 1, 2, 3, 4];

        $verts = $isPentagon ? $pentagonVerts : $hexVerts;
        return array_search($dir, $verts, true) ?: 0;
    }

    private static function directionForNeighbor(int $cellIndex, int $neighborIndex): int
    {
        $cell = Cell::fromIndex($cellIndex);
        if ($cell === null || !$cell->isValid()) {
            return 0;
        }

        $neighbors = self::gridDiskNeighbors($cell);
        
        foreach ($neighbors as $dir => $neighbor) {
            if ($neighbor->index() === $neighborIndex) {
                return $dir;
            }
        }

        return 0;
    }

    private static function revDirection(int $dir, int $rotations): int
    {
        static $revDirs = [0, 5, 3, 4, 1, 0, 2];
        $adjusted = ($dir + $rotations) % 6;
        return $revDirs[$adjusted] ?? $dir;
    }

    private static function getCCWRotation60(int $baseCell, int $face): int
    {
        return BaseCells::baseCellToCCWrot60($baseCell, $face);
    }

    public static function getIndexDigit(int $index, int $res): int
    {
        $digitOffset = C::H3_PER_DIGIT_OFFSET * (C::MAX_RESOLUTION - $res + 1);
        return ($index >> $digitOffset) & 0x7;
    }

    public static function cellToString(Cell $cell): string
    {
        return self::indexToString($cell->index());
    }

    public static function indexFromString(string $s): int
    {
        $s = str_starts_with(strtolower($s), '0x')
            ? substr($s, 2)
            : $s;
        return intval($s, 16);
    }

    public static function indexToString(int $i): string
    {
        return sprintf('%016x', $i);
    }

    public static function newLatLng(float $lat, float $lng): LatLng
    {
        return LatLng::fromDegrees($lat, $lng);
    }

    public static function vertexFromString(string $hex): ?Vertex
    {
        return Vertex::fromString($hex);
    }

    public static function directedEdgeFromString(string $hex): ?DirectedEdge
    {
        return DirectedEdge::fromString($hex);
    }
}