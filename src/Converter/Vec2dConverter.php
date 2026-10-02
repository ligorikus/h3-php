<?php

declare(strict_types=1);

namespace H3\Converter;

use H3\Constants;
use H3\FaceProjection;
use H3\Helper\Math;
use H3\ValueObject\CoordIJK;
use H3\ValueObject\Vec2d;
use H3\ValueObject\Vec3d;

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
        $k = 0;

        // first do a reverse conversion
        $a1 = abs($v->x);
        $a2 = abs($v->y);

        $x2 = $a2 * Constants::M_RSIN60;
        $x1 = $a1 + $x2 / 2.0;

        // check if we have the center of a hex
        $m1 = (int)$x1;
        $m2 = (int)$x2;

        // otherwise round correctly
        $r1 = $x1 - (float) $m1;
        $r2 = $x2 - (float) $m2;

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

        if ($v->x < 0.0) {
            if ($j%2 === 0) {
                $axisi = intdiv($j, 2);
                $diff = $i - $axisi;
                $i = $i - 2 * $diff;
            } else {
                $axisi = intdiv($j + 1, 2);
                $diff = $i - $axisi;
                $i = $i - (2 * $diff + 1);
            }
        }

        if ($v->y < 0.0) {
            $i = $i - intdiv(2 * $j + 1, 2);
            $j = -1 * $j;
        }

        $cijk = new CoordIJK($i, $j, $k);
        return $cijk->normalize();
    }

    public static function hex2dToVec3(
        Vec2d $v,
        int $face,
        int $res,
        int $substrate,
    ): Vec3d {
        // calculate (r, theta) in hex2d
        $r = $v->mag();

        if ($r < Constants::EPSILON) {
            return Vec3d::fromArray(FaceProjection::FACE_CENTER_POINT[$face]);
        }

        $theta = atan2($v->y, $v->x);

        // scale for current resolution length u
        for ($i = 0; $i < $res; $i++) {
            $r *= Constants::M_RSQRT7;
        }

        // scale accordingly if this is a substrate grid
        if ($substrate === 1) {
            $r *= Constants::M_ONETHIRD;
            // Never occurs because every case where this function is called with
            // substrate=1, the res has been adjusted by _faceIjkPentToVerts, which
            // adjusts the res by +1, making it no longer Class III.
            if (Math::isResolutionClassIII($res)) {
                $r *= Constants::M_RSQRT7;
            }
        }

        $r *= Constants::RES0_U_GNOMONIC;
        // perform inverse gnomonic scaling of r
        $r = atan($r);

        // adjust theta for Class III
        // if a substrate grid, then it's already been adjusted for Class III
        if ($substrate === 0 && Math::isResolutionClassIII($res) === 1) {
            $theta = Math::posAngleRads($theta + Constants::M_AP7_ROT_RADS);
        }

        // find theta as an azimuth
        $theta = Math::posAngleRads(FaceProjection::FACE_AXES_AZ_RADS_CII[$face][0] - $theta);

        // now find the point at (r,theta) from the face center
        $tangentBasis = Math::vec3TangentBasis(Vec3d::fromArray(FaceProjection::FACE_CENTER_POINT[$face]));
        $northDir = $tangentBasis['northDir'];
        $eastDir = $tangentBasis['eastDir'];
        $dir = Math::vec3LinComb(
            cos($theta),
            $northDir,
            sin($theta),
            $eastDir,
        );
        $vec3d = Math::vec3LinComb(
            cos($r),
            Vec3d::fromArray(FaceProjection::FACE_CENTER_POINT[$face]),
            sin($r),
            $dir,
        );
        return $vec3d->normalize();
    }
}
