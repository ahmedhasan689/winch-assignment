<?php

namespace Presentation\Dispatcher\Requests;

use Domain\Order\DataTransferObjects\OrderFiltersData;
use Domain\Order\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GetDriverOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(OrderStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toFilters(): OrderFiltersData
    {
        return OrderFiltersData::fromArray($this->validated());
    }
}
