<?php

declare(strict_types=1);

namespace H3\Enum;

/**
 * indexes for faceNeighbors table
 */
enum FaceNeighbors: int
{
    /**
     * IJ quadrant faceNeighbors table direction
     */
    case IJ = 1;
    /**
     * KI quadrant faceNeighbors table direction
     */
    case KI = 2;
    /**
     * JK quadrant faceNeighbors table direction
     */
    case JK = 3;
    /**
     * Invalid face index
     */
    case INVALID_FACE = -1;
}
