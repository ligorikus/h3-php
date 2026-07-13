<?php

declare(strict_types=1);

namespace H3\Converter;

use H3\Constants;
use H3\ValueObject\CoordIJK;
use H3\ValueObject\Vec2d;

final readonly class Vec2dConverter
{
    /**
     * Determine the containing hex in ijk+ coordinates for a 2D cartesian
     * coordinate vector (from DGGRID).
     * @param Vec2d $v The 2D cartesian coordinate vector.
     * @return CoordIJK The ijk+ coordinates of the containing hex.
 */
    public static function vec2dToCoordIJK(Vec2d $v): CoordIJK
    {
        // quantize into the ij system and then normalize
        $i = 0;
        $j = 0;
        $k = 0;

        // first do a reverse conversion
        $a1 = abs($v->getX());
        $a2 = abs($v->getY());

        $x2 = $a2 * Constants::M_RSIN60;
        $x1 = $a1 + $x2 / 2.0;

        // check if we have the center of a hex
        $m1 = (int)$x1;
        $m2 = (int)$x2;

        // otherwise round correctly
        $r1 = $x1 - $m1;
        $r2 = $x2 - $m2;

        if ($r1 < 0.5) {
            if ($r1 < 1.0 / 3.0) {
                if ($r2 < (1.0 + $r1) / 2.0) {
                    $i = $m1;
                    $j = $m2;
                } else {
                    $i = $m1;
                    $j = $m2 + 1;
                }
            } else {
                if ($r2 < (1.0 - $r1)) {
                    $j = $m2;
                } else {
                    $j = $m2 + 1;
                }

                if ((1.0 - $r1) <= $r2 && $r2 < (2.0 * $r1)) {
                    $i = $m1 + 1;
                } else {
                    $i = $m1;
                }
            }
        } else {
            if ($r1 < 2.0 / 3.0) {
                if ($r2 < (1.0 - $r1)) {
                    $j = $m2;
                } else {
                    $j = $m2 + 1;
                }

                if ((2.0 * $r1 - 1.0) < $r2 && $r2 < (1.0 - $r1)) {
                    $i = $m1;
                } else {
                    $i = $m1 + 1;
                }
            } else {
                if ($r2 < ($r1 / 2.0)) {
                    $i = $m1 + 1;
                    $j = $m2;
                } else {
                    $i = $m1 + 1;
                    $j = $m2 + 1;
                }
            }
        }

        if ($v->getX() < 0.0) {
            if ($j %2 === 0) {
                $axisi = $j / 2;
                $diff = (int)($i - $axisi);
                $i = (int)($i - 2.0 * $diff);
            } else {
                $axisi = ($j + 1) / 2;
                $diff = $i - $axisi;
                $i = (int)($i - (2.0 * $diff + 1));
            }
        }

        if ($v->getY() < 0.0) {
            $i = (int)($i - (2 * $j + 1) / 2);
            $j = -1 * $j;
        }

        $cijk = new CoordIJK($i, $j, $k);
        return $cijk->normalize();
    }
}