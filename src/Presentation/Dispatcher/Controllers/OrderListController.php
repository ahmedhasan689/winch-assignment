<?php

namespace Presentation\Dispatcher\Controllers;

use App\Http\Controllers\Controller;
use Domain\Order\Actions\ListOrdersAction;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Presentation\Dispatcher\Requests\ListOrdersRequest;
use Presentation\Dispatcher\Resources\OrderResource;

final class OrderListController extends Controller
{
    public function __construct(
        private readonly ListOrdersAction $action,
    ) {}

    public function index(ListOrdersRequest $request): AnonymousResourceCollection
    {
        $orders = $this->action->execute($request->toFilters());

        return OrderResource::collection($orders);
    }
}
