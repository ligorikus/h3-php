<?php

declare(strict_types=1);

namespace H3\ValueObject;

final class CoordIJ
{
    public function __construct(
        public int $i,
        public int $j
    ) {}

    public static function fromIndex(int $index): self
    {
        return new self(
            ($index >> 7) & 0x1FFF,
            $index & 0x7F
        );
    }

    public function toIndex(): int
    {
        return ($this->i << 7) | ($this->j & 0x7F);
    }

    public function __toString(): string
    {
        return (string)$this->toIndex();
    }
}
