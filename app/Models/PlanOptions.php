<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanOptions extends Model {
    protected $fillable = [
        'name',
        'price',
        'description',
        'subscription_id',
        'is_active',
        'type'
    ];
}
