<?php

declare(strict_types=1);

use H3\FaceProjection;
use H3\H3;
use H3\ValueObject\LatLng;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

const DEFAULT_IMAGE = 'h3-php-research:h3-4.5.0';
const DEFAULT_SEED = 1592594996;
const MAX_FAILURE_EXAMPLES = 20;

final class Oracle
{
    /** @var resource */
    private $process;

    /** @var array<int, resource> */
    private array $pipes;

    public function __construct(string $image)
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open(['docker', 'run', '--rm', '--interactive', $image], $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start the H3 oracle container.');
        }

        $this->process = $process;
        $this->pipes = $pipes;
    }

    /** @return array<string, string> */
    public function metadata(): array
    {
        $this->write("meta\n");
        $metadata = [];
        while (($line = $this->readLine()) !== 'end') {
            [$key, $value] = explode("\t", $line, 2);
            $metadata[$key] = $value;
        }

        return $metadata;
    }

    /**
     * @param list<array{lat: float, lng: float, resolution: int, category: string}> $cases
     * @return list<array{error: int, index: string}>
     */
    public function latLngToCell(array $cases): array
    {
        $payload = 'latlng ' . count($cases) . "\n";
        foreach ($cases as $case) {
            $payload .= sprintf("%.17g %.17g %d\n", $case['lat'], $case['lng'], $case['resolution']);
        }
        $this->write($payload);

        $results = [];
        foreach ($cases as $_) {
            [$error, $index] = explode(' ', $this->readLine(), 2);
            $results[] = ['error' => (int) $error, 'index' => $index];
        }

        return $results;
    }

    /**
     * @param list<string> $indexes
     * @return list<array{error: int, lat: float, lng: float}>
     */
    public function cellToLatLng(array $indexes): array
    {
        $this->write('cell ' . count($indexes) . "\n" . implode("\n", $indexes) . "\n");

        $results = [];
        foreach ($indexes as $_) {
            $parts = explode(' ', $this->readLine());
            $results[] = [
                'error' => (int) $parts[0],
                'lat' => (float) $parts[1],
                'lng' => (float) $parts[2],
            ];
        }

        return $results;
    }

    /** @return list<array{index: string, lat: float, lng: float}> */
    public function pentagons(int $resolution): array
    {
        $this->write("pentagons {$resolution}\n");
        $results = [];
        for ($i = 0; $i < 12; $i++) {
            $parts = explode(' ', $this->readLine());
            if ((int) $parts[0] !== 0) {
                throw new RuntimeException("Official getPentagons failed at resolution {$resolution}.");
            }
            $results[] = ['index' => $parts[1], 'lat' => (float) $parts[2], 'lng' => (float) $parts[3]];
        }

        return $results;
    }

    /**
     * @param list<string> $indexes
     * @return list<list<array{lat: float, lng: float}>>
     */
    public function boundaries(array $indexes): array
    {
        $this->write('boundary ' . count($indexes) . "\n" . implode("\n", $indexes) . "\n");
        $results = [];
        foreach ($indexes as $index) {
            $parts = explode(' ', $this->readLine());
            if ((int) array_shift($parts) !== 0) {
                throw new RuntimeException("Official cellToBoundary failed for {$index}.");
            }
            $vertexCount = (int) array_shift($parts);
            $vertices = [];
            for ($vertex = 0; $vertex < $vertexCount; $vertex++) {
                $vertices[] = [
                    'lat' => (float) $parts[$vertex * 2],
                    'lng' => (float) $parts[$vertex * 2 + 1],
                ];
            }
            $results[] = $vertices;
        }

        return $results;
    }

    /** @param callable(string, string, float, float): void $consume */
    public function eachUpstreamVector(callable $consume): void
    {
        $this->write("vectors\n");
        while (($line = $this->readLine()) !== 'end') {
            [$file, $index, $lat, $lng] = explode(' ', $line);
            $consume($file, $index, (float) $lat, (float) $lng);
        }
    }

    /**
     * @param list<array{lat: float, lng: float, resolution: int, category: string}> $cases
     * @return array{raw: list<int>, degrees: list<int>}
     */
    public function benchmarkLatLng(array $cases, int $warmups, int $repeats): array
    {
        $payload = sprintf("bench-latlng %d %d %d\n", count($cases), $warmups, $repeats);
        foreach ($cases as $case) {
            $payload .= sprintf("%.17g %.17g %d\n", $case['lat'], $case['lng'], $case['resolution']);
        }
        $this->write($payload);

        $timings = ['raw' => [], 'degrees' => []];
        while (($line = $this->readLine()) !== 'end') {
            [$kind, $elapsed] = explode(' ', $line, 3);
            $timings[$kind][] = (int) $elapsed;
        }

        return $timings;
    }

    /**
     * @param list<string> $indexes
     * @return list<int>
     */
    public function benchmarkCells(array $indexes, int $warmups, int $repeats): array
    {
        $payload = sprintf("bench-cell %d %d %d\n", count($indexes), $warmups, $repeats);
        $payload .= implode("\n", $indexes) . "\n";
        $this->write($payload);

        $timings = [];
        while (($line = $this->readLine()) !== 'end') {
            [, $elapsed] = explode(' ', $line, 3);
            $timings[] = (int) $elapsed;
        }

        return $timings;
    }

    public function close(): void
    {
        if (!isset($this->pipes)) {
            return;
        }
        @fwrite($this->pipes[0], "quit\n");
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        $stderr = stream_get_contents($this->pipes[2]);
        fclose($this->pipes[2]);
        $exitCode = proc_close($this->process);
        unset($this->pipes);

        if ($exitCode !== 0) {
            throw new RuntimeException("H3 oracle exited with code {$exitCode}: {$stderr}");
        }
    }

    public function __destruct()
    {
        if (isset($this->pipes)) {
            $this->close();
        }
    }

    private function write(string $payload): void
    {
        $length = strlen($payload);
        $written = 0;
        while ($written < $length) {
            $result = fwrite($this->pipes[0], substr($payload, $written));
            if ($result === false || $result === 0) {
                throw new RuntimeException('Cannot write to the H3 oracle.');
            }
            $written += $result;
        }
        fflush($this->pipes[0]);
    }

    private function readLine(): string
    {
        $line = fgets($this->pipes[1]);
        if ($line === false) {
            $stderr = stream_get_contents($this->pipes[2]);
            throw new RuntimeException("Unexpected end of H3 oracle output: {$stderr}");
        }

        return rtrim($line, "\r\n");
    }
}

