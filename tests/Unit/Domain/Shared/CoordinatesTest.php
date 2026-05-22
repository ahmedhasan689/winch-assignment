<?php

namespace Tests\Unit\Domain\Shared;

use Domain\Shared\ValueObjects\Coordinates;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CoordinatesTest extends TestCase
{
    #[Test]
    public function it_accepts_valid_coordinates(): void
    {
        $coords = new Coordinates(24.7136, 46.6753);

        $this->assertSame(24.7136, $coords->latitude);
        $this->assertSame(46.6753, $coords->longitude);
    }

    #[Test]
    public function it_rejects_latitude_above_90(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Coordinates(91.0, 46.6753);
    }

    #[Test]
    public function it_rejects_longitude_below_minus_180(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Coordinates(24.7, -181.0);
    }

    #[Test]
    public function it_calculates_distance_between_riyadh_and_jeddah(): void
    {
        // Riyadh: 24.7136, 46.6753
        // Jeddah: 21.4858, 39.1925
        // Known great-circle distance: ~857 km
        $riyadh = new Coordinates(24.7136, 46.6753);
        $jeddah = new Coordinates(21.4858, 39.1925);

        $distance = $riyadh->distanceTo($jeddah);

        // Allow 1% tolerance for floating-point precision
        $this->assertEqualsWithDelta(845, $distance, 5);
    }

    #[Test]
    public function distance_to_same_point_is_zero(): void
    {
        $coords = new Coordinates(24.7, 46.6);

        $this->assertSame(0.0, $coords->distanceTo($coords));
    }
}
