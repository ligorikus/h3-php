<?php

declare(strict_types=1);

namespace H3\Enum;

/**
 * Digit representing overage type
 */
enum Overage: int
{
    /**
     * No overage (on original face)
     */
    case NO_OVERAGE = 0;
    /**
     * On face edge (only occurs on substrate grids)
     */
    case FACE_EDGE = 1;
    /**
     * Overage on new face interior
     */
    case NEW_FACE = 2;
}
