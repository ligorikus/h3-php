<?php

declare(strict_types=1);

namespace H3;

use H3\Exception\H3DomainException;
use H3\Exception\H3IndexInvalidException;
use H3\Exception\H3LatLngDomainException;
use H3\Exception\H3ResolutionException;
use H3\Internal\Converter\H3IndexConverter;
use H3\Internal\Converter\Vec3dConverter;
use H3\Internal\Indexing;
use H3\Type\CellBoundary;
use H3\Type\CoordIJ;
use H3\Type\LatLng;
use http\Exception\BadMethodCallException;

/**
 * Public entry point for library consumers.
 * @psalm-api
 *
 * @phpstan-type H3Index int
 */
final class H3
{
    /** Maximum number of cell boundary vertices; worst case is pentagon:
     *  5 original verts + 5 edge crossings
     */
    public const MAX_CELL_BNDRY_VERTS = 10;

    /** ========================================================================================== */

    /** INDEXING BLOCK */

    /**
     * Encodes a coordinate on the sphere to the H3 index of the containing cell at
     * the specified resolution.
     *
     * @param LatLng $latLng The spherical coordinates to encode.
     * @param int $resolution The desired H3 resolution for the encoding.
     * @return H3Index The encoded H3Index.
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     * @throws H3DomainException
     */
    public static function latLngToCell(LatLng $latLng, int $resolution): int
    {
        return Indexing::latLngToCell($latLng, $resolution);
    }

    /**
     * Determines the spherical coordinates of the center point of an H3 index
     *
     * @param H3Index $h3 The H3 index
     * @return LatLng The spherical coordinates of the H3 cell center
     * @throws H3IndexInvalidException
     * @throws H3DomainException
     * @throws H3ResolutionException
     */
    public static function cellToLatLng(int $h3): LatLng
    {
        return Vec3dConverter::vec3ToLatLng(
            H3IndexConverter::h3IndexToVec3d($h3),
        );
    }

    /**
     * Determines the cell boundary in spherical coordinates for an H3 index
     *
     * @param H3Index $h3 The H3 index
     * @return CellBoundary The boundary of the H3 cell in spherical coordinates
     */
    public static function cellToBoundary(int $h3): CellBoundary
    {
        throw new \BadMethodCallException('H3 cellToBoundary is not supported');
    }

    /** INDEXING BLOCK ENDS */

    /** ========================================================================================== */

    /** INSPECTION BLOCK */

    /**
     * Returns the H3 resolution of an H3 index
     *
     * @param H3Index $h3 The H3 index
     * @return int The resolution of the H3 index argument
     */
    public static function getResolution(int $h3): int
    {
        throw new \BadMethodCallException('H3 getResolution is not supported');
    }

    /**
     * Returns the H3 base cell "number" of an H3 cell (hexagon or pentagon).
     *
     *  Note: Technically works on H3 edges, but will return base cell of the
     *  origin cell.
     *
     * @param H3Index $h3 The H3 cell
     * @return int The base cell "number" of the H3 cell argument
     */
    public static function getBaseCellNumber(int $h3): int
    {
        throw new \BadMethodCallException('H3 getBaseCellNumber is not supported');
    }

    /**
     * Returns the index digit at `res`, which starts with 1 for resolution
     *  1, up to and including resolution 15.
     *
     *  0 is not a valid value for `res` because resolution 0 is specified by
     *  the base cell number, not an indexing digit.
     *
     *  `res` may exceed the actual resolution of the index, in which case
     *  the actual digit stored in the index is returned. For valid cell indexes
     *  this will be 7.
     *
     * @param H3Index $h3 The H3 index (e.g. cell)
     * @param int $resolution Which indexing digit to retrieve, starting with 1
     * @return int Receives the value of the indexing digit
     */
    public static function getIndexDigit(int $h3, int $resolution): int
    {
        throw new \BadMethodCallException('H3 getIndexDigit is not supported');
    }

    /**
     * Create a cell from its components (resolution, base cell, children digits).
     * Only allows for constructing valid H3 cells.
     *
     * @param int $resolution 0--15
     * @param int $baseCellNumber 0--121
     * @param array $digits Array of child digits (0--6) of length `res`.
     *                 EMPTY allowed for `res=0`.
     * @return int Created cell
     */
    public static function constructCell(int $resolution, int $baseCellNumber, array $digits): int
    {
        throw new \BadMethodCallException('H3 constructCell is not supported');
    }

