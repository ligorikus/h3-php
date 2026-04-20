<?php

declare(strict_types=1);

namespace H3\Internal;

use H3\ValueObject\LatLng;
use H3\InternalConstants as C;

final class MathUtils
{
    public static function greatCircleDistanceRads(LatLng $a, LatLng $b): float
    {
        $latA = $a->latRadians();
        $lngA = $a->lngRadians();
        $latB = $b->latRadians();
        $lngB = $b->lngRadians();

        $dLat = $latB - $latA;
        $dLng = $lngB - $lngA;

        $sinDLat2 = sin($dLat / 2);
        $sinDLng2 = sin($dLng / 2);
        $sinLatA = sin($latA);
        $sinLatB = sin($latB);

        $aVal = $sinDLat2 * $sinDLat2 + $sinLatA * $sinLatB * $sinDLng2 * $sinDLng2;
        return 2 * atan2(sqrt($aVal), sqrt(1 - $aVal));
    }

    public static function greatCircleDistanceKm(LatLng $a, LatLng $b): float
    {
        return self::greatCircleDistanceRads($a, $b) * C::EARTH_RADIUS_KM;
    }

    public static function greatCircleDistanceM(LatLng $a, LatLng $b): float
    {
        return self::greatCircleDistanceRads($a, $b) * C::EARTH_RADIUS_M;
    }

    public static function hexagonAreaAvgKm2(int $res): float|false
    {
        if ($res < 0 || $res > C::MAX_H3_RES) {
            return false;
        }

        $area = C::EARTH_RADIUS_KM * C::EARTH_RADIUS_KM;
        $hexArea = $area / (3 * self::pow7($res) * self::pow7($res + 1));
        $pentArea = $area / (3 * 5 * $res * $res + 3 * $res);
        $res0HexArea = $area / 3;

        return $res === 0 ? $res0HexArea : $hexArea - ($res0HexArea - $pentArea) / self::pow7($res);
    }

    public static function hexagonAreaAvgM2(int $res): float|false
    {
        $km2 = self::hexagonAreaAvgKm2($res);
        return $km2 === false ? false : $km2 * 1000000;
    }

    public static function hexagonEdgeLengthAvgKm(int $res): float|false
    {
        if ($res < 0 || $res > C::MAX_H3_RES) {
            return false;
        }

        $sideLength = C::RES0_U_GNOMONIC / pow(7, $res + 1);
        return $sideLength * C::EARTH_RADIUS_KM;
    }

    public static function hexagonEdgeLengthAvgM(int $res): float|false
    {
        $km = self::hexagonEdgeLengthAvgKm($res);
        return $km === false ? false : $km * 1000;
    }

    public static function numCells(int $res): int|false
    {
        if ($res < 0 || $res > C::MAX_H3_RES) {
            return false;
        }

        return 2 + 120 * self::pow7($res);
    }

    private static function pow7(int $exp): int
    {
        static $powers = [
            0 => 1, 1 => 7, 2 => 49, 3 => 343, 4 => 2401,
            5 => 16807, 6 => 117649, 7 => 823543, 8 => 5764801,
            9 => 40353607, 10 => 282475249, 11 => 1977326743,
            12 => 13841287201, 13 => 96889010407, 14 => 678223072849,
            15 => 4747561509943, 16 => 332778305196041, 17 => 2329447806372287,
        ];

        return $powers[$exp] ?? 0;
    }

    public static function posAngleRads(float $rads): float
    {
        $tmp = $rads < 0.0 ? $rads + C::TWO_PI : $rads;
        if ($rads >= C::TWO_PI) {
            $tmp -= C::TWO_PI;
        }
        return $tmp;
    }

    public static function geoAzimuthRads(LatLng $p1, LatLng $p2): float
    {
        $lngDiff = $p2->lngRadians() - $p1->lngRadians();
        return atan2(
            cos($p2->latRadians()) * sin($lngDiff),
            cos($p1->latRadians()) * sin($p2->latRadians()) -
            sin($p1->latRadians()) * cos($p2->latRadians()) * cos($lngDiff)
        );
    }

    public static function geoAzDistanceRads(LatLng $p1, float $az, float $distance): LatLng
    {
        if ($distance < C::EPSILON) {
            return $p1;
        }

        $az = self::posAngleRads($az);

        if ($az < C::EPSILON || abs($az - C::PI) < C::EPSILON) {
            if ($az < C::EPSILON) {
                $lat = $p1->latRadians() + $distance;
            } else {
                $lat = $p1->latRadians() - $distance;
            }

            if (abs($lat - C::PI_2) < C::EPSILON) {
                return LatLng::fromRadians(C::PI_2, 0.0);
            } elseif (abs($lat + C::PI_2) < C::EPSILON) {
                return LatLng::fromRadians(-C::PI_2, 0.0);
            }

            $lng = $p1->lngRadians();
            while ($lng > C::PI) { $lng -= C::TWO_PI; }
            while ($lng < -C::PI) { $lng += C::TWO_PI; }
            return LatLng::fromRadians($lat, $lng);
        }

        $sinLat = sin($p1->latRadians()) * cos($distance) +
                  cos($p1->latRadians()) * sin($distance) * cos($az);
        $sinLat = max(-1.0, min(1.0, $sinLat));
        $lat = asin($sinLat);

        $cosAz = cos($az);
        $cosLat = cos($lat);
        $dLng = atan2(sin($distance) * sin($az), cos($distance) - sin($p1->latRadians()) * $sinLat);

        if ($cosLat < C::EPSILON) {
            $lng = $p1->lngRadians();
        } else {
            $lng = $p1->lngRadians() + atan2(sin($distance) * sin($az), cos($distance) - sin($p1->latRadians()) * $sinLat);
        }

        while ($lng > C::PI) { $lng -= C::TWO_PI; }
        while ($lng < -C::PI) { $lng += C::TWO_PI; }

        return LatLng::fromRadians($lat, $lng);
    }

    public static function isResClassIII(int $res): bool
    {
        return $res % 2 !== 0;
    }
}