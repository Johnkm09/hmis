<?php

namespace App\Http\Requests\Api\V1\Room;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
                'room_type_id' => ['sometimes','integer','exists:room_types,id'],
                'room_number' => [
                    'sometimes',
                    'string',
                    'max:255',
                    Rule::unique('rooms','room_number')->ignore($this->route('room'))
                ],
                'floor_no' => ['sometimes','nullable', 'integer'],
                'status' => ['sometimes', 
                            Rule::in([
                                        'available', 
                                        'occupied', 
                                        'reserved', 
                                        'maintenance', 
                                        'out_of_order',
                            ]),
                ],
                'price' => ['sometimes','numeric','decimal:2','min:0'],
                'is_active' => ['sometimes','boolean']
        ];
    }
}
