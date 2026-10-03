<?php

declare(strict_types=1);

namespace H3\Internal\ValueObject;

use H3\Exception\H3DomainException;
use H3\Internal\FaceProjection;

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
     * @throws H3DomainException
     */
    public function getBaseCell(): array
    {
        $result = FaceProjection::FACE_IJK_BASE_CELLS[$this->face][$this->coord->getI()][$this->coord->getJ()][$this->coord->getK()] ?? null;
        if ($result === null) {
            throw new H3DomainException('Face and IJK coordinates do not identify a base cell');
        }

        return [
            'baseCell' => $result[0],
            'ccwRot60' => $result[1],
        ];
    }
}
