<?php

declare(strict_types=1);

namespace H3\Type;

/**
 * A coordinate node in a linked geo structure, part of a linked list
 */
final readonly class LinkedLatLng
{
    /**
     * @param LatLng $latLng
     * @param LinkedLatLng|null $next
     */
    public function __construct(
        public LatLng $latLng,
        public ?LinkedLatLng $next = null,
    ) {}
}