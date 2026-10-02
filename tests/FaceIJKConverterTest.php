<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\Converter\FaceIJKConverter;
use H3\Enum\Overage;
use H3\Exception\H3DomainException;
use H3\Exception\H3ResolutionException;
use H3\ValueObject\CoordIJK;
use H3\ValueObject\FaceIJK;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Entry point discovered and invoked by PHPUnit.
 * @psalm-api
 */
final class FaceIJKConverterTest extends TestCase
{
    /**
     * @throws H3DomainException
     * @throws H3ResolutionException
     */
    #[DataProvider('invalidResolutions')]
    public function testRejectsInvalidClassIIResolution(int $resolution): void
    {
        $this->expectException(H3ResolutionException::class);

        FaceIJKConverter::adjustOverageClassII(new FaceIJK(0, new CoordIJK(0, 0, 0)), $resolution, false, 0);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidResolutions(): iterable
    {
        yield 'negative' => [-1];
        yield 'Class III' => [1];
        yield 'maximum Class III' => [15];
        yield 'above adjusted maximum' => [17];
        yield 'Class II above adjusted maximum' => [18];
    }

    /**
     * @throws H3DomainException
     * @throws H3ResolutionException
     */
    #[DataProvider('invalidFaces')]
    public function testRejectsInvalidFace(int $face): void
    {
        $this->expectException(H3DomainException::class);

        FaceIJKConverter::adjustOverageClassII(new FaceIJK($face, new CoordIJK(0, 0, 0)), 0, false, 0);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidFaces(): iterable
    {
        yield 'negative' => [-1];
        yield 'above maximum' => [20];
    }

    /**
     * @throws H3DomainException
     * @throws H3ResolutionException
     * @throws ExpectationFailedException
     */
    #[DataProvider('classIIResolutions')]
    public function testAcceptsClassIIResolutionIncludingAdjustedMaximum(int $resolution): void
    {
        $face = new FaceIJK(19, new CoordIJK(0, 0, 0));

        $result = FaceIJKConverter::adjustOverageClassII($face, $resolution, false, 0);

        self::assertSame($face, $result['fijk']);
        self::assertSame(Overage::NO_OVERAGE, $result['overage']);
    }

    /** @return iterable<string, array{int}> */
    public static function classIIResolutions(): iterable
    {
        yield 'minimum' => [0];
        yield 'maximum' => [14];
        yield 'adjusted maximum for Class III' => [16];
    }
}
