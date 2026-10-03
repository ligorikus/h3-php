<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\Exception\H3DomainException;
use H3\Internal\Converter\Vec2dConverter;
use H3\Internal\ValueObject\Vec2d;
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

    /** @throws H3DomainException */
    #[DataProvider('invalidFaces')]
    public function testRejectsInvalidFace(int $face, float $x): void
    {
        $this->expectException(H3DomainException::class);

        Vec2dConverter::hex2dToVec3(new Vec2d($x, 0.0), $face, 0, 0);
    }

    /** @return iterable<string, array{int, float}> */
    public static function invalidFaces(): iterable
    {
        yield 'negative face at center' => [-1, 0.0];
        yield 'negative face away from center' => [-1, 1.0];
        yield 'face above maximum at center' => [20, 0.0];
        yield 'face above maximum away from center' => [20, 1.0];
    }
}
