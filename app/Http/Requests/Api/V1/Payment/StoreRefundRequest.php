<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string'],
            'status' => ['required', 'in:pending,completed,failed'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
