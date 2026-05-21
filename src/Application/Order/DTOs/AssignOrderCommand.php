<?php

namespace Application\Order\DTOs;

/**
 * Data Transfer Object (DTO) for assignment order.
 * Immutable
 * No validation here (done in Form Request)
 * Minimum data required to implement Use Case
 */
final readonly class AssignOrderCommand
{
    public function __construct(
        public int $orderId,
        public int $driverId,
    ) {}
}
