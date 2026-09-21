<?php

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
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
            'receipt_number' => $this->receipt_number,

            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment->id,
                    'amount' => $this->payment->amount,
                    'method' => $this->payment->method,
                    'provider' => $this->payment->provider,
                    'transaction_reference' => $this->payment->transaction_reference,
                    'status' => $this->payment->status,
                    'paid_at' => $this->payment->paid_at,
                ];
            }),

            'issued_at' => $this->issued_at,

            'issued_by' => $this->whenLoaded('issuedBy', function () {
                return [
                    'id' => $this->issuedBy->id,
                    'name' => $this->issuedBy->name,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
