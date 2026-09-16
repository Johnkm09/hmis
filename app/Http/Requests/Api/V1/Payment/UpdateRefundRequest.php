<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'reason' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:pending,completed,failed'],
            'transaction_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
