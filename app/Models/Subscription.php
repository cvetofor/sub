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
        'next_date_payment'
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

    public function plan() {
        return $this->belongsTo(Plan::class);
    }

    public function totalAmount(): float {
        $plan = $this->plan;

        if (!$plan) {
            return 0;
        }

        $frequencyCount = \App\Enums\Frequency::getFrequencyElem($this->frequency)['count'];
        $sumPlanOptions = $plan->options->where('is_every_delivery', true)->sum('price');
        $sumOncePlanOptions = $plan->options->where('is_every_delivery', false)->sum('price');
        $totalPrice = ($plan->price + $sumPlanOptions) * $frequencyCount + $sumOncePlanOptions;

        return $totalPrice;
    }

    public function payments() {
        return $this->belongsToMany(Payment::class, 'subscription_payments', 'subscription_id', 'payment_id')
            ->withTimestamps();
    }
}
