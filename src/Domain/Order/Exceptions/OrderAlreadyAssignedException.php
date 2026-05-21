<?php

namespace Domain\Order\Exceptions;

use DomainException;
final class OrderAlreadyAssignedException extends DomainException
{
    public static function forOrder(int $orderId): self
    {
        return new self("Order #{$orderId} is already assigned and cannot be reassigned.");
    }
}
