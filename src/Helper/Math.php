<?php

declare(strict_types=1);

namespace H3\Helper;

use H3\Constants;
use H3\ValueObject\Vec3d;

final readonly class Math
{
    /**
     * Normalizes radians to a value between 0.0 and two PI.
     *
     * @param float $rads The input radians value.
     * @return float The normalized radians value.
     */
    public static function posAngleRads(float $rads): float
    {
        $tmp = ($rads < 0.0) ? $rads + Constants::M_2PI : $rads;
        if ($rads >= Constants::M_2PI) {
            $tmp -= Constants::M_2PI;
        }
        return $tmp;
    }

    public static function vec3DistSq(Vec3d $v1, Vec3d $v2): float
    {
        $d = self::vec3LinComb(1.0, $v1, -1.0, $v2);
        return self::vec3NormSq($d);
    }

    public static function vec3LinComb(float $a, Vec3d $v1, float $b, Vec3d $v2): Vec3d
    {
        return new Vec3d(
            x: $a * $v1->getX() + $b * $v2->getX(),
            y: $a * $v1->getY() + $b * $v2->getY(),
            z: $a * $v1->getZ() + $b * $v2->getZ(),
        );
    }

    public static function vec3NormSq(Vec3d $v): float
    {
        return self::vec3Dot($v, $v);
    }

    public static function vec3Dot(Vec3d $v1, Vec3d $v2): float
    {
        return $v1->getX() * $v2->getX() + $v1->getY() * $v2->getY() + $v1->getZ() * $v2->getZ();
    }

    /**
     * Calculates the azimuth from p1 to p2.
     *
     * @param Vec3d $p1 The first vector.
     * @param Vec3d $p2 The second vector.
     * @return float The azimuth in radians.
     */
    public static function vec3AzimuthRads(Vec3d $p1, Vec3d $p2): float
    {
        $tangentBasis = self::vec3TangentBasis($p1);
        $northDir = $tangentBasis['northDir'];
        $eastDir = $tangentBasis['eastDir'];

        // project p2 onto tangent plane at p1
        $p2Proj = self::vec3LinComb(1.0, $p2, -self::vec3Dot($p2, $p1), $p1);
        $p2Proj = self::vec3Normalize($p2Proj);

        return atan2(
            self::vec3Dot($p2Proj, $eastDir),
            self::vec3Dot($p2Proj, $northDir),
        );
    }

    /**
     * Compute the local north and east directions on the tangent plane
     * at a point on the unit sphere.
     *
     * Will not work if p is at a pole, but icosahedron face centers
     * are never at the poles.
     *
     * @param Vec3d $p Unit vector on the sphere.
     * @return array{northDir: Vec3d, eastDir: Vec3d}
     * north Output: local north direction on tangent plane.
     * east Output: local east direction on tangent plane.
     */
    public static function vec3TangentBasis(Vec3d $p): array
    {
        $northPole = new Vec3d(0.0, 0.0, 1.0);
        $north = self::vec3LinComb(1.0, $northPole, -self::vec3Dot($northPole, $p), $p);
        $north = self::vec3Normalize($north);
        $east = self::vec3Cross($north, $p);

        return [
            'northDir' => $north,
            'eastDir' => $east,
        ];
    }

    public static function vec3Norm(Vec3d $v): float
    {
        return sqrt(self::vec3NormSq($v));
    }

    public static function vec3Normalize(Vec3d $v): Vec3d
    {
        $norm = self::vec3Norm($v);
        $s = 0.0;
        if ($norm > 0.0) {
            $s = 1.0 / $norm;
        }

        return new Vec3d(
            x: $v->getX() * $s,
            y: $v->getY() * $s,
            z: $v->getZ() * $s,
        );
    }

    public static function vec3Cross(Vec3d $v1, Vec3d $v2): Vec3d
    {
        return new Vec3d(
            x: $v1->getY() * $v2->getZ() - $v1->getZ() * $v2->getY(),
            y: $v1->getZ() * $v2->getX() - $v1->getX() * $v2->getZ(),
            z: $v1->getX() * $v2->getY() - $v1->getY() * $v2->getX(),
        );
    }

    /**
     * Returns whether or not a resolution is a Class III grid. Note that odd
     * resolutions are Class III and even resolutions are Class II.
     *
     * @param int $res The H3 resolution.
     * @return int 1 if the resolution is a Class III grid, and 0 if the resolution is a Class II grid.
     */
    public static function isResolutionClassIII(int $res): int
    {
        return $res % 2;
    }
}