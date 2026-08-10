<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
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
            'room_type_id' => ['required','integer','exists:room_types,id'],
            'room_number' => ['required','string','max:255','unique:rooms,room_number'],
            'floor_no' => ['nullable','integer'],
            'status' => ['required', Rule::in([
                'available', 'occupied', 'reserved', 'maintenance', 'out_of_order',
            ])],
            'price' => ['required','numeric','decimal:2','min:0'],
            'is_active' => ['sometimes','boolean']
        ];
    }
}
