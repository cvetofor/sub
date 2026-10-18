<?php

namespace App\Models;

use App\Services\Pricing;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model {
    protected $fillable = [
        'is_active',
        'plan_id',
        'user_id',
        'time_delivery_id',
        'delivery_day',
        'sender_name',
        'receiving_name',
        'address',
        'frequency',
        'comment',
        'preferences',
        'using_promo',
        'sender_phone',
        'receiving_phone',
        'next_date_payment',
        'ab_variant',
        'ab_visitor',
    ];

    protected $casts = [
        'preferences' => 'array',
        'is_active' => 'boolean',
        'using_promo' => 'boolean',
        'next_date_payment' => 'datetime',
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

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    public function plan() {
        return $this->belongsTo(Plan::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function timeDelivery() {
        return $this->belongsTo(TimeDelivery::class);
    }

    public function payments() {
        return $this->belongsToMany(Payment::class, 'subscription_payments', 'subscription_id', 'payment_id')
            ->withTimestamps();
    }

    /** Принадлежит ли подписка пользователю: по связи или по номеру телефона (старые подписки без user_id). */
    public function belongsToUser(User $user): bool {
        if ($this->user_id && (int) $this->user_id === (int) $user->id) {
            return true;
        }
        $mine = preg_replace('/\D+/', '', (string) ($user->phone_e164 ?: $user->phone));
        $sender = preg_replace('/\D+/', '', (string) $this->sender_phone);

        return $mine !== '' && $mine === $sender;
    }

    /** Опции, которые добавляются к каждой доставке, в рублях за доставку. */
    public function perDeliveryExtras(): float {
        return (float) ($this->plan?->options->where('is_every_delivery', true)->sum('price') ?? 0);
    }

    /** Разовые опции (ваза и подобное), в рублях, только в первый платёж. */
    public function oneTimeExtras(): float {
        return (float) ($this->plan?->options->where('is_every_delivery', false)->sum('price') ?? 0);
    }

    /** Первый платёж: с подарочным букетом и разовыми опциями. */
    public function firstPaymentAmount(): float {
        if (!$this->plan || !Pricing::isValid($this->frequency)) {
            return 0;
        }

        return Pricing::firstMonth((float) $this->plan->price, $this->frequency, $this->oneTimeExtras(), $this->perDeliveryExtras());
    }

    /** Повторный ежемесячный платёж. */
    public function monthlyAmount(): float {
        if (!$this->plan || !Pricing::isValid($this->frequency)) {
            return 0;
        }

        return Pricing::monthly((float) $this->plan->price, $this->frequency, $this->perDeliveryExtras());
    }

    /** Совместимость со старым кодом: сумма обычного месяца. */
    public function totalAmount(): float {
        return $this->monthlyAmount();
    }
}
