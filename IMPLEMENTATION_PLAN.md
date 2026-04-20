# php-h3 Implementation Plan

## Overview
This plan documents the implementation of missing functions in php-h3 to match the h3-go library API.

## Target: Match h3-go (pure PHP, no C extensions)

---

## Stage 1: Constants and Basic Classes

### 1.1 Add constants to InternalConstants.php

```php
public const MAX_CELL_BNDRY_VERTS = 10;

// ContainmentMode
public const CONTAINMENT_CENTER = 1;
public const CONTAINMENT_FULL = 2;
public const CONTAINMENT_OVERLAPPING = 3;
public const CONTAINMENT_OVERLAPPING_BBOX = 4;
public const CONTAINMENT_INVALID = 5;
```

### 1.2 Create ValueObject/CoordIJ.php

```php
<?php
declare(strict_types=1);
namespace H3\ValueObject;

final class CoordIJ {
    public function __construct(public int $i, public int $j) {}
    public static function fromIndex(int $index): self
    public function toIndex(): int
    public function __toString(): string
}
```

---

## Stage 2: Grid Functions

### 2.1 GridDiskDistances (H3.php)
- Returns `array[][]`, where `array[k] = [Cell, Cell, ...]` at distance k
- Signature: `public static function gridDiskDistances(Cell $origin, int $k): array`

### 2.2 GridDiskDistancesUnsafe (H3.php)
- Fast version without pentagon handling
- Signature: `public static function gridDiskDistancesUnsafe(Cell $origin, int $k): array`

### 2.3 GridDiskDistancesSafe (H3.php)
- Safe version for pentagons
- Signature: `public static function gridDiskDistancesSafe(Cell $origin, int $k): array`

### 2.4 GridDisksUnsafe (H3.php)
- Batch processing for multiple origins
- Signature: `public static function gridDisksUnsafe(array $origins, int $k): array`

### 2.5 GridRingUnsafe (H3.php)
- Fast version of gridRing
- Signature: `public static function gridRingUnsafe(Cell $origin, int $k): array`

---

## Stage 3: Local IJ Coordinates

### 3.1 CellToLocalIJ (H3.php)
- Returns IJ coordinates of cell relative to origin
- Signature: `public static function cellToLocalIJ(Cell $origin, Cell $cell): ?CoordIJ`

### 3.2 LocalIJToCell (H3.php)
- Reverse operation - get cell from IJ
- Signature: `public static function localIJToCell(Cell $origin, CoordIJ $ij): ?Cell`

---

## Stage 4: Child Position

### 4.1 ChildPosToCell (H3.php)
- Get child cell by position
- Signature: `public static function childPosToCell(int $position, Cell $cell, int $resolution): ?Cell`

### 4.2 CellToChildPos (H3.php)
- Get position of child cell
- Signature: `public static function cellToChildPos(Cell $cell, int $resolution): ?int`

---

## Stage 5: Vertex Class

### 5.1 Create Type/Vertex.php

```php
<?php
declare(strict_types=1);
namespace H3\Type;

use H3\InternalConstants;
use H3\ValueObject\LatLng;

final class Vertex {
    private int $index;
    
    public function __construct(int $index) { $this->index = $index; }
    public static function fromIndex(int $index): self
    public function index(): int
    public function __toString(): string  // hex string
    public function isValid(): bool
    public function resolution(): int
    public function indexDigit(int $resolution): int
    public function latLng(): ?LatLng
    public static function fromString(string $hex): ?self
}
```

### 5.2 Update H3.php - change return types
- `cellToVertex()` -> returns `?Vertex`
- `cellToVertexes()` -> returns `array of Vertex`
- `vertexToLatLng()` -> accepts `Vertex`
- `isValidVertex()` -> accepts `Vertex`

---

## Stage 6: DirectedEdge Class

### 6.1 Create Type/DirectedEdge.php

```php
<?php
declare(strict_types=1);
namespace H3\Type;

use H3\ValueObject\LatLng;
use H3\Type\Cell;

final class DirectedEdge {
    private int $index;
    
    public function __construct(int $index) { $this->index = $index; }
    public static function fromIndex(int $index): self
    public function index(): int
    public function __toString(): string
    public function isValid(): bool
    public function resolution(): int
    public function indexDigit(int $resolution): int
    public function origin(): ?Cell
    public function destination(): ?Cell
    public function cells(): array  // [origin, dest]
    public function boundary(): CellBoundary
    public static function fromString(string $hex): ?self
}
```

### 6.2 EdgeLength for DirectedEdge (H3.php)

```php
public static function edgeLengthRads(DirectedEdge $edge): float
public static function edgeLengthKm(DirectedEdge $edge): float
public static function edgeLengthM(DirectedEdge $edge): float
```

---

## Stage 7: Experimental Functions

### 7.1 ContainmentMode
Add constants to InternalConstants.php

### 7.2 PolygonToCellsExperimental (H3.php)

```php
public static function polygonToCellsExperimental(
    GeoPolygon $polygon, 
    int $resolution, 
    int $mode, 
    ?int $maxNumCells = null
): array
```

---

## Stage 8: Utilities

### 8.1 IsValidIndex (H3.php)
- Universal index validation
- Signature: `public static function isValidIndex(int $index): bool`

---

## File Structure After Implementation

```
php-h3/src/
├── H3.php                      # + GridDiskDistances*, LocalIJ*, ChildPos*, IsValidIndex
├── InternalConstants.php       # + ContainmentMode, MAX_CELL_BNDRY_VERTS
├── Type/
│   ├── Cell.php
│   ├── Vertex.php              # NEW
│   └── DirectedEdge.php        # NEW
└── ValueObject/
    ├── LatLng.php
    ├── GeoPolygon.php
    ├── GeoLoop.php
    ├── CellBoundary.php
    └── CoordIJ.php             # NEW
```

---

## Testing

After each function implementation, run:

```bash
cd /home/ligorikus/opensource/php-h3 && ./vendor/bin/phpunit
```

---

## Implementation Order

1. Stage 1: Constants and basic classes
2. Stage 2: Grid functions
3. Stage 3: Local IJ
4. Stage 4: Child Position
5. Stage 5: Vertex class
6. Stage 6: DirectedEdge class
7. Stage 7: Experimental functions
8. Stage 8: Utilities
