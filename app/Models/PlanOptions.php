<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanOptions extends Model {
    protected $fillable = [
        'name',
        'price',
        'description',
        'subscription_id',
        'is_every_delivery',
        'is_active',
        'type'
    ];

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    public function plans() {
        return $this->belongsToMany(Plan::class, 'plan_plan_option');
    }
}
