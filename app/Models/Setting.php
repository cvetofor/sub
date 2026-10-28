<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Настройка сайта из админки: ключ и значение в JSON. Читать через App\Services\Settings. */
class Setting extends Model {
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];
}
