<?php

namespace Domain\Order\Models;

use Domain\Driver\Exceptions\DriverNotAvailableException;
use Domain\Driver\Models\Driver;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Exceptions\OrderAlreadyAssignedException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'pickup_lat',
        'pickup_lng',
        'dropoff_lat',
        'dropoff_lng',
        'status',
        'driver_id',
        'assigned_at',
    ];

    /**
     * ! Casting
     * @var string[]
     */
    protected $casts = [
        'status' => OrderStatus::class,
        'assigned_at' => 'datetime',
        'pickup_lat' => 'float',
        'pickup_lng' => 'float',
        'dropoff_lat' => 'float',
        'dropoff_lng' => 'float',
    ];

    /**
     * ! Relations
     */

    /**
     * ? Driver Relation
     * @return BelongsTo
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * ? Assign the order to a specific driver.
     * Work rules apply:
     * 1. The order must be in the pending status.
     * 2. The driver must be available.
     * @throws OrderAlreadyAssignedException
     * @throws DriverNotAvailableException
     */
    public function assignTo(Driver $driver): void
    {
        if (!$this->status->isAssignable()) {
            throw OrderAlreadyAssignedException::forOrder($this->id);
        }

        if (!$driver->isAvailable()) {
            throw DriverNotAvailableException::forDriver($driver->id);
        }

        $this->driver_id = $driver->id;
        $this->status = OrderStatus::ASSIGNED;
        $this->assigned_at = now();
    }
}
