<?php

declare(strict_types=1);

namespace H3\ValueObject;

final readonly class LatLng
{
    public function __construct(
        private float $lat,
        private float $lng
    ) {}

    public function getLat(): float
    {
        return deg2rad($this->lat);
    }

    public function getLng(): float
    {
        return deg2rad($this->lng);
    }
}