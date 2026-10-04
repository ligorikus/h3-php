<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * A polygon node in a linked geo structure, part of a linked list
 */
final readonly class LinkedGeoPolygon
{
    /**
     * @param LinkedGeoLoop|null $first
     * @param LinkedGeoLoop|null $last
     * @param LinkedGeoPolygon|null $next
     */
    public function __construct(
        public ?LinkedGeoLoop     $first = null,
        public ?LinkedGeoLoop     $last = null,
        public ?LinkedGeoPolygon $next = null,
    ) {}
}