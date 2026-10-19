<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\DeliverySchedule;
use Illuminate\Console\Command;

/** Один раз после выката: расписание доставок для подписок, оплаченных до появления этой функции. */
class BackfillDeliveries extends Command {
    protected $signature = 'deliveries:backfill {--dry : Только показать, ничего не создавать}';

    protected $description = 'Создать расписание доставок для оплаченных подписок без расписания';

    public function handle(): int {
        $subs = Subscription::active()
            ->whereDoesntHave('deliveries')
            ->whereHas('payments', fn ($q) => $q->where('payment_status_id', Payment::PAYED))
            ->get();

        foreach ($subs as $sub) {
            if (!$sub->delivery_day) {
                $sub->delivery_day = 6; // по умолчанию суббота, менеджер уточнит у клиента
                $sub->save();
            }
            $this->line("Подписка #{$sub->id} ({$sub->frequency}, день {$sub->delivery_day})");
            if (!$this->option('dry')) {
                DeliverySchedule::planNextMonth($sub);
            }
        }
        $this->info(($this->option('dry') ? 'Будет обработано: ' : 'Обработано: ') . $subs->count());

        return self::SUCCESS;
    }
}
