<?php

namespace Domain\Driver\Models\Entities;

use Database\Factories\DriverFactory;
use Domain\Driver\Enums\DriverStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\Factory;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'drivers';

    protected $fillable = [
        'name',
        'phone',
        'status',
        'current_lat',
        'current_lng',
    ];

    /**
     * ! Casting
     * @var string[]
     */
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

    public function isAvailable(): bool
    {
        return $this->status->canAcceptOrder();
    }

    public function markBusy(): void
    {
        $this->status = DriverStatus::BUSY;
    }

    public function markAvailable(): void
    {
        $this->status = DriverStatus::AVAILABLE;
    }

    protected static function newFactory(): Factory
    {
        return DriverFactory::new();
    }
}
