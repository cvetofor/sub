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

    public static function getFrequencyElem(string $frequencyName): array {
        return match ($frequencyName) {
            self::WEEKLY->value => ['count' => 4, 'translate' => 'Еженедельно'],
            self::BIWEEKLY->value => ['count' => 2, 'translate' => 'Каждые 2 недели'],
            self::MONTHLY->value => ['count' => 1, 'translate' => 'Ежемесячно'],
            default => 0,
        };
    }
}