    /**
     * Converts a string representation of an H3 index into an H3 index
     *
     * @param string $str The string representation of an H3 index
     * @return H3Index Output: The H3 index corresponding to the string argument
     */
    public static function stringToH3(string $str): int
    {
        throw new \BadMethodCallException('H3 stringToH3 is not supported');
    }

    /**
     * Converts an H3 index into a string representation
     *
     * @param H3Index $h3 The H3 index to convert
     * @return string The string representation of the H3 index
     */
    public static function h3ToString(int $h3): string
    {
        throw new \BadMethodCallException('H3 h3ToString is not supported');
    }

    /**
     * Returns whether or not an H3 index is a valid cell (hexagon or pentagon)
     *
     * @param H3Index $h3 The H3 index to validate
     * @return bool true if the H3 index if valid, and false if it is not
     */
    public static function isValidCell(int $h3): bool
    {
        throw new \BadMethodCallException('H3 isValidCell is not supported');
    }

    /**
     * Returns whether or not an H3 index is valid for any mode (cell, directed
     *  edge, or vertex).
     *
     * @param H3Index $h3 The H3 index to validate
     * @return bool true if the H3 index is valid for any supported type, false otherwise
     */
    public static function isValidIndex(int $h3): bool
    {
        throw new \BadMethodCallException('H3 isValidIndex is not supported');
    }

    /**
     * isResClassIII takes a hexagon ID and determines if it is in a
     *  Class III resolution (rotated versus the icosahedron and subject
     *  to shape distortion adding extra points on icosahedron edges, making
     *  them not true hexagons).
     *
     * @param H3Index $h3 The H3Index to check
     * @return bool Returns true if the hexagon is class III, otherwise false
     */
    public static function isResClassIII(int $h3): bool
    {
        throw new \BadMethodCallException('H3 isResClassIII is not supported');
    }

    /**
     * isPentagon takes an H3Index and determines if it is actually a pentagon
     *
     * @param H3Index $h3 The H3Index to check
     * @return bool Returns true if it is a pentagon, otherwise false
     */
    public static function isPentagon(int $h3): bool
    {
        throw new \BadMethodCallException('H3 isPentagon is not supported');
    }

    /**
     *  Find all icosahedron faces intersected by a given H3 index, represented
     *  as integers from 0-19. The array is sparse; since 0 is a valid value,
     *  invalid array values are represented as -1. It is the responsibility of
     *  the caller to filter out invalid values.
     *
     * @param H3Index $h3 The H3 index
     * @return int[] Output array. Must be of size maxFaceCount(h3)
     */
    public static function getIcosahedronFaces(int $h3): array
    {
        throw new \BadMethodCallException('H3 getIcosahedronFaces is not supported');
    }

    /**
     * Returns the max number of possible icosahedron faces an H3 index
     * may intersect.
     *
     * @param H3Index $h3 The H3 index
     * @return int int count of faces
     */
    public static function maxFaceCount(int $h3): int
    {
        throw new \BadMethodCallException('H3 maxFaceCount is not supported');
    }

    /** INSPECTION BLOCK ENDS */

    /** ========================================================================================== */

    /** TRAVERSAL BLOCK */

    /**
     * Produces the grid distance between the two indexes.
     *
     * This function may fail to find the distance between two indexes, for
     * example if they are very far apart. It may also fail when finding
     * distances for indexes on opposite sides of a pentagon.
     *
     * @param H3Index $origin Index to find the distance from
     * @param H3Index $index Index to find the distance to.
     * @return int The distance in cells
     */
    public static function gridDistance(int $origin, int $index): int
    {
        throw new \BadMethodCallException('H3 gridDistance is not supported');
    }

    /**
     * Returns the "hollow" ring of hexagons at exactly grid distance k from
     *  the origin hexagon. In particular, k=0 returns just the origin hexagon.
     *
     *  Elements of the output array may be left zero, as can happen when crossing a
     *  pentagon.
     *
     * @param H3Index $origin Origin location
     * @param int $k k >= 0
     * @return H3Index[] Array which must be of size 6 * k (or 1 if k == 0)
     */
    public static function gridRing(int $origin, int $k): array
    {
        throw new \BadMethodCallException('H3 gridRing is not supported');
    }

