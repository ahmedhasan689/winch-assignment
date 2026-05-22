<?php

declare(strict_types=1);

namespace Domain\Driver\Services;

use Domain\Driver\Contracts\DriverMatcherContract;
use Domain\Driver\Contracts\DriverRepositoryContract;
use Domain\Driver\Models\Entities\Driver;
use Domain\Shared\ValueObjects\Coordinates;

/**
 * Finds the geographically nearest available driver
 * who has no active orders.
 *
 * Computes distance in PHP using Haversine (see Coordinates VO).
 *
 * NOTE: For massive datasets, prefer pushing distance calculation to
 * the database (MySQL ST_Distance_Sphere) or using spatial indexes.
 * See ARCHITECTURE.md for the scaling discussion.
 */
final class NearestAvailableDriverMatcher implements DriverMatcherContract
{
    public function __construct(
        private readonly DriverRepositoryContract $driverRepository,
    ) {}

    public function findBestMatch(Coordinates $pickup): ?Driver
    {
        $candidates = $this->driverRepository->availableDriversWithoutActiveOrders();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortBy(fn (Driver $driver) => $pickup->distanceTo(
                new Coordinates($driver->current_lat, $driver->current_lng)
            ))
            ->first();
    }
}
