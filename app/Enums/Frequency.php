<?php

namespace App\Enums;

enum Frequency: string {
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array {
        return array_map(fn($case) => [
            'key' => $case->name,
            'label' => $case->value
        ], self::cases());
    }
}
