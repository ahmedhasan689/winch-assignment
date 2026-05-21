<?php

namespace Domain\Driver\Exceptions;

use DomainException;
final class DriverNotAvailableException extends DomainException
{
    public static function forDriver(int $driverId): self
    {
        return new self("Driver #{$driverId} is not available to accept new orders.");
    }
}
