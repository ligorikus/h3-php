<?php

declare(strict_types=1);

namespace H3\ValueObject;

final readonly class Vec2d
{
    public function __construct(
        public float $x,
        public float $y,
    ) {}

    /**
     * Calculates the magnitude of a 2D cartesian vector.
     * @return float The magnitude of the vector
     */
    public function mag(): float
    {
        return sqrt($this->x * $this->x + $this->y * $this->y);
    }
}