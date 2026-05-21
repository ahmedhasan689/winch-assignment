<?php

namespace Domain\Driver\Models;

use Domain\Driver\Enums\DriverStatus;
use Domain\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'status',
        'current_lat',
        'current_lng',
    ];

    protected $casts = [
        'status' => DriverStatus::class,
        'current_lat' => 'float',
        'current_lng' => 'float',
    ];

    /**
     * ! Relations
     */

    /**
     * ? Orders Relation
     * @return HasMany
     */

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }


    /**
     * ? Domain Behavior
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->status->canAcceptOrder();
    }

    /**
     * ? The driver is placed in "busy" mode.
     * ? He is automatically called when a order is assigned to him.
     */
    public function markBusy(): void
    {
        $this->status = DriverStatus::BUSY;
    }

    public function markAvailable(): void
    {
        $this->status = DriverStatus::AVAILABLE;
    }
}
