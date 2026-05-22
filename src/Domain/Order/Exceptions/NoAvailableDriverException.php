<?php

namespace Domain\Order\Exceptions;

use DomainException;

final class NoAvailableDriverException extends DomainException
{
    public static function forOrder(int $orderId): self
    {
        return new self("No available driver could be matched for order #{$orderId}.");
    }
}
