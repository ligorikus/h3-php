<?php

declare(strict_types=1);

namespace H3;

final class H3Modification
{
    private const H3_MODE_OFFSET = 59;
    private const H3_MODE_MASK = 15 << self::H3_MODE_OFFSET;
    private const H3_MODE_MASK_NEGATIVE = ~self::H3_MODE_MASK;
    private const H3_RES_OFFSET = 52;
    private const H3_RES_MASK = 15 << self::H3_RES_OFFSET;
    private const H3_RES_MASK_NEGATIVE = ~self::H3_RES_MASK;

    public static function h3Mode(int $h3, H3IndexMode $v): int
    {
        return ((($h3)&self::H3_MODE_MASK_NEGATIVE) | (($v->value) << self::H3_MODE_OFFSET));
    }

    public static function h3Resolution(int $h3, int $resolution): int
    {
        return ((($h3)&self::H3_RES_MASK_NEGATIVE) | (($resolution) << self::H3_RES_OFFSET));
    }
}