<?php

declare(strict_types=1);

namespace H3\ValueObject;

use H3\Enum\Direction;

/**
 * IJK hexagon coordinates
 *
 * Each axis is spaced 120 degrees apart.
 */
final readonly class CoordIJK
{
    private const UNIT_VECS  = [
        [0, 0, 0], // direction 0
        [0, 0, 1], // direction 1
        [0, 1, 0], // direction 2
        [0, 1, 1], // direction 3
        [1, 0, 0], // direction 4
        [1, 0, 1], // direction 5
        [1, 1, 0], // direction 6
    ];

    public function __construct(
        private int $i, ///< i component
        private int $j, ///< j component
        private int $k, ///< k component
    ) {}

    public function getI(): int
    {
        return $this->i;
    }

    public function getJ(): int
    {
        return $this->j;
    }

    public function getK(): int
    {
        return $this->k;
    }

    public function normalize(): self
    {
        $i = $this->i;
        $j = $this->j;
        $k = $this->k;

        if ($i < 0) {
            $j -= $i;
            $k -= $i;
            $i = 0;
        }

        if ($j < 0) {
            $i -= $j;
            $k -= $j;
            $j = 0;
        }

        if ($k < 0) {
            $i -= $k;
            $j -= $k;
            $k = 0;
        }

        $min = $i;
        if ($j < $min) $min = $j;
        if ($k < $min) $min = $k;
        if ($min > 0) {
            $i -= $min;
            $j -= $min;
            $k -= $min;
        }

        return new self($i, $j, $k);
    }

    public function scale(int $factor): self
    {
        return new self(
            i: $this->i * $factor,
            j: $this->j * $factor,
            k: $this->k * $factor,
        );
    }

    public function add(CoordIJK $ijk): self
    {
        return new self(
            i: $this->i + $ijk->getI(),
            j: $this->j + $ijk->getJ(),
            k: $this->k + $ijk->getK(),
        );
    }

    public function sub(CoordIJK $ijk): self
    {
        return new self(
            i: $this->i - $ijk->getI(),
            j: $this->j - $ijk->getJ(),
            k: $this->k - $ijk->getK(),
        );
    }

    public function toDigit(): int
    {
        $c = $this->normalize();
        $digit = Direction::INVALID_DIGIT;
        for ($i = 0; $i < 7; $i++) {
            $unitVec = self::UNIT_VECS[$i];
            if ($c->matches(new self($unitVec[0], $unitVec[1], $unitVec[2]))) {
                $digit = $i;
                break;
            }
        }
        return $digit;
    }

    public function matches(CoordIJK $c): bool
    {
        return ($this->getI() === $c->getI() && $this->getJ() === $c->getJ() && $this->getK() === $c->getK());
    }
}