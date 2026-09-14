<?php

namespace App\Models\Folio;

use App\Models\Reservation\Reservation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Folio\FolioCharge;

class Folio extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function charges()
    {
        return $this->hasMany(FolioCharge::class);
    }
}
