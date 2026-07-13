<?php

declare(strict_types=1);

namespace H3\Converter;

use H3\Constants;
use H3\FaceProjection;
use H3\Helper\Math;
use H3\ValueObject\FaceIJK;
use H3\ValueObject\Vec2d;
use H3\ValueObject\Vec3d;

final readonly class Vec3dConverter
{
    /**
     * Encodes a Vec3d coordinate to the FaceIJK address of the containing
     * cell at the specified resolution.
     *
     * Vec3d $vec3d is expected to be on the unit sphere.
     *
     * @param Vec3d $vec3d The Vec3d coordinates to encode.
     * @param int $resolution The desired H3 resolution for the encoding.
     * @return FaceIJK FaceIJK address of the containing cell at resolution res.
     */
    public static function vec3ToFaceIjk(Vec3d $vec3d, int $resolution): FaceIJK
    {
        $hex2d = self::vec3ToHex2d($vec3d, $resolution);
        $face = $hex2d['face'];
        $v = $hex2d['v'];
        $coordIjk = Vec2dConverter::vec2dToCoordIJK($v);

        return new FaceIJK($face, $coordIjk);
    }

    /**
     * Encodes a coordinate on the sphere to the corresponding icosahedral face and
     * containing 2D hex coordinates relative to that face center.
     *
     * Vec3d $vec3d is expected to be on the unit sphere.
     *
     * @param Vec3d $vec3d The Vec3d coordinates to encode.
     * @param int $resolution The desired H3 resolution for the encoding.
     * @return array{face: int, v: Vec2d}
     *
     * face Output: The icosahedral face containing the coordinates.
     * v Output: The 2D hex coordinates of the cell containing the point.
     */
    public static function vec3ToHex2d(Vec3d $vec3d, int $resolution): array
    {
        // determine the icosahedron face
        $closestFace = self::vec3ToClosestFace($vec3d);
        $face = $closestFace['face'];
        $sqd = $closestFace['sqd'];

        // cos(r) = 1 - 2 * sin^2(r/2) = 1 - 2 * (sqd / 4) = 1 - sqd/2
        $r = acos(1.0 - $sqd * 0.5);
        if ($r < Constants::EPSILON) {
            return [
                'face' => $face,
                'v' => new Vec2d(0.0, 0.0),
            ];
        }

        // now have face and r, now find CCW theta from CII i-axis
        $theta = Math::posAngleRads(
            FaceProjection::FACE_AXES_AZ_RADS_CII[$face][0] -
            Math::posAngleRads(
                Math::vec3AzimuthRads(
                    Vec3d::fromArray(FaceProjection::FACE_CENTER_POINT[$face]),
                    $vec3d,
                ),
            ),
        );

        // adjust theta for Class III (odd resolutions)
        if (Math::isResolutionClassIII($resolution)) {
            $theta = Math::posAngleRads($theta - Constants::M_AP7_ROT_RADS);
        }

        // perform gnomonic scaling of r
        $r = tan($r);
        // scale for current resolution length u
        $r *= Constants::INV_RES0_U_GNOMONIC;
        for ($i = 0; $i < $resolution; $i++) {
            $r *= Constants::M_SQRT7;
        }
        // we now have (r, theta) in hex2d with theta ccw from x-axes
        // convert to local x,y
        $v = new Vec2d(
            x: $r * cos($theta),
            y: $r * sin($theta),
        );

        return [
            'face' => $face,
            'v' => $v,
        ];
    }

    /**
     * Encodes a coordinate on the sphere to the corresponding icosahedral face and
     * containing the squared euclidean distance to that face center.
     *
     * Vec3d v is expected to be on the unit sphere.
     *
     * @param Vec3d $v The Vec3d coordinates to encode.
     * @return array{face: int, sqd: float}
     *
     * face Output: The icosahedral face containing the coordinates.
     * sqd Output: The squared euclidean distance to its face center.
     */
    public static function vec3ToClosestFace(Vec3d $v): array
    {
        $face = 0;
        $sqd = 5.0;

        for ($f = 0; $f < Constants::NUM_ICOSA_FACES; ++$f) {
            $sqdT = Math::vec3DistSq(
                v1: Vec3d::fromArray(FaceProjection::FACE_CENTER_POINT[$f]),
                v2: $v,
            );
            if ($sqdT < $sqd) {
                $face = $f;
                $sqd = $sqdT;
            }
        }

        return [
            'face' => $face,
            'sqd' => $sqd,
        ];
    }
}