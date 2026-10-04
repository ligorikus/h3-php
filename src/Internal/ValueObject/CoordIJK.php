<?php

declare(strict_types=1);

namespace H3\Internal\ValueObject;

use H3\Internal\Constants;
use H3\Internal\Enum\Direction;

/**
 * IJK hexagon coordinates
 *
 * Each axis is spaced 120 degrees apart.
 */
final readonly class CoordIJK
{
    private const UNIT_VECS  = [
        [0, 0, 0], // direction 0
        [0, 0, 1], // direction 1
        [0, 1, 0], // direction 2
        [0, 1, 1], // direction 3
        [1, 0, 0], // direction 4
        [1, 0, 1], // direction 5
        [1, 1, 0], // direction 6
    ];

    public function __construct(
        private int $i, ///< i component
        private int $j, ///< j component
        private int $k, ///< k component
    ) {}

    /**
     * @param array{int, int, int} $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self($array[0], $array[1], $array[2]);
    }

    public function getI(): int
    {
        return $this->i;
    }

    public function getJ(): int
    {
        return $this->j;
    }

    public function getK(): int
    {
        return $this->k;
    }

    public function normalize(): self
    {
        $i = $this->i;
        $j = $this->j;
        $k = $this->k;

        if ($i < 0) {
            $j -= $i;
            $k -= $i;
            $i = 0;
        }

        if ($j < 0) {
            $i -= $j;
            $k -= $j;
            $j = 0;
        }

        if ($k < 0) {
            $i -= $k;
            $j -= $k;
            $k = 0;
        }

        $min = $i;
        if ($j < $min) $min = $j;
        if ($k < $min) $min = $k;
        if ($min > 0) {
            $i -= $min;
            $j -= $min;
            $k -= $min;
        }

        return new self($i, $j, $k);
    }

    public function scale(int $factor): self
    {
        return new self(
            i: $this->i * $factor,
            j: $this->j * $factor,
            k: $this->k * $factor,
        );
    }

    public function add(CoordIJK $ijk): self
    {
        return new self(
            i: $this->i + $ijk->getI(),
            j: $this->j + $ijk->getJ(),
            k: $this->k + $ijk->getK(),
        );
    }

    public function sub(CoordIJK $ijk): self
    {
        return new self(
            i: $this->i - $ijk->getI(),
            j: $this->j - $ijk->getJ(),
            k: $this->k - $ijk->getK(),
        );
    }

    public function toDigit(): int
    {
        $c = $this->normalize();
        $digit = Direction::INVALID_DIGIT;
        for ($i = 0; $i < 7; $i++) {
            $unitVec = self::UNIT_VECS[$i];
            if ($c->matches(new self($unitVec[0], $unitVec[1], $unitVec[2]))) {
                $digit = $i;
                break;
            }
        }
        return $digit;
    }

    public function matches(CoordIJK $c): bool
    {
        return ($this->getI() === $c->getI() && $this->getJ() === $c->getJ() && $this->getK() === $c->getK());
    }

    /**
     * Find the normalized ijk coordinates of the indexing parent of a cell in a
     * counter-clockwise aperture 7 grid. Works in place.
     *
     * @return self
     */
    public function upAp7(): self
    {
        $i = $this->getI() - $this->getK();
        $j = $this->getJ() - $this->getK();

        $newI = (int)round(num: floatval(3 * $i - $j) * Constants::M_ONESEVENTH);
        $newJ = (int)round(num: floatval($i + 2 * $j) * Constants::M_ONESEVENTH);

        $newK = 0;
        return (new CoordIJK(
            i: $newI,
            j: $newJ,
            k: $newK
        ))->normalize();
    }

    public function upAp7r(): self
    {
        $i = $this->getI() - $this->getK();
        $j = $this->getJ() - $this->getK();

        $newI = (int)round(num: floatval(2 * $i + $j) * Constants::M_ONESEVENTH);
        $newJ = (int)round(num: floatval(3 * $j - $i) * Constants::M_ONESEVENTH);
        $newK = 0;

        return (new CoordIJK(
            i: $newI,
            j: $newJ,
            k: $newK
        ))->normalize();
    }

    public function downAp7(): self
    {
        $iVec = new CoordIJK(3, 0, 1);
        $jVec = new CoordIJK(1, 3, 0);
        $kVec = new CoordIJK(0, 1, 3);

        $iVec = $iVec->scale($this->getI());
        $jVec = $jVec->scale($this->getJ());
        $kVec = $kVec->scale($this->getK());

        $ijk = $iVec->add($jVec);
        $ijk = $ijk->add($kVec);

        return $ijk->normalize();
    }

    public function downAp7r(): self
    {
        $iVec = new CoordIJK(3, 1, 0);
        $jVec = new CoordIJK(0, 3, 1);
        $kVec = new CoordIJK(1, 0, 3);

        $iVec = $iVec->scale($this->getI());
        $jVec = $jVec->scale($this->getJ());
        $kVec = $kVec->scale($this->getK());

        $ijk = $iVec->add($jVec);
        $ijk = $ijk->add($kVec);

        return $ijk->normalize();
    }

    public function neighbor(int $digit): self
    {
        $ijk = $this;
        if ($digit > Direction::CENTER_DIGIT && $digit < Direction::NUM_DIGITS) {
            $ijk = $ijk->add(
                self::fromArray(self::UNIT_VECS[$digit])
            );
            $ijk = $ijk->normalize();
        }
        return $ijk;
    }

    /**
     * Rotates ijk coordinates 60 degrees counter-clockwise. Works in place
     *
     * @return self
     */
    public function rotate60ccw(): self
    {
        // unit vector rotations
        $iVec = new CoordIJK(1, 1, 0);
        $jVec = new CoordIJK(0, 1, 1);
        $kVec = new CoordIJK(1, 0, 1);

        $iVec = $iVec->scale($this->getI());
        $jVec = $jVec->scale($this->getJ());
        $kVec = $kVec->scale($this->getK());

        $ijk = $iVec->add($jVec);
        $ijk = $ijk->add($kVec);

        return $ijk->normalize();
    }

    /**
     * Rotates ijk coordinates 60 degrees clockwise. Works in place
     *
     * @return self
     */
    public function rotate60cw(): self
    {
        // unit vector rotations
        $iVec = new CoordIJK(1, 0, 1);
        $jVec = new CoordIJK(1, 1, 0);
        $kVec = new CoordIJK(0, 1, 1);

        $iVec = $iVec->scale($this->getI());
        $jVec = $jVec->scale($this->getJ());
        $kVec = $kVec->scale($this->getK());

        $ijk = $iVec->add($jVec);
        $ijk = $ijk->add($kVec);

        return $ijk->normalize();
    }
}