<?php

declare(strict_types=1);

namespace H3\Internal\Converter;

use H3\Internal\Constants;
use H3\Internal\ValueObject\CoordIJK;
use H3\Internal\ValueObject\Vec2d;

final readonly class CoordIJKConverter
{
    /**
     * Find the center point in 2D cartesian coordinates of a hex
     *
     * @param CoordIJK $coordIJK The ijk coordinates of the hex
     * @return Vec2d The 2D cartesian coordinates of the hex center point
     */
    public static function coordIJKToVec2d(CoordIJK $coordIJK): Vec2d
    {
        $i = (float) ($coordIJK->getI() - $coordIJK->getK());
        $j = (float) ($coordIJK->getJ() - $coordIJK->getK());

        return new Vec2d(
            x: $i - 0.5 * $j,
            y: $j * Constants::M_SQRT3_2,
        );
    }
}
