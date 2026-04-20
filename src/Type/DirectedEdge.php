<?php

declare(strict_types=1);

namespace H3\Type;

use H3\InternalConstants;
use H3\ValueObject\LatLng;
use H3\ValueObject\CellBoundary;
use H3\H3;

final class DirectedEdge
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

        if ($index <= 0 || !self::isValidEdge($index)) {
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
        return self::isValidEdge($this->index);
    }

    public static function isValidEdge(int $index): bool
    {
        if ($index === 0) {
            return false;
        }
        $mode = ($index >> InternalConstants::H3_MODE_OFFSET) & 0xF;
        return $mode === InternalConstants::H3_DIRECTEDEDGE_MODE;
    }

    public static function isValidIndex(int $index): bool
    {
        return self::isValidEdge($index);
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

    public function origin(): ?Cell
    {
        return H3::getDirectedEdgeOrigin($this);
    }

    public function destination(): ?Cell
    {
        return H3::getDirectedEdgeDestination($this);
    }

    public function cells(): array
    {
        return H3::directedEdgeToCells($this);
    }

    public function boundary(): CellBoundary
    {
        return H3::directedEdgeToBoundary($this);
    }
}