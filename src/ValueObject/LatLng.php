<?php

declare(strict_types=1);

namespace H3\ValueObject;

use H3\InternalConstants as C;

final class LatLng
{
    private float $lat;
    private float $lng;

    public function __construct(float $lat, float $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public static function fromDegrees(float $lat, float $lng): self
    {
        return new self($lat, $lng);
    }

    public static function fromRadians(float $latRad, float $lngRad): self
    {
        return new self(
            $latRad * C::PI_180_INV,
            $lngRad * C::PI_180_INV
        );
    }

    public function lat(): float
    {
        return $this->lat;
    }

    public function lng(): float
    {
        return $this->lng;
    }

    public function latRadians(): float
    {
        return $this->lat * C::PI_180;
    }

    public function lngRadians(): float
    {
        return $this->lng * C::PI_180;
    }

    public function isValid(): bool
    {
        return $this->lat >= -90 && $this->lat <= 90
            && $this->lng >= -180 && $this->lng <= 180;
    }

    public function __toString(): string
    {
        return sprintf('(%.5f, %.5f)', $this->lat, $this->lng);
    }
}