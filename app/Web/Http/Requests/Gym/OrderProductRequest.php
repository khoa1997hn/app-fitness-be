<?php

namespace App\Web\Http\Requests\Gym;

use App\Share\Enums\OrderType;
use App\Share\Enums\TherapyVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sản phẩm của đơn hàng, dùng chung cho hội viên tự đặt và nhân viên tạo hộ.
 */
class OrderProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(OrderType::getValues())],
            'package_id' => 'exclude_unless:type,'.OrderType::Package.'|required|integer',
            'therapy_service_id' => 'exclude_unless:type,'.OrderType::Therapy.'|required|integer|exists:therapy_services,id',
            'therapy_variant' => ['exclude_unless:type,'.OrderType::Therapy, 'required', Rule::in(TherapyVariant::getValues())],
            'event_id' => 'exclude_unless:type,'.OrderType::Event.'|required|integer|exists:events,id',
            'quantity' => 'exclude_unless:type,'.OrderType::ExtraPeople.','.OrderType::Event.'|required|integer|min:1|max:1000',
            'note' => 'nullable|string|max:1000',
        ];
    }
}