    /**
     * Returns the "hollow" ring of hexagons at exactly grid distance k from
     *  the origin hexagon. In particular, k=0 returns just the origin hexagon.
     *
     *  A nonzero failure code may be returned in some cases, for example,
     *  if a pentagon is encountered.
     *  Failure cases may be fixed in future versions.
     *
     * @param H3Index $origin Origin location
     * @param int $k k >= 0
     * @return H3Index[] Array which must be of size 6 * k (or 1 if k == 0)
     */
    public static function gridRingUnsafe(int $origin, int $k): array
    {
        throw new \BadMethodCallException('H3 gridRingUnsafe is not supported');
    }

    /**
     * Maximum number of cells that result from the gridRing algorithm with the
     * given k
     *
     * @param int $k k value, k >= 0
     * @return int size in indexes
     */
    public static function maxGridRingSize(int $k): int
    {
        throw new \BadMethodCallException('H3 maxGridRingSize is not supported');
    }

    /**
     * Produce cells within grid distance k of the origin cell.
     *
     *  k-ring 0 is defined as the origin cell, k-ring 1 is defined as k-ring 0 and
     *  all neighboring cells, and so on.
     *
     *  Output is placed in the provided array in no particular order. Elements of
     *  the output array may be left zero, as can happen when crossing a pentagon.
     *
     * @param H3Index $origin origin cell
     * @param int $k k >= 0
     * @return H3Index[] zero-filled array which must be of size maxGridDiskSize(k)
     */
    public static function gridDisk(int $origin, int $k): array
    {
        throw new \BadMethodCallException('H3 gridDisk is not supported');
    }

    /**
     * Maximum number of cells that result from the gridDisk algorithm with the
     * given k. Formula source and proof: https://oeis.org/A003215
     *
     * @param int $k k value, k >= 0
     * @return int size of indexes
     */
    public static function maxGridDiskSize(int $k): int
    {
        throw new \BadMethodCallException('H3 maxGridDiskSize is not supported');
    }

    /**
     * Produce cells and their distances from the given origin cell, up to
     *  distance k.
     *
     *  k-ring 0 is defined as the origin cell, k-ring 1 is defined as k-ring 0 and
     *  all neighboring cells, and so on.
     *
     *  Output is placed in the provided array in no particular order. Elements of
     *  the output array may be left zero, as can happen when crossing a pentagon.
     *
     * @param H3Index $origin origin cell
     * @param int $k k >= 0
     * @return array{H3Index?: int} empty array or a zero-filled array which
     *                     must be of size  maxGridDiskSize(k)
     *                     mapped H3Index key to distance
     */
    public static function gridDiskDistances(int $origin, int $k): array
    {
        throw new \BadMethodCallException('H3 gridDiskDistances is not supported');
    }

    /**
     * gridDiskUnsafe produces indexes within k distance of the origin index.
     *  Output behavior is undefined when one of the indexes returned by this
     *  function is a pentagon or is in the pentagon distortion area.
     *
     *  k-ring 0 is defined as the origin index, k-ring 1 is defined as k-ring 0 and
     *  all neighboring indexes, and so on.
     *
     *  Output is placed in the provided array in order of increasing distance from
     *  the origin.
     *
     * @param int $origin Origin location
     * @param int $k k >= 0
     * @return H3Index[] Array which must be of size maxGridDiskSize(k)
     */
    public static function gridDiskUnsafe(int $origin, int $k): array
    {
        throw new \BadMethodCallException('H3 gridDiskUnsafe is not supported');
    }

    /**
     * gridDiskDistancesUnsafe produces indexes within k distance of the origin
     *  index. Output behavior is undefined when one of the indexes returned by this
     *  function is a pentagon or is in the pentagon distortion area.
     *
     *  k-ring 0 is defined as the origin index, k-ring 1 is defined as k-ring 0 and
     *  all neighboring indexes, and so on.
     *
     *  Output is placed in the provided array in order of increasing distance from
     *  the origin. The distances in hexagons is placed in the distances array at
     *  the same offset.
     *
     * @param int $origin Origin location
     * @param int $k k >= 0
     * @return array{H3Index?: int}  Null or array which must be of size maxGridDiskSize(k)
     */
    public static function gridDiskDistancesUnsafe(int $origin, int $k): array
    {
        throw new BadMethodCallException('H3 gridDiskDistancesUnsafe is not supported');
    }

