<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model {
    public const PLANNED = 'planned';
    public const DELIVERED = 'delivered';
    public const SKIPPED = 'skipped';
    public const CANCELLED = 'cancelled';

    public const STATUSES = [
        self::PLANNED => 'Запланирована',
        self::DELIVERED => 'Доставлена',
        self::SKIPPED => 'Пропущена клиентом',
        self::CANCELLED => 'Отменена',
    ];

    protected $fillable = ['subscription_id', 'planned_date', 'time_delivery_id', 'status', 'reminded_at', 'delivered_at', 'note'];

    protected $casts = [
        'planned_date' => 'date',
        'reminded_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function subscription() {
        return $this->belongsTo(Subscription::class);
    }

    public function timeDelivery() {
        return $this->belongsTo(TimeDelivery::class);
    }

    public function scopePlanned($query) {
        return $query->where('status', self::PLANNED);
    }

    public function scopeOnDate($query, $date) {
        return $query->whereDate('planned_date', $date);
    }

    public function windowLabel(): string {
        $w = $this->timeDelivery;

        return $w ? substr($w->from, 0, 5) . '–' . substr($w->to, 0, 5) : '';
    }
}
