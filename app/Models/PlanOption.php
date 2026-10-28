<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PlanOption extends Model {
    protected $fillable = [
        'name',
        'price',
        'description',
        'subscription_id',
        'is_every_delivery',
        'is_active',
        'type',
        'image',
        'sort',
    ];

    public const TYPE_ADDITION = 'addition';

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    public function plans() {
        return $this->belongsToMany(Plan::class, 'plan_plan_options');
    }

    /** Дополнения к букету для витрины и квиза: активные, в заданном порядке. */
    public function scopeAdditions($query) {
        return $query->where('is_active', true)->where('type', self::TYPE_ADDITION)->orderBy('sort')->orderBy('price');
    }

    /** Фото дополнения: загруженное в админке или запасное. */
    public function imageUrl(): string {
        return $this->image ? Storage::disk('public')->url($this->image) : asset('images/site/g-home-table.jpg');
    }

    public function sumOneDeliveryOpt() {
    }
}
