<?php

declare(strict_types=1);

namespace H3\Converter;

use H3\Constants;
use H3\Enum\Overage;
use H3\Exception\H3IndexInvalidException;
use H3\FaceProjection;
use H3\H3Modification;
use H3\Helper\BaseCell;
use H3\Helper\Math;
use H3\ValueObject\CoordIJK;
use H3\ValueObject\FaceIJK;
use H3\ValueObject\Vec3d;

final class H3IndexConverter
{
    /**
     * Determines the 3D cartesian coordinates of the center of an H3 cell
     *
     * @param int $h3 The H3 index
     * @return Vec3d The 3D cartesian coordinates of the H3 cell center
     * @throws H3IndexInvalidException
     */
    public static function h3IndexToVec3d(int $h3): Vec3d
    {
        return FaceIJKConverter::faceIjkToVec3d(
            self::h3ToFaceIjk($h3),
            H3Modification::h3GetResolution($h3),
        );
    }

    /**
     * Convert an H3Index to a FaceIJK address
     *
     * @param int $h3 The H3Index
     * @return FaceIJK The corresponding FaceIJK address
     * @throws H3IndexInvalidException
     */
    public static function h3ToFaceIjk(int $h3): FaceIJK
    {
        $baseCell = H3Modification::h3GetBaseCell($h3);
        if ($baseCell < 0 || $baseCell >= Constants::NUM_BASE_CELLS) {
            // Base cells less than zero can not be represented in an index
            throw new H3IndexInvalidException();
        }
        // adjust for the pentagonal missing sequence; all of sub-sequence 5 needs
        // to be adjusted (and some of sub-sequence 4 below)
        if (BaseCell::isBaseCellPentagon($baseCell) && H3Modification::h3LeadingNonZeroDigit($h3) === 5) {
            $h3 = H3Modification::h3Rotate60cw($h3);
        }

        // start with the "home" face and ijk+ coordinates for the base cell of c
        $baseCellData = FaceProjection::BASE_CELL_DATA[$baseCell][0];
        $fijk = new FaceIJK(
            face: $baseCellData[0],
            coord: CoordIJK::fromArray($baseCellData[1])
        );
        $result = self::h3ToFaceIjkWithInitializedFijk($h3, $fijk);
        $fijk = $result['fijk'];
        if ($result['possibleOverage'] === 0) {
            return $fijk;
        }

        // if we're here we have the potential for an "overage"; i.e., it is
        // possible that c lies on an adjacent face
        $origIJK = $fijk->getCoord();
        $res = H3Modification::h3GetResolution($h3);

        // if we're in Class III, drop into the next finer Class II grid
        if (Math::isResolutionClassIII($res) === 1) {
            // Class III
            $fijk = new FaceIJK(
                face: $fijk->getFace(),
                coord: $fijk->getCoord()->downAp7r(),
            );
            $res++;
        }

        // adjust for overage if needed
        // a pentagon base cell with a leading 4 digit requires special handling
        $pentLeading4 = BaseCell::isBaseCellPentagon($baseCell) && H3Modification::h3LeadingNonZeroDigit($h3) === 4;

        $result = FaceIJKConverter::adjustOverageClassII($fijk, $res, $pentLeading4, 0);
        $fijk = $result['fijk'];
        if ($result['overage'] !== Overage::NO_OVERAGE) {
            // if the base cell is a pentagon we have the potential for secondary
            // overages
            if (BaseCell::isBaseCellPentagon($baseCell)) {
                do {
                    $result = FaceIJKConverter::adjustOverageClassII($fijk, $res, $pentLeading4, 0);
                    $fijk = $result['fijk'];
                } while ($result['overage'] !== Overage::NO_OVERAGE);
            }

            if ($res !== H3Modification::h3GetResolution($h3)) {
                $fijk = new FaceIJK(
                    face: $fijk->getFace(),
                    coord: $fijk->getCoord()->upAp7r(),
                );
            }
        } else if ($res !== H3Modification::h3GetResolution($h3)) {
            $fijk = new FaceIJK(
                face: $fijk->getFace(),
                coord: $origIJK,
            );
        }

        return $fijk;
    }

    /**
     * Convert an H3Index to the FaceIJK address on a specified icosahedral face
     * @param int $h3 The H3Index
     * @param FaceIJK $fijk The FaceIJK address, initialized with the desired face
     *         and normalized base cell coordinates
     * @return array{fijk: FaceIJK, possibleOverage: int} possibleOverage returns 1 if the possibility of overage exists, otherwise 0
     */
    public static function h3ToFaceIjkWithInitializedFijk(int $h3, FaceIJK $fijk): array
    {
        $ijk = $fijk->getCoord();
        $resolution = H3Modification::h3GetResolution($h3);

        $possibleOverage = 1;
        if(
            !BaseCell::isBaseCellPentagon(H3Modification::h3GetBaseCell($h3))
            && ($resolution === 0
                || (
                    $fijk->getCoord()->getI() === 0
                    && $fijk->getCoord()->getJ() === 0
                    && $fijk->getCoord()->getK() === 0
                )
            )
        ) {
            $possibleOverage = 0;
        }

        for ($r = 1; $r <= $resolution; $r++) {
            if (Math::isResolutionClassIII($r) === 1) {
                $ijk = $ijk->downAp7();
            } else {
                $ijk = $ijk->downAp7r();
            }
            $ijk = $ijk->neighbor(H3Modification::h3GetIndexDigit($h3, $r));
        }

        return [
            'fijk' => $fijk,
            'possibleOverage' => $possibleOverage,
        ];
    }
}