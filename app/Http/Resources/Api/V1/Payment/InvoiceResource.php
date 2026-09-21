<?php

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,

            'folio' => $this->whenLoaded('folio', function () {
                return [
                    'id' => $this->folio->id,
                    'reservation_id' => $this->folio->reservation_id,
                    'status' => $this->folio->status,
                ];
            }),

            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,

            'status' => $this->status,
            'issued_at' => $this->issued_at,

            'issued_by' => $this->whenLoaded('issuedBy', function () {
                return [
                    'id' => $this->issuedBy->id,
                    'name' => $this->issuedBy->name,
                ];
            }),

            'notes' => $this->notes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
