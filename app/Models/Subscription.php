<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model {
    protected $fillable = [
        'is_active',
        'plan_id',
        'time_delivery_id',
        'sender_name',
        'receiving_name',
        'address',
        'frequency',
        'comment',
        'using_promo',
        'sender_phone',
        'receiving_phone',
    ];

    protected static function boot() {
        parent::boot();

        static::saving(function ($subscription) {
            if (empty($subscription->receiving_name)) {
                $subscription->receiving_name = $subscription->sender_name;
            }

            if (empty($subscription->receiving_phone)) {
                $subscription->receiving_phone = $subscription->sender_phone;
            }
        });
    }
}
