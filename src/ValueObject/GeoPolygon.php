<?php

declare(strict_types=1);

namespace H3\ValueObject;

final class GeoPolygon
{
    public readonly GeoLoop $geoLoop;
    
    /** @var list<GeoLoop> */
    public readonly array $holes;

    public function __construct(GeoLoop $geoLoop, array $holes = [])
    {
        $this->geoLoop = $geoLoop;
        $this->holes = $holes;
    }

    public static function fromLatLngs(array $outer, array $holes = []): self
    {
        return new self(
            new GeoLoop($outer),
            array_map(fn(array $hole) => new GeoLoop($hole), $holes)
        );
    }
}