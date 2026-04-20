<?php

declare(strict_types=1);

namespace H3\Type;

use H3\InternalConstants;

final class Cell
{
    private int $index;

    public function __construct(int $index)
    {
        $this->index = $index;
    }

    public function index(): int
    {
        return $this->index;
    }

    public static function fromIndex(int $index): self
    {
        return new self($index);
    }

    public static function fromString(string $hex): ?self
    {
        $hex = str_starts_with(strtolower($hex), '0x')
            ? substr($hex, 2)
            : $hex;

        $index = self::hexToInt($hex);

        if ($index === 0 || ! self::isValidCell($index)) {
            return null;
        }

        return new self($index);
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

    public function __toString(): string
    {
        return self::intToHex($this->index);
    }

    public function isValid(): bool
    {
        return $this->index !== InternalConstants::H3_NULL
            && self::isValidCell($this->index);
    }

    public static function isValidCell(int $index): bool
    {
        $mode = ($index >> InternalConstants::H3_MODE_OFFSET) & 0xF;
        return $mode === InternalConstants::H3_CELL_MODE;
    }

    public function resolution(): int
    {
        return ($this->index >> InternalConstants::H3_RES_OFFSET) & 0xF;
    }

    public function baseCellNumber(): int
    {
        return ($this->index >> InternalConstants::H3_BC_OFFSET) & 0x7F;
    }

    public function isPentagon(): bool
    {
        $baseCell = $this->baseCellNumber();
        return \in_array($baseCell, [4, 14, 24, 38, 49, 58, 63, 72, 83, 97, 107, 117], true);
    }

    public function isResClassIII(): bool
    {
        $res = $this->resolution();
        return $res % 2 !== 0;
    }

    public function latLng(): \H3\ValueObject\LatLng
    {
        return \H3\H3::cellToLatLng($this);
    }

    public function boundary(): \H3\ValueObject\CellBoundary
    {
        return \H3\H3::cellToBoundary($this);
    }

    public function gridDisk(int $k): array
    {
        return \H3\H3::gridDisk($this, $k);
    }

    public function gridDiskDistances(int $k): array
    {
        return \H3\H3::gridDiskDistances($this, $k);
    }

    public function gridDiskDistancesUnsafe(int $k): array
    {
        return \H3\H3::gridDiskDistancesUnsafe($this, $k);
    }

    public function gridDiskDistancesSafe(int $k): array
    {
        return \H3\H3::gridDiskDistancesSafe($this, $k);
    }

    public function gridRing(int $k): array
    {
        return \H3\H3::gridRing($this, $k);
    }

    public function gridRingUnsafe(int $k): array
    {
        return \H3\H3::gridRingUnsafe($this, $k);
    }

    public function parent(int $resolution): ?self
    {
        return \H3\H3::cellToParent($this, $resolution);
    }

    public function immediateParent(): ?self
    {
        return \H3\H3::cellToImmediateParent($this);
    }

    public function children(int $resolution): array
    {
        return \H3\H3::cellToChildren($this, $resolution);
    }

    public function immediateChildren(): array
    {
        return \H3\H3::cellToImmediateChildren($this);
    }

    public function centerChild(int $resolution): ?self
    {
        return \H3\H3::cellToCenterChild($this, $resolution);
    }

    public function isNeighbor(self $other): bool
    {
        return \H3\H3::areNeighborCells($this, $other);
    }

    public function indexDigit(int $resolution): int
    {
        return \H3\H3::getIndexDigit($this->index, $resolution);
    }

    public function directedEdge(self $other): ?\H3\Type\DirectedEdge
    {
        return \H3\H3::cellsToDirectedEdge($this, $other);
    }

    public function directedEdges(): array
    {
        return \H3\H3::originToDirectedEdges($this);
    }

    public function gridDistance(self $other): ?int
    {
        return \H3\H3::gridDistance($this, $other);
    }

    public function gridPath(self $other): array
    {
        return \H3\H3::gridPath($this, $other);
    }

    public function vertex(int $vertexNum): ?\H3\Type\Vertex
    {
        return \H3\H3::cellToVertex($this, $vertexNum);
    }

    public function vertexes(): array
    {
        return \H3\H3::cellToVertexes($this);
    }

    public function childPosToCell(int $position, int $resolution): ?self
    {
        return \H3\H3::childPosToCell($position, $this, $resolution);
    }

    public function childPos(int $resolution): ?int
    {
        return \H3\H3::cellToChildPos($this, $resolution);
    }

    public function faces(): array
    {
        return \H3\H3::icosahedronFaces($this);
    }
}