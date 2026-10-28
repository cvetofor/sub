<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Plan extends Model {
    public const SCENARIO_HOME = 'home';
    public const SCENARIO_GIFT = 'gift';
    public const SCENARIO_ANY = 'any';

    public const SCENARIOS = [
        self::SCENARIO_HOME => 'Себе домой',
        self::SCENARIO_GIFT => 'В подарок',
        self::SCENARIO_ANY => 'Домой или в подарок',
    ];

    protected $fillable = [
        'name',
        'name_gift',
        'price',
        'description',
        'is_active',
        'is_custom',
        'city_id',
        'scenario',
        'tag',
        'image',
        'sort',
    ];

    /** Название тарифа для сценария: в подарок у тарифа может быть своё имя. */
    public function nameFor(?string $scenario): string {
        return $scenario === self::SCENARIO_GIFT && $this->name_gift ? $this->name_gift : $this->name;
    }

    public function city() {
        return $this->belongsTo(City::class);
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

    /** Готовые планы для витрины: не пользовательские, активные, в заданном порядке. */
    public function scopeShowcase($query) {
        return $query->where('is_custom', false)->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    /** Адрес фото карточки: загруженное в админке или запасное по сценарию. */
    public function imageUrl(): string {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }

        return asset(match ($this->scenario) {
            self::SCENARIO_GIFT => 'images/site/plan-gift.jpg',
            self::SCENARIO_HOME => 'images/site/plan-home.jpg',
            default => 'images/site/plan-author.jpg',
        });
    }
}
