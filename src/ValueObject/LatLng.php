<?php

declare(strict_types=1);

namespace H3\ValueObject;

final readonly class LatLng
{
    public function __construct(
        public float $lat,
        public float $lng
    ) {}

    public function getLatRadians(): float
    {
        return deg2rad($this->lat);
    }

    public function getLngRadians(): float
    {
        return deg2rad($this->lng);
    }
}