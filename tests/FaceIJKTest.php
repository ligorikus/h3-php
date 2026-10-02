<?php

declare(strict_types=1);

namespace H3\Tests;

use H3\Exception\H3DomainException;
use H3\ValueObject\CoordIJK;
use H3\ValueObject\FaceIJK;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Entry point discovered and invoked by PHPUnit.
 * @psalm-api
 */
final class FaceIJKTest extends TestCase
{
    /**
     * @throws H3DomainException
     * @throws ExpectationFailedException
     */
    public function testFindsBaseCellAtFaceCenter(): void
    {
        $face = new FaceIJK(0, new CoordIJK(0, 0, 0));

        self::assertSame(['baseCell' => 16, 'ccwRot60' => 0], $face->getBaseCell());
    }

    /** @throws H3DomainException */
    #[DataProvider('invalidAddresses')]
    public function testRejectsInvalidAddress(int $face, int $i, int $j, int $k): void
    {
        $this->expectException(H3DomainException::class);

        (new FaceIJK($face, new CoordIJK($i, $j, $k)))->getBaseCell();
    }

    /** @return iterable<string, array{int, int, int, int}> */
    public static function invalidAddresses(): iterable
    {
        yield 'negative face' => [-1, 0, 0, 0];
        yield 'face above maximum' => [20, 0, 0, 0];
        yield 'negative i' => [0, -1, 0, 0];
        yield 'i above maximum' => [0, 3, 0, 0];
        yield 'negative j' => [0, 0, -1, 0];
        yield 'j above maximum' => [0, 0, 3, 0];
        yield 'negative k' => [0, 0, 0, -1];
        yield 'k above maximum' => [0, 0, 0, 3];
    }
}