final class Lcg
{
    public function __construct(private int $state) {}

    public function unit(): float
    {
        $this->state = (int) ((1103515245 * $this->state + 12345) % 2147483648);
        return $this->state / 2147483648;
    }
}

final class Results
{
    /** @var array<string, array<string, array{cases: int, failures: int, skipped: int, max_error_deg: float}>> */
    public array $stats = [];

    /** @var list<string> */
    public array $failures = [];

    public function pass(string $method, string $category, float $errorDegrees = 0.0): void
    {
        $this->init($method, $category);
        $this->stats[$method][$category]['cases']++;
        $this->stats[$method][$category]['max_error_deg'] = max(
            $this->stats[$method][$category]['max_error_deg'],
            $errorDegrees,
        );
    }

    public function fail(string $method, string $category, string $message, float $errorDegrees = 0.0): void
    {
        if (str_contains($message, 'operation skipped')) {
            $this->skip($method, $category);
            return;
        }
        $this->pass($method, $category, $errorDegrees);
        $this->stats[$method][$category]['failures']++;
        if (count($this->failures) < MAX_FAILURE_EXAMPLES) {
            $this->failures[] = "{$method} / {$category}: {$message}";
        }
        if (str_contains($message, 'timed out')) {
            fwrite(STDERR, "Timeout: {$method} / {$category}: {$message}\n");
        }
    }

    public function skip(string $method, string $category): void
    {
        $this->init($method, $category);
        $this->stats[$method][$category]['skipped']++;
    }

    public function failureCount(): int
    {
        $count = 0;
        foreach ($this->stats as $categories) {
            foreach ($categories as $stat) {
                $count += $stat['failures'];
            }
        }

        return $count;
    }

    private function init(string $method, string $category): void
    {
        $this->stats[$method][$category] ??= ['cases' => 0, 'failures' => 0, 'skipped' => 0, 'max_error_deg' => 0.0];
    }
}

final class OperationTimedOut extends RuntimeException {}

final class Watchdog
{
    private const MAX_TIMEOUTS_PER_METHOD = 3;

    /** @var array<string, int> */
    private static array $timeouts = [];

    private static bool $initialized = false;

    /** @template T @param callable(): T $operation @return T */
    public static function run(string $method, callable $operation): mixed
    {
        if ((self::$timeouts[$method] ?? 0) >= self::MAX_TIMEOUTS_PER_METHOD) {
            throw new RuntimeException('operation skipped after repeated timeouts');
        }
        if (!function_exists('pcntl_alarm') || !function_exists('pcntl_signal')) {
            return $operation();
        }

        if (!self::$initialized) {
            pcntl_async_signals(true);
            pcntl_signal(SIGALRM, static function (): never {
                throw new OperationTimedOut('operation timed out after 1 second');
            });
            self::$initialized = true;
        }

        pcntl_alarm(1);
        try {
            return $operation();
        } catch (OperationTimedOut $exception) {
            self::$timeouts[$method] = (self::$timeouts[$method] ?? 0) + 1;
            throw $exception;
        } finally {
            pcntl_alarm(0);
        }
    }
}

/** @return array<string, string|bool> */
function parseOptions(array $arguments): array
{
    $options = [];
    foreach ($arguments as $argument) {
        if (!str_starts_with($argument, '--')) {
            throw new InvalidArgumentException("Unexpected argument: {$argument}");
        }
        $option = substr($argument, 2);
        if (str_contains($option, '=')) {
            [$key, $value] = explode('=', $option, 2);
            $options[$key] = $value;
        } else {
            $options[$option] = true;
        }
    }

    return $options;
}

