<?php

namespace Domain\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException(
                "Latitude must be between -90 and 90, got {$latitude}"
            );
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException(
                "Longitude must be between -180 and 180, got {$longitude}"
            );
        }
    }

    /**
     * ? Distance in kilometers to another point (Haversine formula).
     * ? Useful later when choosing the nearest driver.
     */
    public function distanceTo(self $other): float
    {
        $earthRadiusKm = 6371;

        $latFrom = deg2rad($this->latitude);
        $latTo = deg2rad($other->latitude);
        $deltaLat = deg2rad($other->latitude - $this->latitude);
        $deltaLng = deg2rad($other->longitude - $this->longitude);

        $a = sin($deltaLat / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
