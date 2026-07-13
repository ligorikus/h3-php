<?php

namespace H3\Enum;

class Direction
{
    public const CENTER_DIGIT = 0;
    public const K_AXES_DIGIT = 1;
    public const J_AXES_DIGIT = 2;
    public const JK_AXES_DIGIT = self::J_AXES_DIGIT | self::K_AXES_DIGIT;
    public const I_AXES_DIGIT = 4;
    public const IK_AXES_DIGIT = self::I_AXES_DIGIT | self::K_AXES_DIGIT;
    public const IJ_AXES_DIGIT = self::I_AXES_DIGIT | self::J_AXES_DIGIT;
    public const INVALID_DIGIT = 7;
    public const PENTAGON_SKIPPED_DIGIT = self::K_AXES_DIGIT;

    public static function rotate60ccw(int $digit): int
    {
        switch ($digit) {
            case self::K_AXES_DIGIT:
                return self::IK_AXES_DIGIT;
            case self::IK_AXES_DIGIT:
                return self::I_AXES_DIGIT;
            case self::I_AXES_DIGIT:
                return self::IJ_AXES_DIGIT;
            case self::IJ_AXES_DIGIT:
                return self::J_AXES_DIGIT;
            case self::J_AXES_DIGIT:
                return self::JK_AXES_DIGIT;
            case self::JK_AXES_DIGIT:
                return self::K_AXES_DIGIT;
            default:
                return $digit;
        }
    }

    public static function rotate60cw(int $digit): int
    {
        switch ($digit) {
            case self::K_AXES_DIGIT:
                return self::JK_AXES_DIGIT;
            case self::JK_AXES_DIGIT:
                return self::J_AXES_DIGIT;
            case self::J_AXES_DIGIT:
                return self::IJ_AXES_DIGIT;
            case self::IJ_AXES_DIGIT:
                return self::I_AXES_DIGIT;
            case self::I_AXES_DIGIT:
                return self::IK_AXES_DIGIT;
            case self::IK_AXES_DIGIT:
                return self::K_AXES_DIGIT;
            default:
                return $digit;
        }
    }
}