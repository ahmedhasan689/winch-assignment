<?php

namespace Presentation\Dispatcher\Controllers;

use App\Http\Controllers\Controller;
use Domain\Order\Actions\AssignOrderAction;
use Presentation\Dispatcher\Resources\OrderResource;

final class OrderAssignmentController extends Controller
{
    public function __construct(
        private readonly AssignOrderAction $assignOrder,
    ) {}

    /**
     * POST /api/orders/{order}/assign
     *
     * No request body — the system selects the best driver automatically.
     */
    public function store(int $order): OrderResource
    {
        $assigned = $this->assignOrder->execute($order);
        $assigned->load('driver');

        return OrderResource::make($assigned);
    }
}
