<?php

namespace Domain\Driver\Contracts;

use Domain\Driver\Models\Entities\Driver;
use Domain\Shared\ValueObjects\Coordinates;

interface DriverMatcherContract
{
    /**
     * Find the best-matching driver for a given pickup point.
     * "Best" criteria are implementation-specific (default: nearest available).
     */
    public function findBestMatch(Coordinates $pickup): ?Driver;
}
