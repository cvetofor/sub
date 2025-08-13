<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model {
    protected $fillable = [
        'name',
        'status'
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
