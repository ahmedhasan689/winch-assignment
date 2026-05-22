<?php

namespace Domain\Order\Contracts;

use Domain\Order\DataTransferObjects\OrderFiltersData;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryContract
{
    public function findOrFail(int $id): Order;

    public function findForUpdate(int $id): Order;

    public function save(Order $order): void;

    /**
     * Paginated orders for a driver, optionally filtered by status.
     *
     * @param int $driverId
     * @param OrderFiltersData $filters
     * @return LengthAwarePaginator
     */
    public function paginateForDriver(
        int $driverId,
        OrderFiltersData $filters,
    ): LengthAwarePaginator;
}
