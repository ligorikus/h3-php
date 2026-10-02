<?php

declare(strict_types=1);

namespace H3\Converter;

use H3\Constants;
use H3\Enum\Direction;
use H3\Enum\FaceNeighbors;
use H3\Enum\Overage;
use H3\Exception\H3DomainException;
use H3\FaceProjection;
use H3\H3IndexMode;
use H3\H3Modification;
use H3\Helper\BaseCell;
use H3\Helper\Math;
use H3\ValueObject\CoordIJK;
use H3\ValueObject\FaceIJK;
use H3\ValueObject\FaceOrientIJK;
use H3\ValueObject\Vec3d;

final class FaceIJKConverter
{
    private const MAX_FACE_COORD = 2;

    /**
     * overage distance table
     */
    private const MAX_DIM_BY_C_I_I_RES = [
        2,        // res  0
        -1,       // res  1
        14,       // res  2
        -1,       // res  3
        98,       // res  4
        -1,       // res  5
        686,      // res  6
        -1,       // res  7
        4802,     // res  8
        -1,       // res  9
        33614,    // res 10
        -1,       // res 11
        235298,   // res 12
        -1,       // res 13
        1647086,  // res 14
        -1,       // res 15
        11529602,  // res 16
    ];

    /**
     * unit scale distance table
     */
    private const UNIT_SCALE_BY_C_I_I_RES = [
        1,       // res  0
        -1,      // res  1
        7,       // res  2
        -1,      // res  3
        49,      // res  4
        -1,      // res  5
        343,     // res  6
        -1,      // res  7
        2401,    // res  8
        -1,      // res  9
        16807,   // res 10
        -1,      // res 11
        117649,  // res 12
        -1,      // res 13
        823543,  // res 14
        -1,      // res 15
        5764801, // res 16
    ];

