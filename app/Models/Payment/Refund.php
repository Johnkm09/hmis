<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'amount',
        'reason',
        'status',
        'refunded_at',
        'refunded_by',
        'transaction_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_at' => 'datetime',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
