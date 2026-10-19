<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Subscription;
use Carbon\Carbon;

/**
 * Расписание доставок по подписке.
 * Первый месяц: столько доставок, сколько обещает акция (4 / 2 / 2).
 * Каждый следующий оплаченный месяц: обычное количество (4 / 2 / 1).
 * Доставки ставятся на выбранный день недели, первая не раньше чем через два дня.
 */
class DeliverySchedule {
    public const MIN_LEAD_DAYS = 2;

    /** Интервал между доставками в днях: раз в месяц в первый месяц это тоже две доставки через 2 недели. */
    private const INTERVAL = [Pricing::WEEKLY => 7, Pricing::BIWEEKLY => 14, Pricing::MONTHLY => 28];

    /** Ближайшая дата нужного дня недели, не раньше чем через MIN_LEAD_DAYS. */
    public static function firstDate(int $deliveryDay, ?Carbon $from = null): Carbon {
        $date = ($from ?? Carbon::today())->copy()->startOfDay()->addDays(self::MIN_LEAD_DAYS);
        while ($date->dayOfWeekIso !== $deliveryDay) {
            $date->addDay();
        }

        return $date;
    }

    /** Доставки первого оплаченного месяца. Повторный вызов ничего не дублирует. */
    public static function planFirstMonth(Subscription $sub): void {
        if (!Pricing::isValid($sub->frequency) || $sub->deliveries()->exists()) {
            return;
        }
        $count = Pricing::FIRST_MONTH_DELIVERIES[$sub->frequency];
        $interval = $sub->frequency === Pricing::MONTHLY ? 14 : self::INTERVAL[$sub->frequency];
        self::append($sub, $count, $interval, self::firstDate((int) ($sub->delivery_day ?: 6)));
    }

    /** Доставки следующего оплаченного месяца, продолжая цепочку. */
    public static function planNextMonth(Subscription $sub): void {
        if (!Pricing::isValid($sub->frequency)) {
            return;
        }
        $count = Pricing::PER_MONTH[$sub->frequency];
        $interval = self::INTERVAL[$sub->frequency];
        $last = $sub->deliveries()->whereIn('status', [Delivery::PLANNED, Delivery::DELIVERED, Delivery::SKIPPED])->max('planned_date');
        $start = $last ? Carbon::parse($last)->addDays($interval) : self::firstDate((int) ($sub->delivery_day ?: 6));
        if ($start->lt(self::firstDate((int) ($sub->delivery_day ?: 6)))) {
            $start = self::firstDate((int) ($sub->delivery_day ?: 6));
        }
        self::append($sub, $count, $interval, $start);
    }

    /** Клиент сменил день недели или окно: переносим будущие доставки на новый день той же недели. */
    public static function moveToWeekday(Subscription $sub): void {
        $day = (int) ($sub->delivery_day ?: 6);
        $minDate = Carbon::today()->addDays(self::MIN_LEAD_DAYS);
        foreach ($sub->deliveries()->planned()->orderBy('planned_date')->get() as $delivery) {
            $date = $delivery->planned_date->copy()->startOfWeek(Carbon::MONDAY)->addDays($day - 1);
            if ($date->lt($minDate)) {
                $date->addWeek();
            }
            $delivery->update(['planned_date' => $date, 'time_delivery_id' => $sub->time_delivery_id]);
        }
    }

    /** После паузы: просроченные доставки переносим вперёд с тем же шагом, не теряя оплаченных букетов. */
    public static function shiftOverdue(Subscription $sub): void {
        $planned = $sub->deliveries()->planned()->orderBy('planned_date')->get();
        $minDate = self::firstDate((int) ($sub->delivery_day ?: 6));
        $interval = $sub->frequency === Pricing::MONTHLY && $planned->count() > 1 ? 14 : (self::INTERVAL[$sub->frequency] ?? 7);
        $date = null;
        foreach ($planned as $delivery) {
            if ($date === null) {
                $date = $delivery->planned_date->lt($minDate) ? $minDate->copy() : $delivery->planned_date->copy();
            } else {
                $date = $date->copy()->addDays($interval);
            }
            if (!$delivery->planned_date->equalTo($date)) {
                $delivery->update(['planned_date' => $date]);
            }
        }
    }

    /** Клиент пропускает доставку: букет не сгорает, а добавляется в конец цепочки. */
    public static function skip(Delivery $delivery): void {
        $sub = $delivery->subscription;
        $delivery->update(['status' => Delivery::SKIPPED]);
        $interval = self::INTERVAL[$sub->frequency] ?? 7;
        $last = $sub->deliveries()->whereIn('status', [Delivery::PLANNED, Delivery::DELIVERED, Delivery::SKIPPED])->max('planned_date');
        $next = Carbon::parse($last)->addDays($interval);
        self::append($sub, 1, $interval, $next);
    }

    /** Подписка остановлена навсегда или удалена: будущие доставки отменяются. */
    public static function cancelPlanned(Subscription $sub): void {
        $sub->deliveries()->planned()->update(['status' => Delivery::CANCELLED]);
    }

    private static function append(Subscription $sub, int $count, int $interval, Carbon $start): void {
        $date = $start->copy();
        for ($i = 0; $i < $count; $i++) {
            Delivery::create([
                'subscription_id' => $sub->id,
                'planned_date' => $date->toDateString(),
                'time_delivery_id' => $sub->time_delivery_id,
                'status' => Delivery::PLANNED,
            ]);
            $date->addDays($interval);
        }
    }
}
