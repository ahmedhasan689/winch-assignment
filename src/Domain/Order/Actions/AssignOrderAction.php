<?php

namespace Domain\Order\Actions;

use Domain\Driver\Contracts\DriverMatcherContract;
use Domain\Driver\Contracts\DriverRepositoryContract;
use Domain\Order\Contracts\OrderRepositoryContract;
use Domain\Order\Exceptions\NoAvailableDriverException;
use Domain\Order\Exceptions\OrderAlreadyAssignedException;
use Domain\Order\Models\Entities\Order;
use Domain\Shared\ValueObjects\Coordinates;
use Illuminate\Support\Facades\DB;

/**
 * Assigns an order to the best-matching driver.
 *
 * "Best" = available + nearest to pickup + has no active order.
 *
 * Race-condition safety:
 *   - Wrapped in DB::transaction with row locking.
 *   - Lock order first, then driver (consistent order avoids deadlocks).
 *   - On transient deadlock, auto-retries up to 3 times.
 */
final class AssignOrderAction
{
    public function __construct(
        private readonly OrderRepositoryContract $orderRepository,
        private readonly DriverRepositoryContract $driverRepository,
        private readonly DriverMatcherContract $driverMatcher,
    ) {}

    public function execute(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId) {
            // 1. Lock the order
            $order = $this->orderRepository->findForUpdate($orderId);

            // 2. Validate it's still assignable (fast-fail before matching)
            if (! $order->isAssignable()) {
                throw OrderAlreadyAssignedException::forOrder($orderId);
            }

            // 3. Find best driver based on pickup location
            $pickup = new Coordinates(
                latitude: $order->pickup_lat,
                longitude: $order->pickup_lng,
            );

            $bestDriver = $this->driverMatcher->findBestMatch($pickup);

            if ($bestDriver === null) {
                throw NoAvailableDriverException::forOrder($orderId);
            }

            // 4. Lock the chosen driver and re-verify availability
            //    (driver might have been taken between matching and locking)
            $driver = $this->driverRepository->findForUpdate($bestDriver->id);

            // 5. Domain rules — Order::assignTo throws if driver no longer available
            $order->assignTo($driver);
            $driver->markBusy();

            // 6. Persist
            $this->orderRepository->save($order);
            $this->driverRepository->save($driver);

            return $order;
        }, attempts: 3);
    }
}
