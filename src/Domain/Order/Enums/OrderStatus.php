<?php

namespace Domain\Order\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * ? Is the order active? (The driver is currently working on it)
     * ? Used by endpoint: GET /api/drivers/{id}/orders
     * @return bool
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::ASSIGNED,
            self::IN_PROGRESS,
        ], true);
    }

    /**
     * ? Can this order be assigned?
     * ? Only pending requests are assignable.
     * @return bool
     */
    public function isAssignable(): bool
    {
        return $this === self::PENDING;
    }

}
