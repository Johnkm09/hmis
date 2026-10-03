<?php

namespace App\Http\Resources\Api\V1\RoomType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'max_occupancy' => $this->max_occupancy,
            'is_active' => $this->is_active,
            'images' => RoomTypeImageResource::collection(
                $this->whenLoaded('images')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
