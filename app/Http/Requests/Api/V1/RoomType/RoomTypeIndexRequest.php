<?php

namespace App\Http\Requests\Api\V1\RoomType;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RoomTypeIndexRequest extends FormRequest
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
            'filter.name' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:name,created_at,-name,-created_at'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
