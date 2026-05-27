<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Room;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function rooms(){
        return $this->hasMany(Room::class);
    }
}
