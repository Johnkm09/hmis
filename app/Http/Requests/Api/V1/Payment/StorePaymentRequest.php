<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'method' => [
                'required',
                'in:mpesa,stripe,cash',
            ],

            'provider' => [
                'nullable',
                'string',
                'max:255',
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'in:pending,completed,failed',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
