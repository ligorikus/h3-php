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

$latLng = new LatLng(57.900535, 55.910254);
$resolution = 9;

$cell = H3::latLngToCell($latLng, $resolution);

echo "H3 Index: " . dechex($cell) . "\n";
```

## License

Apache License 2.0 - see [LICENSE](LICENSE)