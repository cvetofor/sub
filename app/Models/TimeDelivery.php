<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeDelivery extends Model {
    protected $fillable = [
        'from',
        'to'
    ];
}
