<?php

namespace App\Http\Resources\Api\V1\Folio;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolioChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio_id' => $this->folio_id,
            'service_id' => $this->service_id,
            'type' => $this->type,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'amount' => $this->amount,
            'charged_at' => $this->charged_at,
            'charged_by' => $this->charged_by,
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                ];
            }),
            'charged_by_user' => $this->whenLoaded('chargedBy', function () {
                return [
                    'id' => $this->chargedBy->id,
                    'name' => $this->chargedBy->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
