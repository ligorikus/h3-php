<?php

declare(strict_types=1);

namespace H3;

use H3\Converter\H3IndexConverter;
use H3\Converter\Vec3dConverter;
use H3\Exception\H3DomainException;
use H3\Exception\H3IndexInvalidException;
use H3\Exception\H3LatLngDomainException;
use H3\Exception\H3ResolutionException;
use H3\ValueObject\LatLng;
use H3\ValueObject\Vec3d;

final class H3
{
    /**
     * Encodes a coordinate on the sphere to the H3 index of the containing cell at
     * the specified resolution.
     *
     * @param LatLng $latLng The spherical coordinates to encode.
     * @param int $resolution The desired H3 resolution for the encoding.
     * @return int The encoded H3Index.
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     * @throws H3DomainException
     */
    public static function latLngToCell(LatLng $latLng, int $resolution): int
    {
        if ($resolution < 0 || $resolution > Constants::MAX_H3_RES) {
            throw new H3ResolutionException('Invalid resolution');
        }

        if (!is_finite($latLng->getLatRadians()) || !is_finite($latLng->getLngRadians())) {
            throw new H3LatLngDomainException('Invalid lat/lng');
        }

        $vec3d = Vec3d::fromLatLng($latLng);
        return Vec3dConverter::vec3ToCell($vec3d, $resolution);
    }

    /**
     * Determines the spherical coordinates of the center point of an H3 index
     *
     * @param int $h3 The H3 index
     * @return LatLng The spherical coordinates of the H3 cell center
     * @throws H3IndexInvalidException
     */
    public static function cellToLatLng(int $h3): LatLng
    {
        return Vec3dConverter::vec3ToLatLng(
            H3IndexConverter::h3IndexToVec3d($h3),
        );
    }
}
