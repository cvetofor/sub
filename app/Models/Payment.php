<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    const IN_PROGRESS = 1;
    const PAYED = 2;

    protected $fillable = [
        'payment_status_id',
        'amount',
        'payment_gateway_transaction',
        'recurrent_token'
    ];

    public function status() {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function statusName(): string {
        return $this->status?->name ?? 'Неизвестный статус';
    }

    public static function totalPaid(): float {
        return self::where('payment_status_id', self::PAYED)->sum('amount');
    }
}
