<?php

declare(strict_types=1);

namespace H3\ValueObject;

/**
 * Face number and ijk coordinates on that face-centered coordinate
 */
final readonly class FaceIJK
{
    public function __construct(
        private int $face, ///< face number
        private CoordIJK $coord, ///< ijk coordinates on that face
    ) {}

    public function getFace(): int
    {
        return $this->face;
    }

    public function getCoord(): CoordIJK
    {
        return $this->coord;
    }
}