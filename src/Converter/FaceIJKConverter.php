<?php

declare(strict_types=1);

namespace H3\Converter;

use H3\Constants;
use H3\Enum\Direction;
use H3\FaceProjection;
use H3\H3IndexMode;
use H3\H3Modification;
use H3\Helper\Math;
use H3\ValueObject\CoordIJK;
use H3\ValueObject\FaceIJK;

final class FaceIJKConverter
{
    private const MAX_FACE_COORD = 2;

    public static function faceIjkToH3Index(FaceIJK $fijk, int $resolution): int
    {
        // initialize the index
        $h = Constants::H3_INIT;
        $h = H3Modification::h3Mode($h, H3IndexMode::H3_CELL_MODE);
        $h = H3Modification::h3Resolution($h , $resolution);

        if ($resolution == 0) {
            if ($fijk->getCoord()->getI() > self::MAX_FACE_COORD
                || $fijk->getCoord()->getJ() > self::MAX_FACE_COORD
                || $fijk->getCoord()->getK() > self::MAX_FACE_COORD) {
                return 0;
            }

            return H3Modification::h3SetBaseCell($h, $fijk->getBaseCell()['baseCell']);
        }

        // build the H3Index from finest res up
        // adjust r for the fact that the res 0 base cell offsets the indexing
        // digits
        $ijk = $fijk->getCoord();
        for ($r = $resolution - 1; $r >= 0; $r--) {
            $lastIjk = $ijk;
            if (Math::isResolutionClassIII($r + 1)) {
                $ijk = self::upAp7($ijk);
                $lastCenter = self::downAp7($ijk);
            } else {
                // rotate cw
                $ijk = self::upAp7r($ijk);
                $lastCenter = self::downAp7r($ijk);
            }
            $diff = $lastIjk->sub($lastCenter);
            $diff = $diff->normalize();
            $h = H3Modification::h3SetIndexDigit($h, $r+1, $diff->toDigit());
        }

        // we need to find the correct base cell FaceIJK for this H3 index;
        // start with the passed in face and resolution res ijk coordinates
        // in that face's coordinate system
        $fijkBC = new FaceIJK($fijk->getFace(), $ijk);

        if (
            $fijkBC->getCoord()->getI() > self::MAX_FACE_COORD
            || $fijkBC->getCoord()->getJ() > self::MAX_FACE_COORD
            || $fijkBC->getCoord()->getK() > self::MAX_FACE_COORD
        ) {
            return 0;
        }

        $h = H3Modification::h3SetBaseCell($h, $fijkBC->getBaseCell()['baseCell']);

        // rotate if necessary to get canonical base cell orientation
        // for this base cell
        $numRots = $fijkBC->getBaseCell()['ccwRot60'];
        if (self::isBaseCellPentagon($fijkBC->getBaseCell()['baseCell'])) {
            // force rotation out of missing k-axes sub-sequence
            if (H3Modification::h3LeadingNonZeroDigit($h) === Direction::K_AXES_DIGIT) {
                // check for a cw/ccw offset face; default is ccw
                if (self::baseCellIsCwOffset($fijkBC->getBaseCell()['baseCell'], $fijkBC->getFace())) {
                    $h = H3Modification::h3Rotate60cw($h);
                } else {
                    $h = H3Modification::h3Rotate60ccw($h);
                }
            }
        } else {
            for ($i = 0; $i < $numRots; $i++) {
                $h = H3Modification::h3Rotate60ccw($h);
            }
        }
        return $h;
    }

    /**
     * Find the normalized ijk coordinates of the indexing parent of a cell in a
     * counter-clockwise aperture 7 grid. Works in place.
     *
     * @param CoordIJK $ijk
     * @return CoordIJK
     */
    private static function upAp7(CoordIJK $ijk): CoordIJK
    {
        $i = $ijk->getI() - $ijk->getK();
        $j = $ijk->getJ() - $ijk->getK();

        $newI = (int)round(num: floatval(3 * $i - $j) * Constants::M_ONESEVENTH);
        $newJ = (int)round(num: floatval($i + 2 * $j) * Constants::M_ONESEVENTH);

        $newK = 0;
        return (new CoordIJK(
            i: $newI,
            j: $newJ,
            k: $newK
        ))->normalize();
    }

    private static function downAp7(CoordIJK $ijk): CoordIJK
    {
        $iVec = new CoordIJK(3, 0, 1);
        $jVec = new CoordIJK(1, 3, 0);
        $kVec = new CoordIJK(0, 1, 3);

        $iVec = $iVec->scale($ijk->getI());
        $jVec = $jVec->scale($ijk->getJ());
        $kVec = $kVec->scale($ijk->getK());

        $ijk = $iVec->add($jVec);
        $ijk = $ijk->add($kVec);

        return $ijk->normalize();
    }

    private static function upAp7r(CoordIJK $ijk): CoordIJK
    {
        $i = $ijk->getI() - $ijk->getK();
        $j = $ijk->getJ() - $ijk->getK();

        $newI = (int)round(num: floatval(2 * $i + $j) * Constants::M_ONESEVENTH);
        $newJ = (int)round(num: floatval(3 * $j - $i) * Constants::M_ONESEVENTH);
        $newK = 0;
        return (new CoordIJK(
            i: $newI,
            j: $newJ,
            k: $newK
        ))->normalize();
    }

    private static function downAp7r(CoordIJK $ijk): CoordIJK
    {
        $iVec = new CoordIJK(3, 1, 0);
        $jVec = new CoordIJK(0, 3, 1);
        $kVec = new CoordIJK(1, 0, 3);

        $iVec = $iVec->scale($ijk->getI());
        $jVec = $jVec->scale($ijk->getJ());
        $kVec = $kVec->scale($ijk->getK());

        $ijk = $iVec->add($jVec);
        $ijk = $ijk->add($kVec);

        return $ijk->normalize();
    }
    private static function isBaseCellPentagon(int $baseCell): bool
    {
        if ($baseCell < 0 || $baseCell > Constants::NUM_BASE_CELLS) {
            return false;
        }

        return (bool)FaceProjection::BASE_CELL_DATA[$baseCell][1];
    }

    private static function baseCellIsCwOffset(int $baseCell, int $testFace): bool
    {
        return FaceProjection::BASE_CELL_DATA[$baseCell][2][0] === $testFace
            || FaceProjection::BASE_CELL_DATA[$baseCell][2][1] === $testFace;
    }

}