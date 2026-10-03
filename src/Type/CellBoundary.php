<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * Cell boundary in latitude/longitude
 */
final readonly class CellBoundary
{
    /**
     * @param int $numVerts   number of vertices
     * @param LatLng[] $verts vertices in ccw order
     */
    public function __construct(
        public int $numVerts,
        public array $verts,
    ) {}
}