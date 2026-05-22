<?php

namespace Domain\Order\Actions;

use Domain\Order\Contracts\OrderRepositoryContract;
use Domain\Order\DataTransferObjects\OrderFiltersData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryContract $orderRepository,
    ) {}

    public function execute(OrderFiltersData $filters): LengthAwarePaginator
    {
        return $this->orderRepository->getAllOrders($filters);
    }
}
