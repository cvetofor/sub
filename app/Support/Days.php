<?php

namespace App\Support;

/** Дни недели: 1 = понедельник … 7 = воскресенье. */
final class Days {
    public const SHORT = [1 => 'пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'];
    public const NOMINATIVE = [1 => 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
    public const ACCUSATIVE = [1 => 'понедельник', 'вторник', 'среду', 'четверг', 'пятницу', 'субботу', 'воскресенье'];
    public const EACH = [1 => 'по понедельникам', 'по вторникам', 'по средам', 'по четвергам', 'по пятницам', 'по субботам', 'по воскресеньям'];

    /** «в субботу»: винительный падеж для фразы «привезём в …». */
    public static function name(?int $day): string {
        return self::ACCUSATIVE[$day] ?? 'субботу';
    }

    /** «во вторник», «в среду»: с предлогом. */
    public static function inDay(?int $day): string {
        $name = self::name($day);

        return ($name === 'вторник' ? 'во ' : 'в ') . $name;
    }

    public static function short(?int $day): string {
        return self::SHORT[$day] ?? 'сб';
    }
}
