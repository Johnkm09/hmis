<?php

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio_id' => $this->folio_id,
            'amount' => $this->amount,
            'method' => $this->method,
            'provider' => $this->provider,
            'transaction_reference' => $this->transaction_reference,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'received_by' => $this->received_by,

            'received_by_user' => $this->whenLoaded('receivedBy', function () {
                return [
                    'id' => $this->receivedBy->id,
                    'name' => $this->receivedBy->name,
                ];
            }),

            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
