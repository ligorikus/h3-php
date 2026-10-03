<?php

declare(strict_types=1);

namespace H3\Enum;

/**
 * Values representing polyfill containment modes, to be used in
 * the `flags` bit field for `polygonToCellsExperimental`.
 */
enum ContainmentMode : int
{
    /**
     * Cell center is contained in the shape
     */
    case CONTAINMENT_CENTER = 0;
    /**
     * Cell is fully contained in the shape
     */
    case CONTAINMENT_FULL = 1;
    /**
     * Cell overlaps the shape at any point
     */
    case CONTAINMENT_OVERLAPPING = 2;
    /**
     * Cell bounding box overlaps shape
     */
    case CONTAINMENT_OVERLAPPING_BBOX = 3;
    /**
     * This mode is invalid and should not be used
     */
    case CONTAINMENT_INVALID = 4;
}
