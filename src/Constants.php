<?php

declare(strict_types=1);

namespace H3;

final readonly class Constants
{
    public const MAX_H3_RES = 15;
    /** threshold epsilon */
    public const EPSILON = 0.0000000000000001;

    /** @var float sqrt(3) / 2.0 */
    public const M_SQRT3_2 = 0.8660254037844386467637231707529361834714;
    public const M_2PI = 6.28318530717958647692528676655900576839433;

    /** scaling factor from hex2d resolution 0 unit length
     * (or distance between adjacent cell center points
     * on the plane) to gnomonic unit length. */
    public const RES0_U_GNOMONIC = 0.38196601125010500003;
    public const INV_RES0_U_GNOMONIC = 2.61803398874989588842;

    /** @var float square root of 7 */
    public const M_SQRT7 = 2.6457513110645905905016157536392604257102;
    /** @var float inverse square root of 7 */
    public const M_RSQRT7 = 0.37796447300922722721451653623418006081576;

    /** rotation angle between Class II and Class III resolution axes
     * (asin(sqrt(3.0 / 28.0)))
     */
    public const M_AP7_ROT_RADS = 0.333473172251832115336090755351601070065900389;
    /** 1/sin(60') **/
    public const M_RSIN60 = 1.1547005383792515290182975610039149112953;

    public const H3_INIT = 35184372088831;
    /** @var float one third */
    public const M_ONETHIRD = 0.333333333333333333333333333333333333333;
    public const M_ONESEVENTH = 0.14285714285714285714285714285714285;
    public const NUM_BASE_CELLS = 122;
}