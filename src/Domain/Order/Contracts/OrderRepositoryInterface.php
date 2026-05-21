<?php

namespace Domain\Order\Contracts;

use Domain\Order\Models\Order;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    public function findOrFail(int $id): Order;


    /**
     * ? Fetches the request with a row lock (SELECT ... FOR UPDATE).
     * ! Must only be used within DB::transaction.
     * ? Prevents race conditions during concurrent assignment.
     */
    public function findForUpdate(int $id): Order;

    public function save(Order $order): void;

    /**
     * @return Collection<int, Order>
     */
    public function activeOrdersForDriver(int $driverId): Collection;
}
