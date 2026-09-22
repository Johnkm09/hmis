<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StripePaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folio_id' => [
                'required',
                'integer',
                'exists:folios,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ];
    }
}
