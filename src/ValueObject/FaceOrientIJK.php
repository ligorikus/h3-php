<?php

declare(strict_types=1);

namespace H3\ValueObject;

final readonly class FaceOrientIJK
{
    public function __construct(
        public int $face,
        public CoordIJK $translate,
        public int $ccwRot60,
    ) {}
}