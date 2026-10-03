<?php

declare(strict_types=1);

namespace H3\Internal;

use H3\Exception\H3LatLngDomainException;
use H3\Exception\H3ResolutionException;
use H3\Exception\H3DomainException;
use H3\Exception\H3IndexInvalidException;
use H3\Internal\Converter\H3IndexConverter;
use H3\Internal\Converter\Vec3dConverter;
use H3\Internal\ValueObject\Vec3d;
use H3\Type\LatLng;

/**
 * @internal
 */
final readonly class Indexing
{
    /**
     * @param LatLng $latLng
     * @param int $resolution
     * @return int
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
     * @param int $h3
     * @return LatLng
     * @throws H3DomainException
     * @throws H3ResolutionException
     * @throws H3IndexInvalidException
     */
    public static function cellToLatLng(int $h3): LatLng
    {
        return Vec3dConverter::vec3ToLatLng(
            H3IndexConverter::h3IndexToVec3d($h3),
        );
    }
}