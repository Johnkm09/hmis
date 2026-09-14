<?php

namespace App\Http\Requests\Api\V1\Folio;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolioChargeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'in:accommodation,service,other',
            ],

            'service_id' => [
                'nullable',
                'integer',
                'exists:services,id',
                'required_if:type,service',
                'prohibited_unless:type,service',
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }
}
