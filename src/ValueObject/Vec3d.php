<?php

declare(strict_types=1);

namespace H3\ValueObject;

final readonly class Vec3d
{
    public function __construct(
        private float $x,
        private float $y,
        private float $z,
    ) {}

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function getZ(): float
    {
        return $this->z;
    }

    /**
     * Convert latitude and longitude to a unit Vec3d on the sphere.
     * @param LatLng $geo
     * @return self
     */
    public static function fromLatLng(LatLng $geo): self
    {
        $r = cos($geo->getLat());

        return new self(
            x: cos($geo->getLng()) * $r,
            y: sin($geo->getLng()) * $r,
            z: sin($geo->getLat()),
        );
    }

    /**
     * @param int[] $arr
     * @return self
     */
    public static function fromArray(array $arr): self
    {
        return new self(
            x: $arr[0],
            y: $arr[1],
            z: $arr[2],
        );
    }
}