    /**
     * Definition of which faces neighbor each other
     */
    private const FACE_NEIGHBORS = [
        [
            // face 0
            [0, [0, 0, 0], 0],  // central face
            [4, [2, 0, 2], 1],  // ij quadrant
            [1, [2, 2, 0], 5],  // ki quadrant
            [5, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 1
            [1, [0, 0, 0], 0],  // central face
            [0, [2, 0, 2], 1],  // ij quadrant
            [2, [2, 2, 0], 5],  // ki quadrant
            [6, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 2
            [2, [0, 0, 0], 0],  // central face
            [1, [2, 0, 2], 1],  // ij quadrant
            [3, [2, 2, 0], 5],  // ki quadrant
            [7, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 3
            [3, [0, 0, 0], 0],  // central face
            [2, [2, 0, 2], 1],  // ij quadrant
            [4, [2, 2, 0], 5],  // ki quadrant
            [8, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 4
            [4, [0, 0, 0], 0],  // central face
            [3, [2, 0, 2], 1],  // ij quadrant
            [0, [2, 2, 0], 5],  // ki quadrant
            [9, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 5
            [5, [0, 0, 0], 0],   // central face
            [10, [2, 2, 0], 3],  // ij quadrant
            [14, [2, 0, 2], 3],  // ki quadrant
            [0, [0, 2, 2], 3],   // jk quadrant
        ],
        [
            // face 6
            [6, [0, 0, 0], 0],   // central face
            [11, [2, 2, 0], 3],  // ij quadrant
            [10, [2, 0, 2], 3],  // ki quadrant
            [1, [0, 2, 2], 3],   // jk quadrant
        ],
        [
            // face 7
            [7, [0, 0, 0], 0],   // central face
            [12, [2, 2, 0], 3],  // ij quadrant
            [11, [2, 0, 2], 3],  // ki quadrant
            [2, [0, 2, 2], 3],   // jk quadrant
        ],
        [
            // face 8
            [8, [0, 0, 0], 0],   // central face
            [13, [2, 2, 0], 3],  // ij quadrant
            [12, [2, 0, 2], 3],  // ki quadrant
            [3, [0, 2, 2], 3],   // jk quadrant
        ],
        [
            // face 9
            [9, [0, 0, 0], 0],   // central face
            [14, [2, 2, 0], 3],  // ij quadrant
            [13, [2, 0, 2], 3],  // ki quadrant
            [4, [0, 2, 2], 3],   // jk quadrant
        ],
        [
            // face 10
            [10, [0, 0, 0], 0],  // central face
            [5, [2, 2, 0], 3],   // ij quadrant
            [6, [2, 0, 2], 3],   // ki quadrant
            [15, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 11
            [11, [0, 0, 0], 0],  // central face
            [6, [2, 2, 0], 3],   // ij quadrant
            [7, [2, 0, 2], 3],   // ki quadrant
            [16, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 12
            [12, [0, 0, 0], 0],  // central face
            [7, [2, 2, 0], 3],   // ij quadrant
            [8, [2, 0, 2], 3],   // ki quadrant
            [17, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 13
            [13, [0, 0, 0], 0],  // central face
            [8, [2, 2, 0], 3],   // ij quadrant
            [9, [2, 0, 2], 3],   // ki quadrant
            [18, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 14
            [14, [0, 0, 0], 0],  // central face
            [9, [2, 2, 0], 3],   // ij quadrant
            [5, [2, 0, 2], 3],   // ki quadrant
            [19, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 15
            [15, [0, 0, 0], 0],  // central face
            [16, [2, 0, 2], 1],  // ij quadrant
            [19, [2, 2, 0], 5],  // ki quadrant
            [10, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 16
            [16, [0, 0, 0], 0],  // central face
            [17, [2, 0, 2], 1],  // ij quadrant
            [15, [2, 2, 0], 5],  // ki quadrant
            [11, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 17
            [17, [0, 0, 0], 0],  // central face
            [18, [2, 0, 2], 1],  // ij quadrant
            [16, [2, 2, 0], 5],  // ki quadrant
            [12, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 18
            [18, [0, 0, 0], 0],  // central face
            [19, [2, 0, 2], 1],  // ij quadrant
            [17, [2, 2, 0], 5],  // ki quadrant
            [13, [0, 2, 2], 3],  // jk quadrant
        ],
        [
            // face 19
            [19, [0, 0, 0], 0],  // central face
            [15, [2, 0, 2], 1],  // ij quadrant
            [18, [2, 2, 0], 5],  // ki quadrant
            [14, [0, 2, 2], 3],  // jk quadrant
        ],
    ];

    /** @throws H3DomainException */
    public static function faceIjkToH3Index(FaceIJK $fijk, int $resolution): int
    {
        // initialize the index
        $h = Constants::H3_INIT;
        $h = H3Modification::h3Mode($h, H3IndexMode::H3_CELL_MODE);
        $h = H3Modification::h3Resolution($h , $resolution);

        if ($resolution === 0) {
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
            if (Math::isResolutionClassIII($r + 1) === 1) {
                // rotate ccw
                $ijk = $ijk->upAp7();
                $lastCenter = clone $ijk;
                $lastCenter = $lastCenter->downAp7();
            } else {
                // rotate cw
                $ijk = $ijk->upAp7r();
                $lastCenter = clone $ijk;
                $lastCenter = $lastCenter->downAp7r();
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
        if (BaseCell::isBaseCellPentagon($fijkBC->getBaseCell()['baseCell'])) {
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
     * @param FaceIJK $fijk
     * @param int $resolution
     * @return Vec3d
     */
    public static function faceIjkToVec3d(FaceIJK $fijk, int $resolution): Vec3d
    {
        $vec2d = CoordIJKConverter::coordIJKToVec2d($fijk->getCoord());
        return Vec2dConverter::hex2dToVec3($vec2d, $fijk->getFace(), $resolution, 0);
    }

    private static function baseCellIsCwOffset(int $baseCell, int $testFace): bool
    {
        $data = FaceProjection::BASE_CELL_DATA[$baseCell] ?? null;
        if ($data === null) {
            return false;
        }

        return $data[2][0] === $testFace || $data[2][1] === $testFace;
    }

    /**
     * Adjusts a FaceIJK address in place so that the resulting cell address is
     * relative to the correct icosahedral face
     * @param FaceIJK $fijk The FaceIJK address of the cell
     * @param int $resolution The H3 resolution of the cell
     * @param bool $pentLeading4 Whether or not the cell is a pentagon with a leading
     *         digit 4
     * @param int $substrate Whether or not the cell is in a substrate grid
     * @return array{fijk: FaceIJK, overage: Overage} overage 0 if on original face (no overage);
     *          1 if on face edge (only occurs on substrate grids);
     *          2 if overage on new face interior
 */
    public static function adjustOverageClassII(
        FaceIJK $fijk,
        int $resolution,
        bool $pentLeading4,
        int $substrate,
    ): array {
        $overage = Overage::NO_OVERAGE;
        $ijk = $fijk->getCoord();
        $maxDim = self::MAX_DIM_BY_C_I_I_RES[$resolution];
        // get the maximum dimension value; scale if a substrate grid
        if ($substrate === 1) {
            $maxDim *= 3;
        }

        // check for overage
        if ($substrate === 1 && $ijk->getI() + $ijk->getJ() + $ijk->getK() === $maxDim) { // on edge
            $overage = Overage::FACE_EDGE;
        } elseif ($ijk->getI() + $ijk->getJ() + $ijk->getK() > $maxDim) { // overage
            $overage = Overage::NEW_FACE;
            if ($ijk->getK() > 0) {
                if ($ijk->getJ() > 0) { // jk "quadrant"
                    $faceNeighbor = self::FACE_NEIGHBORS[$fijk->getFace()][FaceNeighbors::JK->value];
                    $fijkOrient = new FaceOrientIJK(
                        $faceNeighbor[0],
                        CoordIJK::fromArray($faceNeighbor[1]),
                        $faceNeighbor[2],
                    );
                } else { // ik "quadrant"
                    $faceNeighbor = self::FACE_NEIGHBORS[$fijk->getFace()][FaceNeighbors::KI->value];
                    $fijkOrient = new FaceOrientIJK(
                        $faceNeighbor[0],
                        CoordIJK::fromArray($faceNeighbor[1]),
                        $faceNeighbor[2],
                    );

                    // adjust for the pentagonal missing sequence
                    if ($pentLeading4) {
                        // translate origin to center of pentagon
                        $origin = new CoordIJK($maxDim, 0, 0);
                        $tmp = $ijk->sub($origin);
                        // rotate to adjust for the missing sequence
                        $tmp = $tmp->rotate60cw();
                        // translate the origin back to the center of the triangle
                        $ijk = $tmp->add($origin);
                    }
                }
            } else { // ij "quadrant"
                $faceNeighbor = self::FACE_NEIGHBORS[$fijk->getFace()][FaceNeighbors::IJ->value];
                $fijkOrient = new FaceOrientIJK(
                    $faceNeighbor[0],
                    CoordIJK::fromArray($faceNeighbor[1]),
                    $faceNeighbor[2],
                );
            }

            $fijk = new FaceIJK(
                face: $fijkOrient->face,
                coord: $fijk->getCoord(),
            );

            // rotate and translate for adjacent face
            for ($i = 0; $i < $fijkOrient->ccwRot60; $i++) {
                $ijk = $ijk->rotate60ccw();
            }

            $transVec = $fijkOrient->translate;
            $unitScale = self::UNIT_SCALE_BY_C_I_I_RES[$resolution];
            if ($substrate === 1) {
                $unitScale *= 3;
            }
            $transVec->scale($unitScale);
            $ijk = $ijk->add($transVec);
            $ijk = $ijk->normalize();

            // overage points on pentagon boundaries can end up on edges
            if ($substrate === 1 && $ijk->getI() + $ijk->getJ() + $ijk->getK() === $maxDim) { // on edge
                $overage = Overage::FACE_EDGE;
            }
        }

        return [
            'fijk' => $fijk,
            'overage' => $overage,
        ];
    }

}
