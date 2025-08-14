<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class City extends Model {
    protected $fillable = [
        'name',
        'code',
        'is_active'
    ];

    protected static function boot() {
        parent::boot();

        static::saving(function ($city) {
            $city->code = Str::slug($city->name, '_');
        });
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }
}
