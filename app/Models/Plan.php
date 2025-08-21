<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model {
    protected $fillable = [
        'name',
        'price',
        'description',
        'is_active',
        'is_custom',
        'city_id'

    ];

    public function subscription() {
        return $this->belongsTo(Subscription::class);
    }

    public function options() {
        return $this->belongsToMany(
            PlanOption::class,
            'plan_plan_options',
            'plan_id',
            'plan_option_id'
        );
    }

    public function scopeActive($query) {
        return $query->where('is_active', true)->orderBy('id');
    }
}
