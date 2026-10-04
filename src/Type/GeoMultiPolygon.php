<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * Simplified core of GeoJSON MultiPolygon coordinates definition
 */
final readonly class GeoMultiPolygon
{
    /**
     * @param int $numPolygons
     * @param GeoPolygon[] $polygons
     */
    public function __construct(
        public int $numPolygons,
        public array $polygons,
    ) {}
}