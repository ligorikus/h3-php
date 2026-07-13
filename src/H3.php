<?php

declare(strict_types=1);

namespace H3;

use H3\Converter\FaceIJKConverter;
use H3\Converter\Vec3dConverter;
use H3\Exception\H3LatLngDomainException;
use H3\Exception\H3ResolutionException;
use H3\ValueObject\LatLng;
use H3\ValueObject\Vec3d;

final class H3
{
    /**
     * Encodes a coordinate on the sphere to the H3 index of the containing cell at
     * the specified resolution.
     * @param LatLng $latLng The spherical coordinates to encode.
     * @param int $resolution The desired H3 resolution for the encoding.
     * @return int The encoded H3Index.
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     */
    public static function latLngToCell(LatLng $latLng, int $resolution): int
    {
        if ($resolution < 0 || $resolution > Constants::MAX_H3_RES) {
            throw new H3ResolutionException('Invalid resolution');
        }

        if (!is_finite($latLng->getLat()) || !is_finite($latLng->getLng())) {
            throw new H3LatLngDomainException('Invalid lat/lng');
        }

        $vec3d = Vec3d::fromLatLng($latLng);
        return self::vec3ToCell($vec3d, $resolution);
    }

    /**
     * Encodes a coordinate on the sphere to the H3 index of the containing cell at
     * the specified resolution.
     *
     * Vec3d $vec3d is expected to be on the unit sphere.
     *
     * @param Vec3d $vec3d The 3D cartesian coordinates to encode.
     * @param int $resolution The desired H3 resolution for the encoding.
     * @return int The encoded H3Index.
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     */
    public static function vec3ToCell(Vec3d $vec3d, int $resolution): int
    {
        if ($resolution < 0 || $resolution > Constants::MAX_H3_RES) {
            throw new H3ResolutionException('Invalid resolution');
        }

        if (!is_finite($vec3d->getX()) || !is_finite($vec3d->getY()) || !is_finite($vec3d->getZ())) {
            throw new H3LatLngDomainException('Invalid x/y/z');
        }

        $fijk = Vec3dConverter::vec3ToFaceIjk($vec3d, $resolution);

        return FaceIJKConverter::faceIjkToH3Index($fijk, $resolution);
    }
}