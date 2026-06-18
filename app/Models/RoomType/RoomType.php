<?php

namespace App\Models\RoomType;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    /*protected static function booted(){
        static::creating(function ($model){
            $model->slug = Str::slug($model->name);
        });
    }*/

    public function scopeActive($query){
        return $query->where('is_active',true);
    }
}
