<?php

declare(strict_types=1);

namespace Presentation\Dispatcher\Controllers;

use App\Http\Controllers\Controller;
use Domain\Driver\Actions\GetDriverOrdersAction;
use Domain\Order\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Presentation\Dispatcher\Requests\GetDriverOrdersRequest;
use Presentation\Dispatcher\Resources\OrderResource;

final class DriverOrdersController extends Controller
{
    public function __construct(
        private readonly GetDriverOrdersAction $getDriverOrders,
    ) {}

    /**
     * GET /api/drivers/{driver}/orders
     *
     * Query params:
     *   - status: comma-separated list (e.g. "assigned,in_progress")
     *   - per_page: results per page (default 15, max 100)
     */
    public function index(GetDriverOrdersRequest $request, int $driver): AnonymousResourceCollection
    {
        $statuses = $this->parseStatusFilter($request->query('status'));
        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = $this->getDriverOrders->execute(
            driverId: $driver,
            filters: $request->toFilters()
        );

        return OrderResource::collection($paginator);
    }

    /**
     * Parse "assigned,in_progress" into [OrderStatus::ASSIGNED, OrderStatus::IN_PROGRESS].
     * Invalid values are silently skipped.
     *
     * @return array<OrderStatus>|null
     */
    private function parseStatusFilter(?string $raw): ?array
    {
        if (!$raw) {
            return null;
        }

        $statuses = [];
        foreach (explode(',', $raw) as $value) {
            $status = OrderStatus::tryFrom(trim($value));
            if ($status !== null) {
                $statuses[] = $status;
            }
        }

        return $statuses === [] ? null : $statuses;
    }
}
