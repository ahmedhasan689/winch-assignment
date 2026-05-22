<?php

declare(strict_types=1);

namespace Presentation\Dispatcher\Resources;

use Domain\Driver\Models\Entities\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Driver
 */
final class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'current_location' => [
                'lat' => $this->current_lat,
                'lng' => $this->current_lng,
            ],
        ];
    }
}
