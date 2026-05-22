<?php

namespace Domain\Driver\Actions;

use Domain\Driver\Contracts\DriverRepositoryContract;
use Domain\Order\Contracts\OrderRepositoryContract;
use Domain\Order\DataTransferObjects\OrderFiltersData;
use Domain\Order\Enums\OrderStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetDriverOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryContract $orderRepository,
        private readonly DriverRepositoryContract $driverRepository,
    ) {}

    /**
     * @param  array<OrderStatus>|null  $statuses  optional status filter
     */
    public function execute(
        int $driverId,
        OrderFiltersData $filters,
    ): LengthAwarePaginator {
        $this->driverRepository->findOrFail($driverId);

        return $this->orderRepository->paginateForDriver(
            driverId: $driverId,
            filters: $filters
        );
    }
}
