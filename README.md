# h3-php

Pure PHP implementation of [H3](https://h3geo.org/) - Uber's hexagonal hierarchical geospatial indexing system.

## Installation

```bash
composer require ligorikus/h3-php
```

## Requirements

- PHP 8.2+

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

use H3\H3;
use H3\ValueObject\LatLng;

$latLng = new LatLng(37.7749, -122.4194);
$resolution = 9;

$cell = H3::latLngToCell($latLng, $resolution);

echo dechex($cell) . "\n"; // 89283082803ffff
```

## License

Apache License 2.0 - see [LICENSE](LICENSE)
