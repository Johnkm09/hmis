<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class MpesaStkPushRequest extends FormRequest
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

            'phone' => [
                'required',
                'string',
                'regex:/^(?:\+?254|0)(?:7|1)\d{8}$/',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace(
            '/[\s-]+/',
            '',
            $this->phone ?? ''
        );

        if (str_starts_with($phone, '+254')) {
            $phone = substr($phone, 1);
        } elseif (
            str_starts_with($phone, '07') ||
            str_starts_with($phone, '01')
        ) {
            $phone = '254' . substr($phone, 1);
        }

        $this->merge([
            'phone' => $phone,
        ]);
    }
}
