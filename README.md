# h3-php

Pure PHP implementation of [H3](https://h3geo.org/) - Uber's hexagonal hierarchical geospatial indexing system.

## Installation

```bash
composer require ligorikus/h3-php
```

## Requirements

- PHP 8.2+

## Development Approach

The library's implementation is developed without large language models (LLMs).
LLMs are used only for documentation and writing tests.

## Development

Use PHP 8.2.27 or newer to run the development tools. Composer resolves the
locked dependencies against PHP 8.2.27 so they also install on PHP 8.2.

```bash
composer install
composer test
```

CI checks the following compatible combinations:

| PHP | PHPUnit |
| --- | --- |
| 8.2 | 11 |
| 8.3 | 11, 12 |
| 8.4 | 11, 12, 13 |
| 8.5 | 11, 12, 13 |

The matrix resolves dependencies for each actual PHP version. A separate PHP 8.2
job validates and installs `composer.lock`, audits dependencies, runs tests,
PHPStan, and Psalm.

## Quick Start

```php
<?php

use H3\H3;
use H3\ValueObject\LatLng;

$latLng = new LatLng(37.7749, -122.4194);
$resolution = 9;

$cell = H3::latLngToCell($latLng, $resolution);

echo dechex($cell) . "\n"; // 89283082803ffff

$center = H3::cellToLatLng($cell);

printf("Latitude: %.6f, Longitude: %.6f\n", $center->lat, $center->lng);
// Latitude: 37.773515, Longitude: -122.418271
```

`H3::cellToLatLng(int $h3): LatLng` returns the center of an H3 cell as a
`LatLng` object. `getLat()` and `getLng()` return coordinates in degrees.
The cell center may differ from the coordinates originally passed to `latLngToCell`.
H3 indexes are passed as integers; use `hexdec('89283082803ffff')` to convert a
hexadecimal H3 index string before calling `cellToLatLng`.

## API implementation status

Functions are grouped and ordered as in the [official H3 4.x API reference](https://h3geo.org/docs/api/indexing/),
including experimental functions and C memory-management helpers.

🟢 Implemented · 🔴 Not implemented

Status refers to the API exposed by `H3\H3`. Low-level helpers such as
`H3Modification::h3GetResolution()` and `H3Modification::h3GetIndexDigit()`
are available internally but do not expose the corresponding official API methods.

<details>
<summary>Indexing</summary>

[Official documentation](https://h3geo.org/docs/api/indexing/)

- 🟢 `latLngToCell`
- 🟢 `cellToLatLng`
- 🔴 `cellToBoundary`

</details>

<details>
<summary>Inspection</summary>

[Official documentation](https://h3geo.org/docs/api/inspection/)

- 🔴 `getResolution`
- 🔴 `getBaseCellNumber`
- 🔴 `getIndexDigit`
- 🔴 `constructCell`
- 🔴 `stringToH3`
- 🔴 `h3ToString`
- 🔴 `isValidCell`
- 🔴 `isValidIndex`
- 🔴 `isResClassIII`
- 🔴 `isPentagon`
- 🔴 `getIcosahedronFaces`
- 🔴 `maxFaceCount`

</details>

<details>
<summary>Traversal</summary>

[Official documentation](https://h3geo.org/docs/api/traversal/)

- 🔴 `gridDistance`
- 🔴 `gridRing`
- 🔴 `gridRingUnsafe`
- 🔴 `maxGridRingSize`
- 🔴 `gridDisk`
- 🔴 `maxGridDiskSize`
- 🔴 `gridDiskDistances`
- 🔴 `gridDiskUnsafe`
- 🔴 `gridDiskDistancesUnsafe`
- 🔴 `gridDiskDistancesSafe`
- 🔴 `gridDisksUnsafe`
- 🔴 `gridPathCells`
- 🔴 `gridPathCellsSize`
- 🔴 `cellToLocalIj`
- 🔴 `localIjToCell`

</details>

<details>
<summary>Hierarchy</summary>

[Official documentation](https://h3geo.org/docs/api/hierarchy/)

- 🔴 `cellToParent`
- 🔴 `cellToChildren`
- 🔴 `cellToChildrenSize`
- 🔴 `cellToCenterChild`
- 🔴 `cellToChildPos`
- 🔴 `childPosToCell`
- 🔴 `compactCells`
- 🔴 `uncompactCells`
- 🔴 `uncompactCellsSize`

</details>

<details>
<summary>Regions</summary>

[Official documentation](https://h3geo.org/docs/api/regions/)

- 🔴 `polygonToCells`
- 🔴 `maxPolygonToCellsSize`
- 🔴 `polygonToCellsExperimental`
- 🔴 `maxPolygonToCellsSizeExperimental`
- 🔴 `cellsToLinkedMultiPolygon` / `cellsToMultiPolygon`
- 🔴 `destroyLinkedMultiPolygon`

</details>

<details>
<summary>Directed edges</summary>

[Official documentation](https://h3geo.org/docs/api/uniedge/)

- 🔴 `areNeighborCells`
- 🔴 `cellsToDirectedEdge`
- 🔴 `isValidDirectedEdge`
- 🔴 `getDirectedEdgeOrigin`
- 🔴 `getDirectedEdgeDestination`
- 🔴 `directedEdgeToCells`
- 🔴 `originToDirectedEdges`
- 🔴 `directedEdgeToBoundary`
- 🔴 `reverseDirectedEdge`

</details>

<details>
<summary>Vertexes</summary>

[Official documentation](https://h3geo.org/docs/api/vertex/)

- 🔴 `cellToVertex`
- 🔴 `cellToVertexes`
- 🔴 `vertexToLatLng`
- 🔴 `isValidVertex`

</details>

<details>
<summary>Miscellaneous</summary>

[Official documentation](https://h3geo.org/docs/api/misc/)

- 🔴 `degsToRads`
- 🔴 `radsToDegs`
- 🔴 `getHexagonAreaAvgKm2`
- 🔴 `getHexagonAreaAvgM2`
- 🔴 `cellAreaRads2`
- 🔴 `cellAreaKm2`
- 🔴 `cellAreaM2`
- 🔴 `getHexagonEdgeLengthAvgKm`
- 🔴 `getHexagonEdgeLengthAvgM`
- 🔴 `edgeLengthKm`
- 🔴 `edgeLengthM`
- 🔴 `edgeLengthRads`
- 🔴 `getNumCells`
- 🔴 `getRes0Cells`
- 🔴 `res0CellCount`
- 🔴 `getPentagons`
- 🔴 `pentagonCount`
- 🔴 `greatCircleDistanceKm`
- 🔴 `greatCircleDistanceM`
- 🔴 `greatCircleDistanceRads`
- 🔴 `describeH3Error`

</details>

## License

Apache License 2.0 - see [LICENSE](LICENSE)
