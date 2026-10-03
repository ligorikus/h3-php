<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * Simplified core of GeoJSON Polygon coordinates definition
 */
final readonly class GeoPolygon
{
    /**
     * @param GeoLoop $geoLoop exterior boundary of the polygon
     * @param int $numHoles number of elements in the array pointed to by holes
     * @param GeoLoop[] $holes interior boundaries (holes) in the polygon
     */
    public function __construct(
        public GeoLoop $geoLoop,
        public int $numHoles,
        public array $holes,
    ) {}
}