/** @return array{exit: int, stdout: string, stderr: string} */
function runCommand(array $command): array
{
    $process = proc_open($command, [
        0 => ['file', '/dev/null', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot run: ' . implode(' ', $command));
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['exit' => proc_close($process), 'stdout' => trim($stdout), 'stderr' => trim($stderr)];
}

function ensureImage(string $image, bool $rebuild): void
{
    $docker = runCommand(['docker', 'version']);
    if ($docker['exit'] !== 0) {
        throw new RuntimeException('Docker is required: ' . $docker['stderr']);
    }

    $inspect = runCommand(['docker', 'image', 'inspect', $image]);
    if ($inspect['exit'] === 0 && !$rebuild) {
        return;
    }

    fwrite(STDERR, "Building pinned official H3 oracle image {$image}...\n");
    $directory = __DIR__;
    $process = proc_open(
        ['docker', 'build', '--tag', $image, '--file', $directory . '/Dockerfile', $directory],
        [0 => STDIN, 1 => STDOUT, 2 => STDERR],
        $pipes,
    );
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Cannot build the H3 oracle image.');
    }
}

/** @return Generator<int, array{lat: float, lng: float, resolution: int, category: string}> */
function randomCases(int $count, int $seed, string $category = 'random'): Generator
{
    $random = new Lcg($seed);
    for ($i = 0; $i < $count; $i++) {
        $z = 2.0 * $random->unit() - 1.0;
        yield [
            'lat' => rad2deg(asin($z)),
            'lng' => 360.0 * $random->unit() - 180.0,
            'resolution' => $i % 16,
            'category' => $category,
        ];
    }
}

/** @return Generator<int, array{lat: float, lng: float, resolution: int, category: string}> */
function polarAndAntimeridianCases(): Generator
{
    $poleLatitudes = [-90.0, -89.999999999999, -89.999999999, -89.999999, 89.999999, 89.999999999, 89.999999999999, 90.0];
    $longitudes = [-180.0, -179.999999999999, -90.0, 0.0, 90.0, 179.999999999999, 180.0];
    foreach ($poleLatitudes as $lat) {
        foreach ($longitudes as $lng) {
            for ($resolution = 0; $resolution <= 15; $resolution++) {
                yield compact('lat', 'lng', 'resolution') + ['category' => 'poles'];
            }
        }
    }

    $latitudes = [-89.0, -60.0, -1.0e-12, 0.0, 1.0e-12, 60.0, 89.0];
    $edgeLongitudes = [-180.0, -179.999999999999, -179.999999999, -179.999999, 179.999999, 179.999999999, 179.999999999999, 180.0];
    foreach ($latitudes as $lat) {
        foreach ($edgeLongitudes as $lng) {
            for ($resolution = 0; $resolution <= 15; $resolution++) {
                yield compact('lat', 'lng', 'resolution') + ['category' => 'antimeridian'];
            }
        }
    }
}

/** @return Generator<int, array{lat: float, lng: float, resolution: int, category: string}> */
function faceBoundaryCases(): Generator
{
    $centers = FaceProjection::FACE_CENTER_POINT;
    $pairs = [];
    foreach ($centers as $face => $center) {
        $distances = [];
        foreach ($centers as $otherFace => $other) {
            if ($face !== $otherFace) {
                $distances[$otherFace] = dot($center, $other);
            }
        }
        arsort($distances);
        foreach (array_slice(array_keys($distances), 0, 3) as $otherFace) {
            $key = min($face, $otherFace) . ':' . max($face, $otherFace);
            $pairs[$key] = [$face, $otherFace];
        }
    }

    foreach ($pairs as [$first, $second]) {
        $a = $centers[$first];
        $b = $centers[$second];
        $normal = normalize([$a[0] - $b[0], $a[1] - $b[1], $a[2] - $b[2]]);
        $midpoint = normalize([$a[0] + $b[0], $a[1] + $b[1], $a[2] + $b[2]]);
        $tangent = normalize(cross($normal, $midpoint));

        foreach ([-0.2, -0.1, 0.0, 0.1, 0.2] as $along) {
            $base = [
                cos($along) * $midpoint[0] + sin($along) * $tangent[0],
                cos($along) * $midpoint[1] + sin($along) * $tangent[1],
                cos($along) * $midpoint[2] + sin($along) * $tangent[2],
            ];
            foreach ([-1.0e-10, -1.0e-12, 0.0, 1.0e-12, 1.0e-10] as $across) {
                $point = normalize([
                    $base[0] + $across * $normal[0],
                    $base[1] + $across * $normal[1],
                    $base[2] + $across * $normal[2],
                ]);
                $lat = rad2deg(asin($point[2]));
                $lng = rad2deg(atan2($point[1], $point[0]));
                for ($resolution = 0; $resolution <= 15; $resolution++) {
                    yield compact('lat', 'lng', 'resolution') + ['category' => 'face-boundaries'];
                }
            }
        }
    }
}

/** @param array{lat: float, lng: float} $point @return array{float, float, float} */
function latLngToVector(array $point): array
{
    $lat = deg2rad($point['lat']);
    $lng = deg2rad($point['lng']);
    $radius = cos($lat);
    return [$radius * cos($lng), $radius * sin($lng), sin($lat)];
}

/** @param array{float, float, float} $vector @return array{lat: float, lng: float} */
function vectorToLatLng(array $vector): array
{
    $vector = normalize($vector);
    return ['lat' => rad2deg(asin($vector[2])), 'lng' => rad2deg(atan2($vector[1], $vector[0]))];
}

/** @param array{float, float, float} $a @param array{float, float, float} $b */
function dot(array $a, array $b): float
{
    return $a[0] * $b[0] + $a[1] * $b[1] + $a[2] * $b[2];
}

/** @param array{float, float, float} $a @param array{float, float, float} $b @return array{float, float, float} */
function cross(array $a, array $b): array
{
    return [
        $a[1] * $b[2] - $a[2] * $b[1],
        $a[2] * $b[0] - $a[0] * $b[2],
        $a[0] * $b[1] - $a[1] * $b[0],
    ];
}

/** @param array{float, float, float} $vector @return array{float, float, float} */
function normalize(array $vector): array
{
    $length = sqrt(dot($vector, $vector));
    return [$vector[0] / $length, $vector[1] / $length, $vector[2] / $length];
}

function angularErrorDegrees(float $expectedLat, float $expectedLng, float $actualLat, float $actualLng): float
{
    $expected = latLngToVector(['lat' => $expectedLat, 'lng' => $expectedLng]);
    $actual = latLngToVector(['lat' => $actualLat, 'lng' => $actualLng]);
    $cross = cross($expected, $actual);
    $sine = sqrt(dot($cross, $cross));
    $cosine = max(-1.0, min(1.0, dot($expected, $actual)));
    return rad2deg(atan2($sine, $cosine));
}

/**
 * @param iterable<array{lat: float, lng: float, resolution: int, category: string}> $cases
 */
function checkGeneratedCases(Oracle $oracle, iterable $cases, Results $results, int $chunkSize, float $tolerance): void
{
    $chunk = [];
    foreach ($cases as $case) {
        $chunk[] = $case;
        if (count($chunk) === $chunkSize) {
            checkGeneratedChunk($oracle, $chunk, $results, $tolerance);
            $chunk = [];
        }
    }
    if ($chunk !== []) {
        checkGeneratedChunk($oracle, $chunk, $results, $tolerance);
    }
}

/**
 * @param list<array{lat: float, lng: float, resolution: int, category: string}> $cases
 */
function checkGeneratedChunk(Oracle $oracle, array $cases, Results $results, float $tolerance): void
{
    $officialIndexes = $oracle->latLngToCell($cases);
    $indexes = [];
    foreach ($cases as $offset => $case) {
        $official = $officialIndexes[$offset];
        $category = $case['category'];
        if ($official['error'] !== 0) {
            $results->fail('latLngToCell', $category, "official error {$official['error']}");
            $indexes[] = '0';
            continue;
        }
        $indexes[] = $official['index'];
        try {
            $actual = dechex(Watchdog::run('latLngToCell',
                static fn (): int => H3::latLngToCell(new LatLng($case['lat'], $case['lng']), $case['resolution']),
            ));
            if ($actual !== $official['index']) {
                $results->fail(
                    'latLngToCell',
                    $category,
                    sprintf('lat=%.17g lng=%.17g res=%d expected=%s actual=%s', $case['lat'], $case['lng'], $case['resolution'], $official['index'], $actual),
                );
            } else {
                $results->pass('latLngToCell', $category);
            }
        } catch (Throwable $exception) {
            $results->fail('latLngToCell', $category, sprintf(
                'lat=%.17g lng=%.17g res=%d %s: %s',
                $case['lat'],
                $case['lng'],
                $case['resolution'],
                get_class($exception),
                $exception->getMessage(),
            ));
        }
    }

    $officialCenters = $oracle->cellToLatLng($indexes);
    foreach ($cases as $offset => $case) {
        $official = $officialCenters[$offset];
        $category = $case['category'];
        if ($official['error'] !== 0) {
            $results->fail('cellToLatLng', $category, "official error {$official['error']} for {$indexes[$offset]}");
            continue;
        }
        try {
            $index = (int) hexdec($indexes[$offset]);
            $actual = Watchdog::run('cellToLatLng', static fn (): LatLng => H3::cellToLatLng($index));
            $error = angularErrorDegrees($official['lat'], $official['lng'], $actual->lat, $actual->lng);
            if ($error > $tolerance) {
                $results->fail(
                    'cellToLatLng',
                    $category,
                    sprintf('cell=%s expected=(%.17g, %.17g) actual=(%.17g, %.17g) error=%.6g deg', $indexes[$offset], $official['lat'], $official['lng'], $actual->lat, $actual->lng, $error),
                    $error,
                );
            } else {
                $results->pass('cellToLatLng', $category, $error);
            }
        } catch (Throwable $exception) {
            $results->fail('cellToLatLng', $category, "cell={$indexes[$offset]} " . get_class($exception) . ': ' . $exception->getMessage());
        }
    }
}

function checkUpstreamVectors(Oracle $oracle, Results $results): void
{
    $oracle->eachUpstreamVector(function (string $file, string $index, float $lat, float $lng) use ($results): void {
        $category = str_starts_with($file, 'res')
            ? 'upstream-res0-4'
            : (str_starts_with($file, 'rand') ? 'upstream-random' : 'upstream-base-cells');
        $cell = (int) hexdec($index);
        $resolution = ($cell >> 52) & 15;

        try {
            $actualIndex = dechex(Watchdog::run('latLngToCell',
                static fn (): int => H3::latLngToCell(new LatLng($lat, $lng), $resolution),
            ));
            if ($actualIndex === $index) {
                $results->pass('latLngToCell', $category);
            } else {
                $results->fail('latLngToCell', $category, "file={$file} expected={$index} actual={$actualIndex}");
            }
        } catch (Throwable $exception) {
            $results->fail('latLngToCell', $category, "file={$file} cell={$index} " . get_class($exception) . ': ' . $exception->getMessage());
        }

        try {
            $actual = Watchdog::run('cellToLatLng', static fn (): LatLng => H3::cellToLatLng($cell));
            $error = angularErrorDegrees($lat, $lng, $actual->lat, $actual->lng);
            if ($error <= 1.000001e-6) {
                $results->pass('cellToLatLng', $category, $error);
            } else {
                $results->fail('cellToLatLng', $category, "file={$file} cell={$index} error={$error} deg", $error);
            }
        } catch (Throwable $exception) {
            $results->fail('cellToLatLng', $category, "file={$file} cell={$index} " . get_class($exception) . ': ' . $exception->getMessage());
        }
    });
}

/** @return list<array{lat: float, lng: float, resolution: int, category: string}> */
function pentagonCases(Oracle $oracle): array
{
    $cases = [];
    for ($resolution = 0; $resolution <= 15; $resolution++) {
        $pentagons = $oracle->pentagons($resolution);
        $indexes = array_column($pentagons, 'index');
        $boundaries = $oracle->boundaries($indexes);
        foreach ($pentagons as $offset => $pentagon) {
            $cases[] = [
                'lat' => $pentagon['lat'],
                'lng' => $pentagon['lng'],
                'resolution' => $resolution,
                'category' => 'pentagon-centers',
            ];
            $vertices = $boundaries[$offset];
            $points = $vertices;
            foreach ($vertices as $vertex => $point) {
                $next = $vertices[($vertex + 1) % count($vertices)];
                $a = latLngToVector($point);
                $b = latLngToVector($next);
                $points[] = vectorToLatLng([$a[0] + $b[0], $a[1] + $b[1], $a[2] + $b[2]]);
            }
            foreach ($points as $point) {
                foreach ([-1.0e-10, 0.0, 1.0e-10] as $delta) {
                    $cases[] = [
                        'lat' => max(-90.0, min(90.0, $point['lat'] + $delta)),
                        'lng' => $point['lng'] + $delta,
                        'resolution' => $resolution,
                        'category' => 'pentagon-boundaries',
                    ];
                }
            }
        }
    }

    return $cases;
}

/** @param array<string, string> $metadata */
function renderDifferentialReport(Results $results, array $metadata, int $randomCount, int $seed, float $tolerance, bool $upstreamIncluded): string
{
    $status = $results->failureCount() === 0 ? 'PASS' : 'FAIL';
    $report = "# Differential test report\n\n";
    $report .= "- Status: **{$status}**\n";
    $report .= '- Generated: ' . gmdate(DATE_ATOM) . "\n";
    $report .= '- PHP: ' . PHP_VERSION . "\n";
    $report .= '- Official H3: ' . $metadata['h3_version'] . ' (`' . $metadata['h3_commit'] . "`)\n";
    $report .= '- Oracle compiler: ' . $metadata['compiler'] . "\n";
    $report .= '- Research harness SHA-256: `' . researchFingerprint() . "`\n";
    $report .= "- Random coordinates: {$randomCount}; seed: {$seed}; resolutions: round-robin 0..15\n";
    $report .= '- Official upstream vectors: ' . ($upstreamIncluded ? 'included' : 'skipped') . "\n";
    $report .= sprintf("- Generated-center tolerance: %.3g degrees; upstream-vector tolerance: 1e-6 degrees\n", $tolerance);
    $report .= '- Project revision: ' . projectRevision() . "\n\n";
    $report .= "| Method | Corpus | Evaluated | Failures | Skipped | Max center error (deg) |\n";
    $report .= "| --- | ---: | ---: | ---: | ---: | ---: |\n";
    foreach ($results->stats as $method => $categories) {
        foreach ($categories as $category => $stat) {
            $report .= sprintf("| `%s` | %s | %d | %d | %d | %.6g |\n", $method, $category, $stat['cases'], $stat['failures'], $stat['skipped'], $stat['max_error_deg']);
        }
    }

    $report .= "\n## Corpus\n\n";
    $report .= "Random points are uniform by sphere area. Targeted cases cover both poles, both representations of the antimeridian, all 30 icosahedron face adjacencies with points on and to both sides of the boundary, all 12 pentagons at every resolution and their vertices/edge midpoints, and the official H3 `res*ic`, `rand*centers`, and `bc*centers` vectors.\n";
    if ($results->failures !== []) {
        $report .= "\n## First counterexamples\n\n";
        foreach ($results->failures as $failure) {
            $report .= '- `' . str_replace('`', '\\`', $failure) . "`\n";
        }
    }

    return $report;
}

function projectRevision(): string
{
    $revision = runCommand(['git', 'rev-parse', 'HEAD']);
    $status = runCommand(['git', 'status', '--short']);
    if ($revision['exit'] !== 0) {
        return 'unknown';
    }
    return $revision['stdout'] . ($status['stdout'] === '' ? '' : ' (dirty)');
}

function researchFingerprint(): string
{
    $contents = '';
    foreach ([__DIR__ . '/Dockerfile', __DIR__ . '/oracle.c', __FILE__] as $file) {
        $contents .= basename($file) . "\0" . file_get_contents($file) . "\0";
    }
    return hash('sha256', $contents);
}

function writeReport(string $path, string $contents): void
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException("Cannot create report directory {$directory}.");
    }
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException("Cannot write report {$path}.");
    }
    fwrite(STDOUT, "Report written to {$path}\n");
}

