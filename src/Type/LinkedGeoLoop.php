<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * A loop node in a linked geo structure, part of a linked list
 */
final readonly class LinkedGeoLoop
{
    /**
     * @param LinkedLatLng|null $first
     * @param LinkedLatLng|null $last
     * @param LinkedGeoLoop|null $next
     */
    public function __construct(
        public ?LinkedLatLng $first = null,
        public ?LinkedLatLng $last = null,
        public ?LinkedGeoLoop $next = null,
    ) {}
}