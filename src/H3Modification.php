<?php

declare(strict_types=1);

namespace H3;

use H3\Enum\Direction;

final class H3Modification
{
    private const H3_MODE_OFFSET = 59;
    private const H3_MODE_MASK = 15 << self::H3_MODE_OFFSET;
    private const H3_MODE_MASK_NEGATIVE = ~self::H3_MODE_MASK;
    private const H3_RES_OFFSET = 52;
    private const H3_RES_MASK = 15 << self::H3_RES_OFFSET;
    private const H3_RES_MASK_NEGATIVE = ~self::H3_RES_MASK;
    private const H3_BC_OFFSET = 45;
    private const H3_BC_MASK = 127 << self::H3_BC_OFFSET;
    private const H3_BC_MASK_NEGATIVE = ~self::H3_BC_MASK;
    private const H3_DIGIT_MASK = 7;
    private const H3_PER_DIGIT_OFFSET = 3;

    public static function h3Mode(int $h3, H3IndexMode $v): int
    {
        return ($h3 & self::H3_MODE_MASK_NEGATIVE) | ($v->value << self::H3_MODE_OFFSET);
    }

    public static function h3Resolution(int $h3, int $resolution): int
    {
        return ($h3 & self::H3_RES_MASK_NEGATIVE) | ($resolution << self::H3_RES_OFFSET);
    }

    public static function h3SetBaseCell(int $h3, int $bc): int
    {
        return ($h3 & self::H3_BC_MASK_NEGATIVE) | ($bc << self::H3_BC_OFFSET);
    }

    public static function h3SetIndexDigit(int $h3, int $resolution, int $digit): int
    {
        return (
            ($h3 & ~((self::H3_DIGIT_MASK << ((Constants::MAX_H3_RES - $resolution) * self::H3_PER_DIGIT_OFFSET))))
            | ($digit << ((Constants::MAX_H3_RES - $resolution) * self::H3_PER_DIGIT_OFFSET))
        );
    }

    public static function h3GetResolution(int $h3): int
    {
        return ($h3 & self::H3_RES_MASK) >> self::H3_RES_OFFSET;
    }

    public static function h3GetIndexDigit(int $h3, $resolution): int
    {
        return ($h3 >> ((Constants::MAX_H3_RES - $resolution) * self::H3_PER_DIGIT_OFFSET)) & self::H3_DIGIT_MASK;
    }

    public static function h3Rotate60cw(int $h3): int
    {
        $res = self::h3GetResolution($h3);
        for ($r = 1; $r <= $res; $r++) {
            $oldDigit = self::h3GetIndexDigit($h3, $r);
            $h3 = self::h3SetIndexDigit($h3, $r, Direction::rotate60cw($oldDigit));
        }

        return $h3;
    }

    public static function h3Rotate60ccw(int $h3): int
    {
        $res = self::h3GetResolution($h3);
        for ($r = 1; $r <= $res; $r++) {
            $oldDigit = self::h3GetIndexDigit($h3, $r);
            $h3 = self::h3SetIndexDigit($h3, $r, Direction::rotate60ccw($oldDigit));
        }

        return $h3;
    }

    public static function h3LeadingNonZeroDigit(int $h3): int
    {
        for ($r = 1; $r <= self::h3GetResolution($h3); $r++) {
            if (self::h3GetIndexDigit($h3, $r)) {
                return self::h3GetIndexDigit($h3, $r);
            }
        }
        return Direction::CENTER_DIGIT;
    }
}