    /**
     * Safe but slow version of gridDiskDistances (also called by it when needed).
     *
     *  Adds the origin cell to the output set (treating it as a hash set)
     *  and recurses to its neighbors, if needed.
     *
     * @param int $origin Origin cell
     * @param int $k Maximum distance to move from the origin
     * @return array{H3Index?: int} Array treated as a hash set, elements being either
     *                      H3Index or 0
     *                      Elements indicate ijk distance from the origin cell to
     *                      the output cell
     */
    public static function gridDiskDistancesSafe(int $origin, int $k): array
    {
        throw new \BadMethodCallException('H3 gridDiskDistancesSafe is not supported');
    }

    /**
     * gridDisksUnsafe takes an array of input hex IDs and a max k-ring and returns
     *  an array of hexagon IDs sorted first by the original hex IDs and then by the
     *  k-ring (0 to max), with no guaranteed sorting within each k-ring group.
     *
     * @param H3Index[] $h3Set An array of H3Indexes
     * @param int $length The total number of H3Indexes in h3Set
     * @param int $k The number of rings to generate
     * @return H3Index[] The new set of H3Indexes to
     *             The memory block should be equal to maxGridDiskSize(k) * length
     */
    public static function gridDisksUnsafe(array $h3Set, int $length, int $k): array
    {
        throw new \BadMethodCallException('H3 gridDisksUnsafe is not supported');
    }

    /**
     * Given two H3 indexes, return the line of indexes between them (inclusive).
     *
     *  This function relies on `gridDistance(start, end)` to determine the expected
     *  path length, and will return the same error if `gridDistance` fails.
     *
     *  Path construction is performed by straight-line interpolation in the
     *  origin-anchored local IJK coordinate space:
     *
     *   - First, interpolate using `start` as the local IJK origin.
     *   - If interpolation fails, retry using `end` as the local IJK origin and
     *     reverse the resulting sequence into `out`.
     *
     *  If both interpolation attempts fail, this function returns the error from the
     *  first attempt.
     *
     *  Notes:
     *
     *   - The specific output of this function should not be considered stable
     *     across library versions. The only guarantees the library provides are
     *     that the line length will be `gridDistance(start, end) + 1` and that
     *     every index in the line will be a neighbor of the preceding index.
     *   - Lines are drawn in grid space, and may not correspond exactly to either
     *     Cartesian lines or great arcs
     *
     * @param H3Index $start Start index of the line
     * @param H3Index $end End index of the line
     * @return H3Index[] Output array, which must be of size gridPathCellsSize(start, end)
     */
    public static function gridPathCells(int $start, int $end): array
    {
        throw new \BadMethodCallException('H3 gridPathCells is not supported');
    }

    /**
     * Number of indexes in a line from the start index to the end index,
     *  to be used for allocating memory.
     *
     *  On success, sets `*size` to `gridDistance(start, end) + 1`
     *  (including both endpoints)
     *
     * @param H3Index $start Start index of the line
     * @param H3Index $end End index of the line
     * @return int Output size of the line
     */
    public static function gridPathCellsSize(int $start, int $end): int
    {
        throw new \BadMethodCallException('H3 gridPathCellsSize is not supported');
    }

    /**
     * Produces ij coordinates for an index anchored by an origin.
     *
     *  The coordinate space used by this function may have deleted
     *  regions or warping due to pentagonal distortion.
     *
     *  Coordinates are only comparable if they come from the same
     *  origin index.
     *
     *  Failure may occur if the index is too far away from the origin
     *  or if the index is on the other side of a pentagon.
     *
     *  This function's output is not guaranteed
     *  to be compatible across different versions of H3
     *
     * @param H3Index $origin An anchoring index for the ij coordinate system
     * @param H3Index $index Index to find the coordinates of
     * @param int $mode Mode, must be 0
     * @return CoordIJ ij coordinates of the index will be placed here on success
     */
    public static function cellToLocalIj(int $origin, int $index, int $mode): CoordIJ
    {
        throw new \BadMethodCallException('H3 cellToLocalIj is not supported');
    }

