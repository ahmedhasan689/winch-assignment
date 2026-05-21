<?php

namespace Domain\Driver\Contracts;

use Domain\Driver\Models\Driver;

interface DriverRepositoryInterface
{
    public function findOrFail(int $id): Driver;

    /**
     * The driver is fetched with a row lock.
     * Used within the transaction assignment to prevent allocation to two simultaneous orders.
     */
    public function findForUpdate(int $id): Driver;

    public function save(Driver $driver): void;
}
