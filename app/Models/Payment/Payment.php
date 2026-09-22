<?php

namespace App\Models\Payment;

use App\Models\Folio\Folio;
use App\Models\User;
use App\Models\Payment\Receipt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio_id',
        'amount',
        'method',
        'provider',
        'transaction_reference',
        'mpesa_phone',
        'mpesa_merchant_request_id',
        'mpesa_checkout_request_id',
        'status',
        'paid_at',
        'received_by',
        'notes',

    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function folio()
    {
        return $this->belongsTo(Folio::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}
