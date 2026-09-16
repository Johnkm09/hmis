<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'sometimes',
                'numeric',
                'gt:0',
            ],

            'method' => [
                'sometimes',
                'in:mpesa,stripe,cash',
            ],

            'provider' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'transaction_reference' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'sometimes',
                'in:pending,completed,failed',
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }
}
