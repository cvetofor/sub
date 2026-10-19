<?php

namespace Tests\Unit;

use App\Services\DeliverySchedule;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DeliveryScheduleTest extends TestCase {
    public function test_first_date_is_next_weekday_not_earlier_than_two_days(): void {
        // Понедельник 19.10.2026: суббота через 5 дней
        $this->assertSame('2026-10-24', DeliverySchedule::firstDate(6, Carbon::parse('2026-10-19'))->toDateString());
        // Пятница 23.10: суббота завтра слишком рано, берём следующую
        $this->assertSame('2026-10-31', DeliverySchedule::firstDate(6, Carbon::parse('2026-10-23'))->toDateString());
        // Четверг 22.10: суббота ровно через два дня подходит
        $this->assertSame('2026-10-24', DeliverySchedule::firstDate(6, Carbon::parse('2026-10-22'))->toDateString());
        // Среда → среда через неделю
        $this->assertSame('2026-10-28', DeliverySchedule::firstDate(3, Carbon::parse('2026-10-21'))->toDateString());
    }
}
