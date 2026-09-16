<?php

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'refunded_at' => $this->refunded_at,
            'refunded_by' => $this->refunded_by,

            'refunded_by_user' => $this->whenLoaded('refundedBy', function () {
                return [
                    'id' => $this->refundedBy->id,
                    'name' => $this->refundedBy->name,
                ];
            }),

            'transaction_reference' => $this->transaction_reference,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
