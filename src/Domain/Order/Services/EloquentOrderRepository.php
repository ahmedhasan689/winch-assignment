<?php

namespace Domain\Order\Services;

use Domain\Order\Contracts\OrderRepositoryContract;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Domain\Order\DataTransferObjects\OrderFiltersData;

final class EloquentOrderRepository implements OrderRepositoryContract
{
    public function findOrFail(int $id): Order
    {
        return Order::query()->findOrFail($id);
    }

    public function findForUpdate(int $id): Order
    {
        return Order::query()->lockForUpdate()->findOrFail($id);
    }

    public function save(Order $order): void
    {
        $order->save();
    }

    public function paginateForDriver(
        int $driverId,
        OrderFiltersData $filters,
    ): LengthAwarePaginator {
        return Order::query()
            ->where('driver_id', $driverId)
            ->when(
                $filters->status,
                fn ($query, OrderStatus $status) => $query->where('status', $status->value),
            )
            ->orderByDesc('assigned_at')
            ->paginate(
                perPage: $filters->perPage,
                page: $filters->page,
            );
    }

    public function getAllOrders(OrderFiltersData $filters): LengthAwarePaginator
    {
        return Order::query()
            ->with('driver')
            ->when(
                $filters->status,
                fn ($query, OrderStatus $status) => $query->where('status', $status->value),
            )
            ->orderByDesc('created_at')
            ->paginate(
                perPage: $filters->perPage,
                page: $filters->page,
            );
    }
}
