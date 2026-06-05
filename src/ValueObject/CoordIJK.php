<?php

declare(strict_types=1);

namespace H3\ValueObject;

/**
 * IJK hexagon coordinates
 *
 * Each axis is spaced 120 degrees apart.
 */
final readonly class CoordIJK
{
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
        if ($k < $min) $k = $j;
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
}