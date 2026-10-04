<?php

declare(strict_types=1);

namespace H3\Internal\Helper;

use H3\Internal\Constants;
use H3\Internal\FaceProjection;

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