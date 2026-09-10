<?php

namespace App\Http\Resources\Api\V1\Reservation;

use App\Http\Resources\Api\V1\Guest\GuestResource;
use App\Http\Resources\Api\V1\Room\RoomResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
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
            'guest_id' => $this->guest_id,
            'room_id' => $this->room_id,
            'guest' => new GuestResource($this->whenLoaded('guest')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'number_of_guests' => $this->number_of_guests,
            'nightly_rate' => $this->nightly_rate,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
