<?php

namespace App\Http\Requests\Api\V1\Guest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GuestRequest extends FormRequest
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
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'id_number'    => ['required', 'string', 'max:150', 'unique:guests,id_number'],
            'phone_number' => ['required', 'string', 'max:20'],
            'email'        => ['nullable', 'email', 'max:255'],
            'country'      => ['required', 'string', 'max:100'],
            'city'         => ['required', 'string', 'max:100'],
            'address'      => ['nullable', 'string', 'max:255'],
        ];
    }
}
