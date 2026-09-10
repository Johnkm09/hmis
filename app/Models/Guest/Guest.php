<?php

namespace App\Models\Guest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Reservation\Reservation;

class Guest extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'id_number',
        'phone_number',
        'email',
        'country',
        'city',
        'address'
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
