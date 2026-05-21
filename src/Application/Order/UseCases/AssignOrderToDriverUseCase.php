<?php

namespace Application\Order\UseCases;

use Application\Order\DTOs\AssignOrderCommand;
use Domain\Driver\Contracts\DriverRepositoryInterface;
use Domain\Order\Contracts\OrderRepositoryInterface;
use Domain\Order\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: Assigning an order to a driver.
 * Responsibilities:
 * 1. Protect the process from Race Conditions (pessimistic locking within a transaction).
 * 2. Coordinate cooperation between Order and Driver entities (no business logic here).
 * 3. Implement assignment rules from the domain (Order::assignTo, Driver::markBusy).
 *
 * Lock Notes:
 *
 * `lockForUpdate` only works within DB::transaction.
 *
 * The lock order is fixed (order then driver) to prevent deadlocks.
 *
 * DB::transaction has 3 attempts to handle rare deadlocks.
 */
final class AssignOrderToDriverUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly DriverRepositoryInterface $driverRepository,
    ) {}

    public function execute(AssignOrderCommand $command): Order
    {
        return DB::transaction(function () use ($command) {
            $order = $this->orderRepository->findForUpdate($command->orderId);

            $driver = $this->driverRepository->findForUpdate($command->driverId);

            $order->assignTo($driver);
            $driver->markBusy();

            $this->orderRepository->save($order);
            $this->driverRepository->save($driver);

            return $order;
        }, attempts: 3);
    }
}