/** @param callable(): void $operation @return list<int> */
function benchmarkPhp(int $warmups, int $repeats, callable $operation): array
{
    for ($warmup = 0; $warmup < $warmups; $warmup++) {
        $operation();
    }
    $timings = [];
    for ($repeat = 0; $repeat < $repeats; $repeat++) {
        $started = hrtime(true);
        $operation();
        $timings[] = hrtime(true) - $started;
    }
    return $timings;
}

/** @template T @param callable(): T $operation @return T */
function withTimeout(int $seconds, callable $operation): mixed
{
    if (!function_exists('pcntl_alarm') || !function_exists('pcntl_signal')) {
        return $operation();
    }
    pcntl_async_signals(true);
    pcntl_signal(SIGALRM, static function () use ($seconds): never {
        throw new OperationTimedOut("benchmark timed out after {$seconds} seconds");
    });
    pcntl_alarm($seconds);
    try {
        return $operation();
    } finally {
        pcntl_alarm(0);
    }
}

/** @param list<int> $values */
function median(array $values): float
{
    sort($values);
    $middle = intdiv(count($values), 2);
    if (count($values) % 2 === 1) {
        return (float) $values[$middle];
    }
    return ($values[$middle - 1] + $values[$middle]) / 2.0;
}

/** @param list<int> $timings */
function metric(array $timings, int $samples): array
{
    $elapsed = median($timings);
    $nsPerOperation = $elapsed / $samples;
    return ['ns' => $nsPerOperation, 'ops' => 1_000_000_000.0 / $nsPerOperation];
}

