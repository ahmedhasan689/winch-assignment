<?php

namespace Domain\Driver\Enums;

enum DriverStatus: string
{
    case OFFLINE = 'offline';
    case AVAILABLE = 'available';
    case BUSY = 'busy';

    /**
     * ? Can this driver accept a new order?
     * @return bool
     */
    public function canAcceptOrder(): bool
    {
        return $this === self::AVAILABLE;
    }
}
