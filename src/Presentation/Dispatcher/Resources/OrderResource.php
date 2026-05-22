<?php

namespace Presentation\Dispatcher\Resources;

use Domain\Order\Models\Entities\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'customer' => [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
            ],
            'pickup' => [
                'lat' => $this->pickup_lat,
                'lng' => $this->pickup_lng,
            ],
            'dropoff' => [
                'lat' => $this->dropoff_lat,
                'lng' => $this->dropoff_lng,
            ],
            'driver' => DriverResource::make($this->whenLoaded('driver')),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
