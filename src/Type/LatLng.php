<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * latitude/longitude in radians
 */
final readonly class LatLng
{
    /**
     * @param float $lat latitude in radians
     * @param float $lng longitude in radians
     */
    public function __construct(
        public float $lat,
        public float $lng
    ) {} // TODO lat&lng now is degrees, need reworks to radians for official API compatibility

    public function getLatRadians(): float
    {
        return deg2rad($this->lat);
    }

    public function getLngRadians(): float
    {
        return deg2rad($this->lng);
    }
}