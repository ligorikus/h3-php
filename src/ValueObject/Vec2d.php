<?php

declare(strict_types=1);

namespace H3\ValueObject;

final readonly class Vec2d
{
    public function __construct(
        private float $x,
        private float $y,
    ) {}

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }
}