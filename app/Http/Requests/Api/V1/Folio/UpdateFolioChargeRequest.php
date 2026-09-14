<?php

namespace App\Http\Requests\Api\V1\Folio;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFolioChargeRequest extends FormRequest
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
            'description' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'quantity' => [
                'sometimes',
                'numeric',
                'min:0.01',
            ],

            'unit_price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
        ];
    }
}
