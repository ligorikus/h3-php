<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * similar to CellBoundary, but requires more alloc work
 */
final readonly class GeoLoop
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