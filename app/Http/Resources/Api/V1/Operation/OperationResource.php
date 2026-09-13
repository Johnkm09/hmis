<?php

namespace App\Http\Resources\Api\V1\Operation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'type' => $this->type,
            'performed_at' => $this->performed_at,
            'notes' => $this->notes,

            'performed_by' => [
                'id' => $this->performedBy->id,
                'name' => $this->performedBy->name,
                'email' => $this->performedBy->email,
            ],

            'created_at' => $this->created_at,
        ];
    }
}
