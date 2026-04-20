<?php

declare(strict_types=1);

namespace H3\Internal;

use H3\ValueObject\LatLng;
use H3\InternalConstants as C;

final class CoordIJK
{
    public int $i = 0;
    public int $j = 0;
    public int $k = 0;

    public function __construct(int $i = 0, int $j = 0, int $k = 0)
    {
        $this->i = $i;
        $this->j = $j;
        $this->k = $k;
    }

    public function toIJ(): array
    {
        return ['i' => $this->i, 'j' => $this->i + $this->j];
    }

    public function add(self $other): self
    {
        return new self(
            $this->i + $other->i,
            $this->j + $other->j,
            $this->k + $other->k
        );
    }

    public function neighbor(int $direction): self
    {
        return match ($direction) {
            0 => new self(0, 0, 0),
            1 => new self(0, 0, 1),
            2 => new self(0, 1, 0),
            3 => new self(0, 1, 1),
            4 => new self(1, 0, 0),
            5 => new self(1, 0, 1),
            6 => new self(1, 1, 0),
            default => new self(),
        };
    }

    public function rotate60ccw(): self
    {
        $i = $this->i;
        $j = $this->j;
        $k = $this->k;

        return new self(-$k, $i, $j);
    }

    public function rotate60cw(): self
    {
        $i = $this->i;
        $j = $this->j;
        $k = $this->k;

        return new self($j, $k, $i);
    }

    public static function fromVec2d(float $x, float $y): self
    {
        $ijk = new self();

        $a1 = abs($x);
        $a2 = abs($y);

        $x2 = $a2 * C::INV_SQRT3;
        $x1 = $a1 + $x2 / 2.0;

        $m1 = (int)$x1;
        $m2 = (int)$x2;

        $r1 = $x1 - $m1;
        $r2 = $x2 - $m2;

        if ($r1 < 0.5) {
            if ($r1 < 1/3) {
                if ($r2 < (1.0 + $r1) / 2.0) {
                    $ijk->i = $m1;
                    $ijk->j = $m2;
                } else {
                    $ijk->i = $m1;
                    $ijk->j = $m2 + 1;
                }
            } else {
                if ($r2 < (1.0 - $r1)) {
                    $ijk->j = $m2;
                } else {
                    $ijk->j = $m2 + 1;
                }

                if ((1.0 - $r1) <= $r2 && $r2 < (2.0 * $r1)) {
                    $ijk->i = $m1 + 1;
                } else {
                    $ijk->i = $m1;
                }
            }
        } else {
            if ($r1 < 2/3) {
                if ($r2 < (1.0 - $r1)) {
                    $ijk->j = $m2;
                } else {
                    $ijk->j = $m2 + 1;
                }

                if ((2.0 * $r1 - 1.0) < $r2 && $r2 < (1.0 - $r1)) {
                    $ijk->i = $m1;
                } else {
                    $ijk->i = $m1 + 1;
                }
            } else {
                if ($r2 < ($r1 / 2.0)) {
                    $ijk->i = $m1 + 1;
                    $ijk->j = $m2;
                } else {
                    $ijk->i = $m1 + 1;
                    $ijk->j = $m2 + 1;
                }
            }
        }

        if ($x < 0.0) {
            $axisi = ($ijk->j % 2 === 0)
                ? $ijk->j / 2
                : ($ijk->j + 1) / 2;
            $diff = $ijk->i - $axisi;
            $ijk->i = (int)($ijk->i - 2.0 * $diff);
        }

        if ($y < 0.0) {
            $ijk->i = $ijk->i - (2 * $ijk->j + 1) / 2;
            $ijk->j = -$ijk->j;
        }

        $ijk->normalize();

        return $ijk;
    }

    public function toVec2d(): array
    {
        $j = $this->j;
        $i = $this->i + ($j + ($j % 2)) / 2;
        $x = $i - ($j + ($j % 2)) / 2;
        $y = $j * C::SQRT3_2;

        return ['x' => $x, 'y' => $y];
    }

    public function normalize(): void
    {
        $sum = $this->i + $this->j + $this->k;

        if ($sum > 0 && ($this->i < 0 || $this->j < 0 || $this->k < 0)) {
            $this->i = $this->i - (int)((($this->i < 0) ? -1 : 0) + $sum - 1) / $sum;
            $this->j = $this->j - (int)((($this->j < 0) ? -1 : 0) + $sum - 1) / $sum;
            $this->k = $this->k - (int)((($this->k < 0) ? -1 : 0) + $sum - 1) / $sum;
        } elseif ($sum < 0) {
            $this->i = $this->i - (int)((($this->i > 0) ? -1 : 0) - $sum - 1) / -$sum;
            $this->j = $this->j - (int)((($this->j > 0) ? -1 : 0) - $sum - 1) / -$sum;
            $this->k = $this->k - (int)((($this->k > 0) ? -1 : 0) - $sum - 1) / -$sum;
        }
    }
}