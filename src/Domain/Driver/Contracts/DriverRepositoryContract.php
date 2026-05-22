<?php

namespace Domain\Driver\Contracts;

use Domain\Driver\Models\Entities\Driver;
use Illuminate\Support\Collection;

interface DriverRepositoryContract
{
    public function findOrFail(int $id): Driver;

    public function findForUpdate(int $id): Driver;

    public function save(Driver $driver): void;

    /**
     * Available drivers with NO active orders.
     * Used by the matcher to find candidates.
     *
     * @return Collection<int, Driver>
     */
    public function availableDriversWithoutActiveOrders(): Collection;
}
