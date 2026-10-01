<?php

namespace App\Http\Requests\Api\V1\RoomType;

use Illuminate\Foundation\Http\FormRequest;

class RoomTypeImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}
