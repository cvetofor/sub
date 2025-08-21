<?php

namespace App\Models;

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

    public function plans() {
        return $this->hasMany(Plan::class, 'subscription_id');
    }

    protected static function booted() {
        static::deleting(function ($subscription) {
            $subscription->plans()->update(['is_active' => false]);
        });
    }
}
