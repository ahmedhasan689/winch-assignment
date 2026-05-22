<?php

namespace Domain\Order\DataTransferObjects;


namespace Domain\Order\DataTransferObjects;

use Domain\Order\Enums\OrderStatus;

/**
 * ? used in GET /api/drivers/{id}/orders
 */
final readonly class OrderFiltersData
{
    public function __construct(
        public ?OrderStatus $status = null,
        public int $perPage = 15,
        public int $page = 1,
    ){}

    public static function fromArray(array $data): self
    {
        return new self(
            status: isset($data['status'])
                ? OrderStatus::from($data['status'])
                : null,
            perPage: (int) ($data['per_page'] ?? 15),
            page: (int) ($data['page'] ?? 1),
        );
    }
}