    /**
     * Produces an index for ij coordinates anchored by an origin.
     *
     *  The coordinate space used by this function may have deleted
     *  regions or warping due to pentagonal distortion.
     *
     *  Failure may occur if the index is too far away from the origin
     *  or if the index is on the other side of a pentagon.
     *
     *  This function's output is not guaranteed
     *  to be compatible across different versions of H3
     *
     * @param H3Index $origin An anchoring index for the ij coordinate system
     * @param CoordIJ $ij ij coordinates to index
     * @param int $mode Mode, must be 0
     * @return H3Index Index will be placed here on success
     */
    public static function localIjToCell(int $origin, CoordIJ $ij, int $mode): int
    {
        throw new \BadMethodCallException('H3 localIjToCell is not supported');
    }

    /** TRAVERSAL BLOCK ENDS */

    /** ========================================================================================== */

    /** HIERARCHY BLOCK */

    /**
     * cellToParent produces the parent index for a given H3 index
     *
     * @param H3Index $h3 H3Index to find parent of
     * @param int $parentResolution The resolution to switch to (parent, grandparent, etc)
     * @return H3Index Output: H3Index of the parent
     */
    public static function cellToParent(int $h3, int $parentResolution): int
    {
        throw new \BadMethodCallException('H3 cellToParent is not supported');
    }

    /**
     * cellToChildren takes the given hexagon id and generates all of the children
     *  at the specified resolution storing them into the provided memory pointer.
     *  It's assumed that cellToChildrenSize was used to determine the allocation.
     *
     * @param H3Index $h3
     * @param int $childResolution
     * @return H3Index[]
     */
    public static function cellToChildren(int $h3, int $childResolution): array
    {
        throw new \BadMethodCallException('H3 cellToChildren is not supported');
    }

    /**
     * cellToChildrenSize returns the exact number of children for a cell at a
     *  given child resolution.
     *
     * @param H3Index $h3 H3Index to find the number of children of
     * @param int $childResolution The child resolution you're interested in
     * @return int Output: exact number of children (handles hexagons and
     *  pentagons correctly)
     */
    public static function cellToChildrenSize(int $h3, int $childResolution): int
    {
        throw new \BadMethodCallException('H3 cellToChildrenSize is not supported');
    }

    /**
     * cellToCenterChild produces the center child index for a given H3 index at
     *  the specified resolution
     *
     * @param H3Index $h3 H3Index to find center child of
     * @param int $childResolution The resolution to switch to
     * @return H3Index H3Index of the center child
     */
    public static function cellToCenterChild(int $h3, int $childResolution): int
    {
        throw new \BadMethodCallException('H3 cellToCenterChild is not supported');
    }

    /**
     * Returns the position of the cell within an ordered list of all children of
     *  the cell's parent at the specified resolution
     *
     * @param int $h3 Child cell index
     * @param int $parentResolution Resolution of the parent cell to find the position within
     * @return int Output: The position of the child cell within its parents cell
     *  list of children
     */
    public static function cellToChildPos(int $h3, int $parentResolution): int
    {
        throw new \BadMethodCallException('H3 cellToChildPos is not supported');
    }

    /**
     * Returns the child cell at a given position within an ordered list of all
     *  children at the specified resolution
     *
     * @param int $childPos
     * @param H3Index $parent
     * @param int $childResolution
     * @return H3Index
     */
    public static function childPosToCell(int $childPos, int $parent, int $childResolution): int
    {
        throw new \BadMethodCallException('H3 childPosToCell is not supported');
    }

    /**
     * compactCells takes a set of hexagons all at the same resolution and
     *  compresses them by pruning full child branches to the parent level. This is
     *  also done for all parents recursively to get the minimum number of hex
     *  addresses that perfectly cover the defined space
     *
     * @param H3Index[] $h3Set Set of hexagons
     * @param int $numHexes The size of the input and output arrays (possible that no
     *  contiguous regions exist in the set at all and no compression possible)
     * @return H3Index[] The output array of compressed hexagons
     */
    public static function compactCells(array $h3Set, int $numHexes): array
    {
        throw new \BadMethodCallException('H3 compactCells is not supported');
    }

