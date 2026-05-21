<?php

namespace Infrastructure\Persistence\Eloquent\Repositories;

use Domain\Order\Contracts\OrderRepositoryInterface;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Order;
use Illuminate\Database\Eloquent\Collection;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function findOrFail(int $id): Order
    {
        return Order::query()->findOrFail($id);
    }

    /**
     * ? The row is locked with SELECT ... FOR UPDATE.
     * ? Only works within DB::transaction; otherwise, there is no true lock.
     */
    public function findForUpdate(int $id): Order
    {
        return Order::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function save(Order $order): void
    {
        $order->save();
    }

    /**
     * ? Active requests (assigned/in_progress) for a specific driver.
     * ? Utilizes the Composite Index (driver_id, status) we created in the migration.
     * @return Collection<int, Order>
     */
    public function activeOrdersForDriver(int $driverId): Collection
    {
        return Order::query()
            ->where('driver_id', $driverId)
            ->whereIn('status', [
                OrderStatus::ASSIGNED->value,
                OrderStatus::IN_PROGRESS->value,
            ])
            ->orderByDesc('assigned_at')
            ->get();
    }
}