/** @param array<string, string> $metadata @param list<array<string, float|string|null>> $rows */
function renderBenchmarkReport(array $metadata, array $rows, int $samples, int $seed, int $warmups, int $repeats): string
{
    $report = "# Benchmark report\n\n";
    $report .= '- Generated: ' . gmdate(DATE_ATOM) . "\n";
    $report .= '- PHP: ' . PHP_VERSION . '; JIT: ' . (ini_get('opcache.jit') ?: 'off') . "\n";
    $report .= '- Official H3: ' . $metadata['h3_version'] . ' (`' . $metadata['h3_commit'] . "`)\n";
    $report .= '- Oracle compiler: ' . $metadata['compiler'] . "\n";
    $report .= '- Research harness SHA-256: `' . researchFingerprint() . "`\n";
    $report .= '- Host: ' . php_uname() . "\n";
    $report .= "- Dataset: {$samples} sphere-uniform points, seed {$seed}, resolutions 0..15\n";
    $report .= "- Protocol: {$warmups} warm-up passes, {$repeats} measured passes; table uses the median\n";
    $report .= '- Project revision: ' . projectRevision() . "\n\n";
    $report .= "| Method | Implementation | ns/op | ops/s | PHP/C ratio |\n";
    $report .= "| --- | --- | ---: | ---: | ---: |\n";
    foreach ($rows as $row) {
        if ($row['ns'] === null || $row['ops'] === null) {
            $report .= sprintf("| `%s` | %s | unavailable | unavailable | %s |\n", $row['method'], $row['implementation'], $row['ratio']);
            continue;
        }
        $ratio = is_float($row['ratio']) ? sprintf('%.2fx', $row['ratio']) : $row['ratio'];
        $report .= sprintf("| `%s` | %s | %.1f | %.0f | %s |\n", $row['method'], $row['implementation'], $row['ns'], $row['ops'], $ratio);
    }
    $report .= "\nThe fair `latLngToCell` comparison is PHP versus C “degrees”: both convert degree input on every call. C “radians” is included as a lower-level reference because the PHP public API has no radians entry point. Container startup, IPC, corpus generation, and report rendering are outside timed regions. Results are machine-specific and should only be compared when the environment metadata matches.\n";
    return $report;
}

