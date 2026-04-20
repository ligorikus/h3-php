# h3-php

Pure PHP implementation of [H3](https://h3geo.org/) - Uber's hexagonal hierarchical geospatial indexing system.

## Installation

```bash
composer require ligorikus/h3-php
```

## Requirements

- PHP 8.3+

## Quick Start

```php
<?php

use H3\H3;
use H3\ValueObject\LatLng;

$latLng = new LatLng(37.7749, -122.4194); // San Francisco

$cell = H3::latLngToCell($latLng, 9);

echo $cell->toString(); // 8928342e20fffff

$boundary = H3::cellToBoundary($cell);

foreach ($boundary->toGeoLoop()->toLatLngs() as $latLng) {
    echo $latLng->lat() . ", " . $latLng->lng() . "\n";
}
```

## API Overview

### Cell Operations

```php
// Create cell from lat/lng
$cell = H3::latLngToCell($latLng, 9);

// Get cell coordinates
$latLng = H3::cellToLatLng($cell);

// Get cell boundary
$boundary = H3::cellToBoundary($cell);

// Cell hierarchy
$parent = H3::cellToParent($cell, 8);
$children = H3::cellToChildren($cell, 10);
```

### Grid Operations

```php
// Get cells within k distance
$cells = H3::gridDisk($origin, 5);

// Get ring of cells at k distance
$ring = H3::gridRing($origin, 2);

// Find path between cells
$path = H3::gridPath($start, $end);
```

### Polygon Operations

```php
// Get all cells in polygon
$cells = H3::polygonToCells($polygon, 9);

// Get GeoJSON multipolygon from cells
$multipolygon = H3::cellsToMultiPolygon($cells);
```

## License

Apache License 2.0 - see [LICENSE](LICENSE)