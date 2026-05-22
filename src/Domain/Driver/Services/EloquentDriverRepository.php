<?php

namespace Domain\Driver\Services;

use Domain\Driver\Contracts\DriverRepositoryContract;
use Domain\Driver\Enums\DriverStatus;
use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Support\Collection;

final class EloquentDriverRepository implements DriverRepositoryContract
{
    public function findOrFail(int $id): Driver
    {
        return Driver::query()->findOrFail($id);
    }

    public function findForUpdate(int $id): Driver
    {
        return Driver::query()->lockForUpdate()->findOrFail($id);
    }

    public function save(Driver $driver): void
    {
        $driver->save();
    }

    public function availableDriversWithoutActiveOrders(): Collection
    {
        return Driver::query()
            ->where('status', DriverStatus::AVAILABLE->value)
            ->whereNotIn('id', function ($subquery) {
                $subquery
                    ->select('driver_id')
                    ->from((new Order())->getTable())
                    ->whereIn('status', [
                        OrderStatus::ASSIGNED->value,
                        OrderStatus::IN_PROGRESS->value,
                    ])
                    ->whereNotNull('driver_id');
            })
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->get();
    }
}
