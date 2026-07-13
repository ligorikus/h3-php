<?php

declare(strict_types=1);

namespace H3;

final readonly class Constants
{
    public const MAX_H3_RES = 15;
    /** threshold epsilon */
    public const EPSILON = 0.0000000000000001;
    /** The number of faces on an icosahedron */
    public const NUM_ICOSA_FACES = 20;
    public const M_2PI = 6.28318530717958647692528676655900576839433;
    public const INV_RES0_U_GNOMONIC = 2.61803398874989588842;
    public const M_SQRT7 = 2.6457513110645905905016157536392604257102;

    /** rotation angle between Class II and Class III resolution axes
     * (asin(sqrt(3.0 / 28.0)))
     */
    public const M_AP7_ROT_RADS = 0.333473172251832115336090755351601070065900389;
    /** 1/sin(60') **/
    public const M_RSIN60 = 1.1547005383792515290182975610039149112953;

    public const H3_INIT = 35184372088831;
    public const M_ONESEVENTH = 0.14285714285714285714285714285714285;
    public const NUM_BASE_CELLS = 122;
}