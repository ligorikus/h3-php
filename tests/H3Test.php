<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\Exception\H3DomainException;
use H3\Exception\H3LatLngDomainException;
use H3\Exception\H3ResolutionException;
use H3\H3;
use H3\Internal\Converter\Vec3dConverter;
use H3\Internal\ValueObject\Vec3d;
use H3\Type\LatLng;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Entry point discovered and invoked by PHPUnit.
 * @psalm-api
 */
final class H3Test extends TestCase
{
    /**
     * @throws H3DomainException
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     * @throws ExpectationFailedException
     */
    public function testEncodesKnownCell(): void
    {
        $cell = H3::latLngToCell(new LatLng(37.7749, -122.4194), 9);

        self::assertSame('89283082803ffff', dechex($cell));
    }

    /**
     * @throws H3DomainException
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     * @throws ExpectationFailedException
     */
    public function testEncodesKnownCellFromVector(): void
    {
        $vector = Vec3d::fromLatLng(new LatLng(37.7749, -122.4194));

        self::assertSame('89283082803ffff', dechex(Vec3dConverter::vec3ToCell($vector, 9)));
    }

    /**
     * @throws H3DomainException
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     */
    #[DataProvider('invalidResolutions')]
    public function testRejectsInvalidResolution(int $resolution): void
    {
        $this->expectException(H3ResolutionException::class);

        H3::latLngToCell(new LatLng(37.7749, -122.4194), $resolution);
    }

    /**
     * @throws H3DomainException
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     */
    #[DataProvider('invalidResolutions')]
    public function testRejectsInvalidVectorResolution(int $resolution): void
    {
        $this->expectException(H3ResolutionException::class);

        Vec3dConverter::vec3ToCell(new Vec3d(1.0, 0.0, 0.0), $resolution);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidResolutions(): iterable
    {
        yield 'negative' => [-1];
        yield 'above maximum' => [16];
    }

    /**
     * @throws H3DomainException
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     */
    #[DataProvider('invalidCoordinates')]
    public function testRejectsNonFiniteCoordinates(float $lat, float $lng): void
    {
        $this->expectException(H3LatLngDomainException::class);

        H3::latLngToCell(new LatLng($lat, $lng), 9);
    }

    /** @return iterable<string, array{float, float}> */
    public static function invalidCoordinates(): iterable
    {
        yield 'infinite latitude' => [INF, 0.0];
        yield 'negative infinite longitude' => [0.0, -INF];
        yield 'NaN latitude' => [NAN, 0.0];
        yield 'NaN longitude' => [0.0, NAN];
    }

    /**
     * @throws H3DomainException
     * @throws H3LatLngDomainException
     * @throws H3ResolutionException
     */
    #[DataProvider('invalidVectors')]
    public function testRejectsNonFiniteVector(float $x, float $y, float $z): void
    {
        $this->expectException(H3LatLngDomainException::class);

        Vec3dConverter::vec3ToCell(new Vec3d($x, $y, $z), 9);
    }

    /** @return iterable<string, array{float, float, float}> */
    public static function invalidVectors(): iterable
    {
        yield 'infinite x' => [INF, 0.0, 0.0];
        yield 'negative infinite y' => [0.0, -INF, 0.0];
        yield 'NaN z' => [0.0, 0.0, NAN];
    }
}
