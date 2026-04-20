<?php

declare(strict_types=1);

namespace H3\Type;

use H3\InternalConstants;

final class Cell
{
    private int $index;

    /**
     * @param int $index H3 cell index
     */
    public function __construct(int $index)
    {
        $this->index = $index;
    }

    /**
     * Get the raw H3 index.
     *
     * @return int H3 index
     */
    public function index(): int
    {
        return $this->index;
    }

    /**
     * Create cell from index.
     *
     * @param int $index H3 index
     * @return self New cell instance
     */
    public static function fromIndex(int $index): self
    {
        return new self($index);
    }

    /**
     * Create cell from hex string.
     *
     * @param string $hex Hex string (with or without 0x prefix)
     * @return self|null Cell or null if invalid
     */
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

    /**
     * Check if cell is valid.
     *
     * @return bool True if valid
     */
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

    /**
     * Get cell resolution.
     *
     * @return int Resolution (0-15)
     */
    public function resolution(): int
    {
        return ($this->index >> InternalConstants::H3_RES_OFFSET) & 0xF;
    }

    /**
     * Get base cell number.
     *
     * @return int Base cell number (0-121)
     */
    public function baseCellNumber(): int
    {
        return ($this->index >> InternalConstants::H3_BC_OFFSET) & 0x7F;
    }

    /**
     * Check if cell is a pentagon.
     *
     * @return bool True if pentagon
     */
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

    /**
     * Get cell center latitude/longitude.
     *
     * @return \H3\ValueObject\LatLng Cell center coordinates
     */
    public function latLng(): \H3\ValueObject\LatLng
    {
        return \H3\H3::cellToLatLng($this);
    }

    /**
     * Get cell boundary.
     *
     * @return \H3\ValueObject\CellBoundary Cell boundary vertices
     */
    public function boundary(): \H3\ValueObject\CellBoundary
    {
        return \H3\H3::cellToBoundary($this);
    }

    /**
     * Get cells within grid distance k.
     *
     * @param int $k Grid distance
     * @return array Array of cells
     */
    public function gridDisk(int $k): array
    {
        return \H3\H3::gridDisk($this, $k);
    }

    /**
     * Get cells with distances from origin.
     *
     * @param int $k Grid distance
     * @return array Array of cell arrays with distances
     */
    public function gridDiskDistances(int $k): array
    {
        return \H3\H3::gridDiskDistances($this, $k);
    }

    /**
     * Get cells with distances (unsafe for pentagons).
     *
     * @param int $k Grid distance
     * @return array Array of cell arrays
     */
    public function gridDiskDistancesUnsafe(int $k): array
    {
        return \H3\H3::gridDiskDistancesUnsafe($this, $k);
    }

    /**
     * Get cells with distances (safe version).
     *
     * @param int $k Grid distance
     * @return array Array of cell arrays
     */
    public function gridDiskDistancesSafe(int $k): array
    {
        return \H3\H3::gridDiskDistancesSafe($this, $k);
    }

    /**
     * Get cells at exactly distance k (hollow ring).
     *
     * @param int $k Grid distance
     * @return array Array of cells
     */
    public function gridRing(int $k): array
    {
        return \H3\H3::gridRing($this, $k);
    }

    /**
     * Get hollow ring (unsafe for pentagons).
     *
     * @param int $k Grid distance
     * @return array Array of cells
     */
    public function gridRingUnsafe(int $k): array
    {
        return \H3\H3::gridRingUnsafe($this, $k);
    }

    /**
     * Get parent cell at given resolution.
     *
     * @param int $resolution Target resolution
     * @return Cell|null Parent cell
     */
    public function parent(int $resolution): ?self
    {
        return \H3\H3::cellToParent($this, $resolution);
    }

    /**
     * Get immediate parent cell.
     *
     * @return Cell|null Parent cell
     */
    public function immediateParent(): ?self
    {
        return \H3\H3::cellToImmediateParent($this);
    }

    /**
     * Get child cells at resolution.
     *
     * @param int $resolution Child resolution
     * @return array Array of child cells
     */
    public function children(int $resolution): array
    {
        return \H3\H3::cellToChildren($this, $resolution);
    }

    /**
     * Get immediate children (resolution + 1).
     *
     * @return array Array of child cells
     */
    public function immediateChildren(): array
    {
        return \H3\H3::cellToImmediateChildren($this);
    }

    /**
     * Get center child at resolution.
     *
     * @param int $resolution Target resolution
     * @return Cell|null Center child cell
     */
    public function centerChild(int $resolution): ?self
    {
        return \H3\H3::cellToCenterChild($this, $resolution);
    }

    /**
     * Check if cells are neighbors.
     *
     * @param Cell $other Other cell
     * @return bool True if neighbors
     */
    public function isNeighbor(self $other): bool
    {
        return \H3\H3::areNeighborCells($this, $other);
    }

    /**
     * Get index digit at resolution.
     *
     * @param int $resolution Resolution
     * @return int Index digit (0-6)
     */
    public function indexDigit(int $resolution): int
    {
        return \H3\H3::getIndexDigit($this->index, $resolution);
    }

    /**
     * Get directed edge to neighbor cell.
     *
     * @param Cell $other Neighbor cell
     * @return DirectedEdge|null Directed edge
     */
    public function directedEdge(self $other): ?\H3\Type\DirectedEdge
    {
        return \H3\H3::cellsToDirectedEdge($this, $other);
    }

    /**
     * Get all directed edges from cell.
     *
     * @return array Array of directed edges
     */
    public function directedEdges(): array
    {
        return \H3\H3::originToDirectedEdges($this);
    }

    /**
     * Get grid distance to other cell.
     *
     * @param Cell $other Target cell
     * @return int|null Distance or null if unreachable
     */
    public function gridDistance(self $other): ?int
    {
        return \H3\H3::gridDistance($this, $other);
    }

    /**
     * Get grid path to other cell.
     *
     * @param Cell $other Target cell
     * @return array Array of cells in path
     */
    public function gridPath(self $other): array
    {
        return \H3\H3::gridPath($this, $other);
    }

    /**
     * Get vertex by number.
     *
     * @param int $vertexNum Vertex number (0-4 or 0-5)
     * @return Vertex|null Vertex
     */
    public function vertex(int $vertexNum): ?\H3\Type\Vertex
    {
        return \H3\H3::cellToVertex($this, $vertexNum);
    }

    /**
     * Get all vertices of cell.
     *
     * @return array Array of vertices
     */
    public function vertexes(): array
    {
        return \H3\H3::cellToVertexes($this);
    }

    /**
     * Convert position to child cell.
     *
     * @param int $position Child position
     * @param int $resolution Target resolution
     * @return Cell|null Child cell
     */
    public function childPosToCell(int $position, int $resolution): ?self
    {
        return \H3\H3::childPosToCell($position, $this, $resolution);
    }

    /**
     * Get child position at resolution.
     *
     * @param int $resolution Resolution
     * @return int|null Child position
     */
    public function childPos(int $resolution): ?int
    {
        return \H3\H3::cellToChildPos($this, $resolution);
    }

    /**
     * Get icosahedron faces for cell.
     *
     * @return array Array of face indices
     */
    public function faces(): array
    {
        return \H3\H3::icosahedronFaces($this);
    }

    /**
     * Get string representation.
     *
     * @return string Hex string
     */
    public function string(): string
    {
        return '0x' . dechex($this->index);
    }

    /**
     * Get edge type.
     *
     * @return int Edge type
     */
    public function edgeType(): int
    {
        return \H3\H3::edgeType($this);
    }
}