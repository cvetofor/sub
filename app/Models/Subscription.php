<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model {
    protected $fillable = [
        'is_active',
        'user_id',
        'plan_id',
        'time_delivery_id',
        'sender_name',
        'receiving_name',
        'address',
        'budget_for_delivery',
        'comment',
        'using_promo',
        'is_custom',
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