    /**
     * uncompactCells takes a compressed set of cells and expands back to the
     *  original set of cells.
     *
     *  Skips elements that are H3_NULL (i.e., 0).
     *
     * @param H3Index[] $h3Set Set of compacted cells
     * @param int $numCompacted The number of cells in the input compacted set
     * @param int $numOut The size of the output array to bound check against
     * @param int $resolution The H3 resolution to decompress to
     * @return H3Index[] Output array for decompressed cells
     */
    public static function uncompactCells(
        array $h3Set,
        int $numCompacted,
        int $numOut,
        int $resolution,
    ): array {
        throw new \BadMethodCallException('H3 uncompactCells is not supported');
    }

    /**
     * uncompactCellsSize takes a compacted set of hexagons and provides
     *  the exact size of the uncompacted set of hexagons
     *
     * @param H3Index[] $compactedSet Set of hexagons
     * @param int $numCompacted The number of hexes in the input set
     * @param int $resolution The hexagon resolution to decompress to
     * @return int The number of hexagons to allocate memory for
     */
    public static function uncompactCellsSize(array $compactedSet, int $numCompacted, int $resolution): int
    {
        throw new \BadMethodCallException('H3 uncompactCellsSize is not supported');
    }

    /** HIERARCHY BLOCK ENDS */

    /** ========================================================================================== */

    /** REGIONS BLOCK */

    public static function polygonToCells()
    {
        // TODO
    }

    public static function maxPolygonToCellsSize()
    {
        // TODO
    }

    public static function polygonToCellsExperimental()
    {
        // TODO
    }

    public static function maxPolygonToCellsSizeExperimental()
    {
        // TODO
    }

    public static function cellsToLinkedMultiPolygon()
    {
        // TODO
    }

    public static function cellsToMultiPolygon()
    {
        // TODO
    }

    public static function destroyLinkedMultiPolygon()
    {
        // TODO
    }
    /** REGIONS BLOCK ENDS */

    /** DIRECTED EDGES BLOCK */

    public static function areNeighborCells()
    {
        // TODO
    }

    public static function cellsToDirectedEdge()
    {
        // TODO
    }

    public static function isValidDirectedEdge()
    {
        // TODO
    }

    public static function getDirectedEdgeOrigin()
    {
        // TODO
    }

    public static function getDirectedEdgeDestination()
    {
        // TODO
    }

    public static function directedEdgeToCells()
    {
        // TODO
    }

    public static function originToDirectedEdges()
    {
        // TODO
    }

    public static function directedEdgeToBoundary()
    {
        // TODO
    }

    public static function reverseDirectedEdge()
    {
        // TODO
    }
    /** DIRECTED EDGES BLOCK ENDS */

    /** VERTEXES BLOCK */

    public static function cellToVertex()
    {
        // TODO
    }

    public static function cellToVertexes()
    {
        // TODO
    }

    public static function vertexToLatLng()
    {
        // TODO
    }

    public static function isValidVertex()
    {
        // TODO
    }
    /** VERTEXES BLOCK ENDS */

    /** MISCELLANEOUS BLOCK */

    public static function degsToRads()
    {
        // TODO
    }

    public static function radsToDegs()
    {
        // TODO
    }

    public static function getHexagonAreaAvgKm2()
    {
        // TODO
    }

    public static function getHexagonAreaAvgM2()
    {
        // TODO
    }

    public static function cellAreaRads2()
    {
        // TODO
    }

    public static function cellAreaKm2()
    {
        // TODO
    }

    public static function cellAreaM2()
    {
        // TODO
    }

    public static function getHexagonEdgeLengthAvgKm()
    {
        // TODO
    }

    public static function getHexagonEdgeLengthAvgM()
    {
        // TODO
    }

    public static function edgeLengthKm()
    {
        // TODO
    }

    public static function edgeLengthM()
    {
        // TODO
    }

    public static function edgeLengthRads()
    {
        // TODO
    }

    public static function getNumCells()
    {
        // TODO
    }

    public static function getRes0Cells()
    {
        // TODO
    }

    public static function res0CellCount()
    {
        // TODO
    }

    public static function getPentagons()
    {
        // TODO
    }

    public static function pentagonCount()
    {
        // TODO
    }

    public static function greatCircleDistanceKm()
    {
        // TODO
    }

    public static function greatCircleDistanceM()
    {
        // TODO
    }

    public static function greatCircleDistanceRads()
    {
        // TODO
    }

    public static function describeH3Error()
    {
        // TODO
    }
    /** MISCELLANEOUS BLOCK ENDS */
}
