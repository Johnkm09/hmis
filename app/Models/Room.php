<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RoomType;
use App\Enums\RoomStatus;

class Room extends Model
{
    protected $fillable = [ 
        'room_type_id',
        'room_number',
        'price_per_night',
        'capacity',
        'status',
        'description'
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'status' => RoomStatus::class,
    ];

    public function roomType(){
        return $this->belongsTo(RoomType::class);
    }

    public function scopeAvailable($query){
        return $query->where('status',RoomStatus::AVAILABLE);
    }
}
