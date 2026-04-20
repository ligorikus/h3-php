<?php

declare(strict_types=1);

namespace H3\Type;

use H3\InternalConstants;
use H3\ValueObject\LatLng;
use H3\H3;

final class Vertex
{
    private int $index;

    public function __construct(int $index)
    {
        $this->index = $index;
    }

    public static function fromIndex(int $index): self
    {
        return new self($index);
    }

    public function index(): int
    {
        return $this->index;
    }

    private static function hexToInt(string $hex): int
    {
        $hex = strtolower($hex);
        return intval($hex, 16);
    }

    private static function intToHex(int $num): string
    {
        return '0x' . dechex($num);
    }

    public static function fromString(string $hex): ?self
    {
        $hex = str_starts_with(strtolower($hex), '0x')
            ? substr($hex, 2)
            : $hex;

        $index = self::hexToInt($hex);

        if ($index <= 0 || !self::isValidIndex($index)) {
            return null;
        }

        return new self($index);
    }

    public function __toString(): string
    {
        return self::intToHex($this->index);
    }

    public function isValid(): bool
    {
        return self::isValidIndex($this->index);
    }

    public static function isValidVertex(int $index): bool
    {
        return ($index >> InternalConstants::H3_MODE_OFFSET & 0xF) === InternalConstants::H3_VERTEX_MODE;
    }

    public static function isValidIndex(int $index): bool
    {
        if ($index === 0) {
            return false;
        }

        if (!self::isValidVertex($index)) {
            return false;
        }

        $vertexNum = ($index >> InternalConstants::H3_RESERVED_OFFSET) & 0xF;
        $owner = $index;
        $owner &= ~(0xF << InternalConstants::H3_MODE_OFFSET);
        $owner &= ~(0xF << InternalConstants::H3_RESERVED_OFFSET);
        $owner |= (InternalConstants::H3_CELL_MODE << InternalConstants::H3_MODE_OFFSET);
        $owner |= (1 << InternalConstants::H3_MAX_OFFSET);

        $ownerCell = Cell::fromIndex($owner);
        if ($ownerCell === null || !$ownerCell->isValid()) {
            return false;
        }

        $cellIsPentagon = $ownerCell->isPentagon();
        $cellNumVerts = $cellIsPentagon ? 5 : 6;

        if ($vertexNum < 0 || $vertexNum >= $cellNumVerts) {
            return false;
        }

        return true;
    }

    public function resolution(): int
    {
        return ($this->index >> InternalConstants::H3_RES_OFFSET) & 0xF;
    }

    public function indexDigit(int $resolution): int
    {
        $digitOffset = InternalConstants::H3_PER_DIGIT_OFFSET * (InternalConstants::MAX_RESOLUTION - $resolution + 1);
        return ($this->index >> $digitOffset) & 0x7;
    }

    public function latLng(): ?LatLng
    {
        return H3::vertexToLatLng($this);
    }

    public function toCell(): ?Cell
    {
        return H3::vertexToCell($this);
    }

    public function string(): string
    {
        return self::intToHex($this->index);
    }
}
