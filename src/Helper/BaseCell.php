<?php

declare(strict_types=1);

namespace H3\Helper;

use H3\Constants;
use H3\FaceProjection;

final readonly class BaseCell
{
    /**
     * Return whether or not the indicated base cell is a pentagon
     *
     * @param int $baseCell
     * @return bool
     */
    public static function isBaseCellPentagon(int $baseCell): bool
    {
        if ($baseCell < 0 || $baseCell >= Constants::NUM_BASE_CELLS) {
            return false;
        }

        return FaceProjection::BASE_CELL_DATA[$baseCell][1] === 1;
    }
}