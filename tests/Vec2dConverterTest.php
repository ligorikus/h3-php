<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\Converter\Vec2dConverter;
use H3\ValueObject\Vec2d;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Entry point discovered and invoked by PHPUnit.
 * @psalm-api
 */
final class Vec2dConverterTest extends TestCase
{
    /** @throws ExpectationFailedException */
    #[DataProvider('hexCenters')]
    public function testConvertsHexCenter(float $x, float $y, int $i, int $j, int $k): void
    {
        $coord = Vec2dConverter::vec2dToCoordIJK(new Vec2d($x, $y));

        self::assertSame([$i, $j, $k], [$coord->getI(), $coord->getJ(), $coord->getK()]);
    }

    /** @return iterable<string, array{float, float, int, int, int}> */
    public static function hexCenters(): iterable
    {
        $sin60 = sqrt(3.0) / 2.0;

        yield 'origin' => [0.0, 0.0, 0, 0, 0];
        yield 'positive x' => [1.0, 0.0, 1, 0, 0];
        yield 'negative x with even j' => [-1.0, 0.0, 0, 1, 1];
        yield 'positive x and y' => [0.5, $sin60, 1, 1, 0];
        yield 'negative x with odd j' => [-0.5, $sin60, 0, 1, 0];
        yield 'negative y' => [0.5, -$sin60, 1, 0, 1];
        yield 'negative x and y' => [-1.0, -2.0 * $sin60, 0, 0, 2];
    }
}
