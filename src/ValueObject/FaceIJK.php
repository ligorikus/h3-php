<?php

declare(strict_types=1);

namespace H3\ValueObject;

use H3\FaceProjection;

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

    /**
     * @return array{baseCell: int, ccwRot60: int}
     */
    public function getBaseCell(): array
    {
        $result = FaceProjection::FACE_IJK_BASE_CELLS[$this->face][$this->getCoord()->getI()][$this->getCoord()->getJ()][$this->getCoord()->getK()];
        return [
            'baseCell' => $result[0],
            'ccwRot60' => $result[1],
        ];
    }
}