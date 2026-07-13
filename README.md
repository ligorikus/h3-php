# h3-php

Pure PHP implementation of [H3](https://h3geo.org/) - Uber's hexagonal hierarchical geospatial indexing system.

## Installation

```bash
composer require ligorikus/h3-php
```

## Requirements

- PHP 8.2+

## Quick Start

```php
<?php

use H3\H3;
use H3\ValueObject\LatLng;

use H3\H3;
use H3\ValueObject\LatLng;

$latLng = new LatLng(37.7749, -122.4194);
$resolution = 9;

$cell = H3::latLngToCell($latLng, $resolution);

echo dechex($cell) . "\n"; // 89283082803ffff
```

## License

Apache License 2.0 - see [LICENSE](LICENSE)