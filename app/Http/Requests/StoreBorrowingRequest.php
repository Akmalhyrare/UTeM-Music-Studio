<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Services\BorrowingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_date'         => 'required|date|after_or_equal:today',
            'return_date'         => 'required|date|after_or_equal:pickup_date',
            'purpose'             => 'nullable|string|max:255',
            'items'               => 'required|array|min:1',
            'items.*.item_id'     => 'required|integer|exists:items,item_id|distinct',
            'items.*.quantity'    => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'           => 'Please select at least one item to borrow.',
            'items.*.item_id.distinct' => 'Each item can only appear once in a request.',
            'pickup_date.after_or_equal' => 'The pickup date must be today or later.',
            'return_date.after_or_equal' => 'The return date must be on or after the pickup date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pickupDate = $this->input('pickup_date');
            $returnDate = $this->input('return_date');

            if (!$pickupDate || !$returnDate || !is_array($this->input('items'))) {
                return;
            }

            $service = app(BorrowingService::class);

            foreach ($this->input('items') as $index => $item) {
                if (empty($item['item_id']) || empty($item['quantity'])) {
                    continue;
                }

                $available = $service->getAvailableQuantity((int) $item['item_id'], $pickupDate, $returnDate);

                if ((int) $item['quantity'] > $available) {
                    $itemName = Item::find($item['item_id'])->item_name ?? 'this item';
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Only {$available} unit(s) of '{$itemName}' are available during the selected reservation period."
                    );
                }
            }
        });
    }
}
