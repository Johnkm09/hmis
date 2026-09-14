<?php

namespace App\Http\Requests\Api\V1\Service;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:services,name',],
            'description' => ['nullable', 'string',],
            'price' => ['required', 'numeric', 'min:0',],
            'is_active' => ['sometimes', 'boolean',],
        ];
    }
}
