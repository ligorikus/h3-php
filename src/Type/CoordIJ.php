<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * IJ hexagon coordinates
 * Each axis is spaced 120 degrees apart.
 */
final readonly class CoordIJ
{
    /**
     * @param int $i
     * @param int $j
     */
    public function __construct(
        public int $i,
        public int $j
    ) {}
}