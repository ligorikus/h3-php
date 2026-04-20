<?php

declare(strict_types=1);

namespace H3\Internal;

use H3\ValueObject\LatLng;
use H3\InternalConstants as C;

final class FaceProjection
{
    private const FACE_NEIGHBORS = [
        [4, 1, 5],   // face 0: ij, ki, jk
        [0, 2, 6],   // face 1
        [1, 3, 7],   // face 2
        [2, 4, 8],   // face 3
        [3, 0, 9],   // face 4
        [10, 14, 0],  // face 5
        [11, 10, 1],  // face 6
        [12, 11, 2],  // face 7
        [13, 12, 3],  // face 8
        [14, 13, 4],  // face 9
        [5, 6, 15],   // face 10
        [6, 7, 16],   // face 11
        [7, 8, 17],   // face 12
        [8, 9, 18],   // face 13
        [9, 5, 19],   // face 14
        [19, 10, 11],  // face 15
        [15, 11, 12],  // face 16
        [16, 12, 13],  // face 17
        [17, 13, 14],  // face 18
        [18, 14, 10],  // face 19
    ];

    private const FACE_NEIGHBOR_DATA = [
        0 => [
            ['face' => 0, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 4, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 1, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 5, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        1 => [
            ['face' => 1, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 0, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 2, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 6, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        2 => [
            ['face' => 2, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 1, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 3, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 7, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        3 => [
            ['face' => 3, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 2, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 4, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 8, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        4 => [
            ['face' => 4, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 3, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 0, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 9, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        5 => [
            ['face' => 5, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 10, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 14, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 0, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        6 => [
            ['face' => 6, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 11, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 10, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 1, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        7 => [
            ['face' => 7, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 12, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 11, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 2, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        8 => [
            ['face' => 8, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 13, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 12, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 3, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        9 => [
            ['face' => 9, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 14, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 13, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 4, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        10 => [
            ['face' => 10, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 5, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 6, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 15, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        11 => [
            ['face' => 11, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 6, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 7, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 16, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        12 => [
            ['face' => 12, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 7, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 8, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 17, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        13 => [
            ['face' => 13, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 8, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 9, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 18, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        14 => [
            ['face' => 14, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 9, 'translate' => [2, 2, 0], 'ccwRot60' => 3],
            ['face' => 5, 'translate' => [2, 0, 2], 'ccwRot60' => 3],
            ['face' => 19, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        15 => [
            ['face' => 15, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 16, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 19, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 10, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        16 => [
            ['face' => 16, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 17, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 15, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 11, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        17 => [
            ['face' => 17, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 18, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 16, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 12, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        18 => [
            ['face' => 18, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 19, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 17, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 13, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
        19 => [
            ['face' => 19, 'translate' => [0, 0, 0], 'ccwRot60' => 0],
            ['face' => 15, 'translate' => [2, 0, 2], 'ccwRot60' => 1],
            ['face' => 18, 'translate' => [2, 2, 0], 'ccwRot60' => 5],
            ['face' => 14, 'translate' => [0, 2, 2], 'ccwRot60' => 3],
        ],
    ];

    public static function getAdjacentFaces(int $face): array
    {
        return self::FACE_NEIGHBORS[$face] ?? [];
    }

    public static function getNeighborData(int $face, int $dir): ?array
    {
        if ($dir < 0 || $dir > 3) {
            return null;
        }
        return self::FACE_NEIGHBOR_DATA[$face][$dir] ?? null;
    }

    public static function getFaceNeighborRotations(int $fromFace, int $toFace): ?int
    {
        for ($dir = 0; $dir < 4; $dir++) {
            $neighbor = self::FACE_NEIGHBOR_DATA[$fromFace][$dir] ?? null;
            if ($neighbor && $neighbor['face'] === $toFace) {
                return $neighbor['ccwRot60'];
            }
        }
        return null;
    }

    public static function getFaceNeighborTranslation(int $fromFace, int $toFace): ?array
    {
        for ($dir = 0; $dir < 4; $dir++) {
            $neighbor = self::FACE_NEIGHBOR_DATA[$fromFace][$dir] ?? null;
            if ($neighbor && $neighbor['face'] === $toFace) {
                return $neighbor['translate'];
            }
        }
        return null;
    }

    private const FACE_CENTER_POINT = [
        [0.2199307791404606, 0.6583691780274996, 0.7198475378926182],
        [-0.2139234834501421, 0.1478171829550703, 0.9656017935214205],
        [0.1092625278784797, -0.4811951572873210, 0.8697775121287253],
        [0.7428567301586791, -0.3593941678278028, 0.5648005936517033],
        [0.8112534709140969, 0.3448953237639384, 0.4721387736413930],
        [-0.1055498149613921, 0.9794457296411413, 0.1718874610009365],
        [-0.8075407579970092, 0.1533552485898818, 0.5695261994882688],
        [-0.2846148069787907, -0.8644080972654206, 0.4144792552473539],
        [0.7405621473854482, -0.6673299564565524, -0.0789837646326737],
        [0.8512303986474293, 0.4722343788582681, -0.2289137388687808],
        [-0.7405621473854481, 0.6673299564565524, 0.0789837646326737],
        [-0.8512303986474292, -0.4722343788582682, 0.2289137388687808],
        [0.1055498149613919, -0.9794457296411413, -0.1718874610009365],
        [0.8075407579970092, -0.1533552485898819, -0.5695261994882688],
        [0.2846148069787908, 0.8644080972654204, -0.4144792552473539],
        [-0.7428567301586791, 0.3593941678278027, -0.5648005936517033],
        [-0.8112534709140971, -0.3448953237639382, -0.4721387736413930],
        [-0.2199307791404607, -0.6583691780274996, -0.7198475378926182],
        [0.2139234834501420, -0.1478171829550704, -0.9656017935214205],
        [-0.1092625278784796, 0.4811951572873210, -0.8697775121287253],
    ];

    public static function geoToFaceAndGeoCoordinates(LatLng $geo): array
    {
        $lat = $geo->latRadians();
        $lng = $geo->lngRadians();

        $x = cos($lat) * cos($lng);
        $y = cos($lat) * sin($lng);
        $z = sin($lat);

        $bestFace = 0;
        $maxDot = -2.0;

        for ($f = 0; $f < 20; $f++) {
            $fc = self::FACE_CENTER_POINT[$f];
            $dot = $x * $fc[0] + $y * $fc[1] + $z * $fc[2];

            if ($dot > $maxDot) {
                $maxDot = $dot;
                $bestFace = $f;
            }
        }

        $faceCenter = self::FACE_CENTER_POINT[$bestFace];
        $faceLat = asin($faceCenter[2]);
        $faceLng = atan2($faceCenter[1], $faceCenter[0]);

        $x2 = $y - cos($faceLat) * sin($faceLng);
        $y2 = $x - cos($faceLat) * cos($faceLng);
        $z2 = $z - sin($faceLat);

        $dist = sqrt($x2 * $x2 + $y2 * $y2 + $z2 * $z2);

        $rLat = 0.0;
        $rLng = 0.0;

        if ($dist > 0.00001) {
            $rLat = asin($z2 / $dist);
            $rLng = atan2($y2, $x2);
        }

        while ($rLng > C::PI) {
            $rLng -= 2 * C::PI;
        }
        while ($rLng < -C::PI) {
            $rLng += 2 * C::PI;
        }

        return [
            'face' => $bestFace,
            'lat' => $rLat,
            'lng' => $rLng,
        ];
    }

    public static function faceCoordinatesToGeo(float $lat, float $lng, int $face): LatLng
    {
        $faceCenter = self::FACE_CENTER_POINT[$face];
        $faceLat = asin($faceCenter[2]);
        $faceLng = atan2($faceCenter[1], $faceCenter[0]);

        $sinLat = sin($lat);
        $cosLat = cos($lat);
        $sinLng = sin($lng);
        $cosLng = cos($lng);

        $x = $sinLat * cos($faceLat) + $cosLat * sin($faceLat) * $cosLng;
        $y = $cosLat * $sinLng;
        $z = $sinLat * sin($faceLat) - $cosLat * cos($faceLat) * $cosLng;

        $latRad = asin($z);
        $lngRad = $faceLng + atan2($y, $x);

        while ($lngRad > C::PI) {
            $lngRad -= 2 * C::PI;
        }
        while ($lngRad < -C::PI) {
            $lngRad += 2 * C::PI;
        }

        return LatLng::fromRadians($latRad, $lngRad);
    }

    public static function getFaceCenterGeo(int $face): array
    {
        $fc = self::FACE_CENTER_POINT[$face];
        return [asin($fc[2]), atan2($fc[1], $fc[0])];
    }

    public static function getFaceCenterPoint(int $face): array
    {
        return self::FACE_CENTER_POINT[$face];
    }
}