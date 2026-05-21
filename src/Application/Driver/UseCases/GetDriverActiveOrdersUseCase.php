<?php

namespace Application\Driver\UseCases;

use Domain\Driver\Contracts\DriverRepositoryInterface;
use Domain\Order\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Use Case: Fetches active requests for a driver.
 * Verifies that the driver exists (otherwise a 404 error occurs).
 * Utilizes the composite index (driver_id, status).
 * No transaction or lock required — read-only.
 */
final class GetDriverActiveOrdersUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly DriverRepositoryInterface $driverRepository,
    ) {}

    public function execute(int $driverId): Collection
    {
        $this->driverRepository->findOrFail($driverId);

        return $this->orderRepository->activeOrdersForDriver($driverId);
    }
}
