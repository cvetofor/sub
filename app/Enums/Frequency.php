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

    public static function getFrequencyElem(?string $frequencyName = null): array {
        return match ($frequencyName) {
            self::WEEKLY->value =>  ['count' => 4, 'code' => 'weekly', 'translate' => 'Еженедельно'],
            self::BIWEEKLY->value => ['count' => 2, 'code' => 'biweekly', 'translate' => 'Каждые 2 недели'],
            self::MONTHLY->value => ['count' => 1, 'code' => 'monthly', 'translate' => 'Ежемесячно'],
            default => [
                ['count' => 4, 'code' => 'weekly', 'translate' => 'Еженедельно'],
                ['count' => 2, 'code' => 'biweekly', 'translate' => 'Каждые 2 недели'],
                ['count' => 1, 'code' => 'monthly', 'translate' => 'Ежемесячно'],
            ],
        };
    }
}
