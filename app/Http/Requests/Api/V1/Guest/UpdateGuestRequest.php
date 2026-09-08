<?php

namespace App\Http\Requests\Api\V1\Guest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuestRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'   => ['sometimes', 'string', 'max:100'],
            'last_name'    => ['sometimes', 'string', 'max:100'],
            'id_number'    => ['sometimes', 'string', 'max:150', Rule::unique('guests', 'id_number')->ignore($this->route('id'))],
            'phone_number' => ['sometimes', 'string', 'max:20'],
            'email'        => ['sometimes', 'nullable', 'email', 'max:255'],
            'country'      => ['sometimes', 'string', 'max:100'],
            'city'         => ['sometimes', 'string', 'max:100'],
            'address'      => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