function runDifferential(Oracle $oracle, array $options): int
{
    $randomCount = (int) ($options['random'] ?? 2_000_000);
    $seed = (int) ($options['seed'] ?? DEFAULT_SEED);
    $chunkSize = (int) ($options['chunk'] ?? 1_000);
    $tolerance = (float) ($options['tolerance'] ?? 1.0e-7);
    $output = (string) ($options['output'] ?? (__DIR__ . '/reports/differential.md'));
    $upstreamIncluded = !isset($options['skip-upstream']);
    if ($randomCount < 0 || $chunkSize < 1 || $tolerance <= 0.0) {
        throw new InvalidArgumentException('random must be >= 0; chunk and tolerance must be positive.');
    }

    $metadata = $oracle->metadata();
    $results = new Results();
    if ($upstreamIncluded) {
        fwrite(STDERR, "Checking official upstream vectors...\n");
        checkUpstreamVectors($oracle, $results);
    }
    fwrite(STDERR, "Checking poles and antimeridian...\n");
    checkGeneratedCases($oracle, polarAndAntimeridianCases(), $results, $chunkSize, $tolerance);
    fwrite(STDERR, "Checking icosahedron face boundaries...\n");
    checkGeneratedCases($oracle, faceBoundaryCases(), $results, $chunkSize, $tolerance);
    fwrite(STDERR, "Checking pentagons and their boundaries...\n");
    checkGeneratedCases($oracle, pentagonCases($oracle), $results, $chunkSize, $tolerance);
    fwrite(STDERR, "Checking {$randomCount} random coordinates...\n");
    checkGeneratedCases($oracle, randomCases($randomCount, $seed), $results, $chunkSize, $tolerance);

    writeReport($output, renderDifferentialReport($results, $metadata, $randomCount, $seed, $tolerance, $upstreamIncluded));
    $failures = $results->failureCount();
    fwrite(STDOUT, $failures === 0 ? "Differential checks passed.\n" : "Differential checks found {$failures} mismatches.\n");
    return $failures === 0 ? 0 : 1;
}

