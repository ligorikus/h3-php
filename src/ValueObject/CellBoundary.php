<?php

declare(strict_types=1);

namespace H3\ValueObject;

final class CellBoundary
{
    /** @var list<LatLng> */
    public readonly array $vertices;

    /** @param LatLng[] $vertices */
    public function __construct(array $vertices)
    {
        $this->vertices = $vertices;
    }

    public function count(): int
    {
        return \count($this->vertices);
    }
}