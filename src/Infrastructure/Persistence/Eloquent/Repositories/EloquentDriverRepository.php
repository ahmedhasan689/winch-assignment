<?php

namespace Infrastructure\Persistence\Eloquent\Repositories;

use Domain\Driver\Contracts\DriverRepositoryInterface;
use Domain\Driver\Models\Driver;

final class EloquentDriverRepository implements DriverRepositoryInterface
{
    public function findOrFail(int $id): Driver
    {
        return Driver::query()->findOrFail($id);
    }

    public function findForUpdate(int $id): Driver
    {
        return Driver::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function save(Driver $driver): void
    {
        $driver->save();
    }
}
