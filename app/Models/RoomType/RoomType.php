<?php

namespace App\Models\RoomType;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Room\Room;

class RoomType extends Model
{
    use SoftDeletes, HasFactory;
    
    protected $table = 'room_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query){
        return $query->where('is_active',true);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
