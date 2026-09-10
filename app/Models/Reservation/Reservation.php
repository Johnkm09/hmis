<?php

namespace App\Models\Reservation;

use App\Models\Guest\Guest;
use App\Models\Room\Room;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'guest_id',
        'room_id',
        'check_in',
        'check_out',
        'number_of_guests',
        'nightly_rate',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'number_of_guests' => 'integer',
        'nightly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