function runBenchmark(Oracle $oracle, array $options): int
{
    $samples = (int) ($options['samples'] ?? 100_000);
    $seed = (int) ($options['seed'] ?? DEFAULT_SEED);
    $warmups = (int) ($options['warmups'] ?? 2);
    $repeats = (int) ($options['repeats'] ?? 9);
    $output = (string) ($options['output'] ?? (__DIR__ . '/reports/benchmark.md'));
    if ($samples < 1 || $warmups < 0 || $repeats < 1) {
        throw new InvalidArgumentException('samples/repeats must be positive; warmups must be >= 0.');
    }

    $metadata = $oracle->metadata();
    $cases = iterator_to_array(randomCases($samples, $seed, 'benchmark'), false);
    $indexes = [];
    foreach (array_chunk($cases, 1_000) as $chunk) {
        foreach ($oracle->latLngToCell($chunk) as $result) {
            if ($result['error'] !== 0) {
                throw new RuntimeException('Official latLngToCell failed while preparing benchmark data.');
            }
            $indexes[] = $result['index'];
        }
    }
    $objects = array_map(static fn (array $case): LatLng => new LatLng($case['lat'], $case['lng']), $cases);
    $integerIndexes = array_map(static fn (string $index): int => (int) hexdec($index), $indexes);
    $benchmarkTimeout = max(5, min(60, (int) ceil($samples * ($warmups + $repeats) / 5_000)));

    fwrite(STDERR, "Benchmarking official H3...\n");
    $cLatLng = $oracle->benchmarkLatLng($cases, $warmups, $repeats);
    $cCells = $oracle->benchmarkCells($indexes, $warmups, $repeats);

    fwrite(STDERR, "Benchmarking PHP implementation...\n");
    $phpLatLng = null;
    $phpLatLngWithAllocation = null;
    $phpCells = null;
    try {
        $phpLatLng = withTimeout($benchmarkTimeout, static fn (): array => benchmarkPhp($warmups, $repeats, static function () use ($cases, $objects): void {
            $checksum = 0;
            foreach ($cases as $offset => $case) {
                $checksum ^= H3::latLngToCell($objects[$offset], $case['resolution']);
            }
            if ($checksum === PHP_INT_MIN) {
                fwrite(STDERR, '');
            }
        }));
    } catch (OperationTimedOut $exception) {
        fwrite(STDERR, "latLngToCell benchmark: {$exception->getMessage()}\n");
    }
    try {
        $phpLatLngWithAllocation = withTimeout($benchmarkTimeout, static fn (): array => benchmarkPhp($warmups, $repeats, static function () use ($cases): void {
            $checksum = 0;
            foreach ($cases as $case) {
                $checksum ^= H3::latLngToCell(new LatLng($case['lat'], $case['lng']), $case['resolution']);
            }
            if ($checksum === PHP_INT_MIN) {
                fwrite(STDERR, '');
            }
        }));
    } catch (OperationTimedOut $exception) {
        fwrite(STDERR, "latLngToCell with allocation benchmark: {$exception->getMessage()}\n");
    }
    try {
        $phpCells = withTimeout($benchmarkTimeout, static fn (): array => benchmarkPhp($warmups, $repeats, static function () use ($integerIndexes): void {
            $checksum = 0.0;
            foreach ($integerIndexes as $index) {
                $coordinate = H3::cellToLatLng($index);
                $checksum += $coordinate->lat + $coordinate->lng;
            }
            if (is_nan($checksum)) {
                fwrite(STDERR, '');
            }
        }));
    } catch (OperationTimedOut $exception) {
        fwrite(STDERR, "cellToLatLng benchmark: {$exception->getMessage()}\n");
    }

    $cRawMetric = metric($cLatLng['raw'], $samples);
    $cDegreesMetric = metric($cLatLng['degrees'], $samples);
    $phpLatLngMetric = $phpLatLng === null ? null : metric($phpLatLng, $samples);
    $phpAllocationMetric = $phpLatLngWithAllocation === null ? null : metric($phpLatLngWithAllocation, $samples);
    $cCellMetric = metric($cCells, $samples);
    $phpCellMetric = $phpCells === null ? null : metric($phpCells, $samples);
    $rows = [
        ['method' => 'latLngToCell', 'implementation' => 'Official C, radians precomputed', ...$cRawMetric, 'ratio' => 'baseline only'],
        ['method' => 'latLngToCell', 'implementation' => 'Official C, degrees conversion', ...$cDegreesMetric, 'ratio' => '1.00x'],
        $phpLatLngMetric === null
            ? ['method' => 'latLngToCell', 'implementation' => 'PHP, prebuilt LatLng', 'ns' => null, 'ops' => null, 'ratio' => 'timed out']
            : ['method' => 'latLngToCell', 'implementation' => 'PHP, prebuilt LatLng', ...$phpLatLngMetric, 'ratio' => $phpLatLngMetric['ns'] / $cDegreesMetric['ns']],
        $phpAllocationMetric === null
            ? ['method' => 'latLngToCell', 'implementation' => 'PHP, including LatLng allocation', 'ns' => null, 'ops' => null, 'ratio' => 'timed out']
            : ['method' => 'latLngToCell', 'implementation' => 'PHP, including LatLng allocation', ...$phpAllocationMetric, 'ratio' => $phpAllocationMetric['ns'] / $cDegreesMetric['ns']],
        ['method' => 'cellToLatLng', 'implementation' => 'Official C', ...$cCellMetric, 'ratio' => '1.00x'],
        $phpCellMetric === null
            ? ['method' => 'cellToLatLng', 'implementation' => 'PHP', 'ns' => null, 'ops' => null, 'ratio' => 'timed out']
            : ['method' => 'cellToLatLng', 'implementation' => 'PHP', ...$phpCellMetric, 'ratio' => $phpCellMetric['ns'] / $cCellMetric['ns']],
    ];

    writeReport($output, renderBenchmarkReport($metadata, $rows, $samples, $seed, $warmups, $repeats));
    return 0;
}

function usage(): void
{
    fwrite(STDOUT, <<<'TEXT'
Usage:
  php tools/research/research.php differential [--random=2000000] [--seed=N]
      [--chunk=1000] [--tolerance=1e-7] [--output=PATH] [--skip-upstream]
      [--rebuild]
  php tools/research/research.php benchmark [--samples=100000] [--seed=N]
      [--warmups=2] [--repeats=9] [--output=PATH] [--rebuild]

TEXT);
}

try {
    $mode = $argv[1] ?? 'help';
    if (!in_array($mode, ['differential', 'benchmark'], true)) {
        usage();
        exit($mode === 'help' || $mode === '--help' ? 0 : 2);
    }
    $options = parseOptions(array_slice($argv, 2));
    $image = (string) ($options['image'] ?? DEFAULT_IMAGE);
    ensureImage($image, isset($options['rebuild']));
    $oracle = new Oracle($image);
    $exitCode = $mode === 'differential'
        ? runDifferential($oracle, $options)
        : runBenchmark($oracle, $options);
    $oracle->close();
    exit($exitCode);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Research failed: ' . $exception->getMessage() . "\n");
    exit(2);
}
