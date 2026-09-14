<?php

namespace App\Models\Folio;

use App\Models\Service\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FolioCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio_id',
        'service_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'charged_at',
        'charged_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'charged_at' => 'datetime',
        ];
    }

    public function folio()
    {
        return $this->belongsTo(Folio::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function chargedBy()
    {
        return $this->belongsTo(User::class, 'charged_by');
    }
}
