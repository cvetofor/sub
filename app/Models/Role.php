<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {
    const ADMIN = 1;
    const MANAGER = 2;
    const CLIENT = 3;

    protected $fillable = [
        'name',
        'code'
    ];
}
