<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\AmoCrm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Передача заявки в amoCRM после ответа клиенту, чтобы оформление не ждало внешний сервис.
 * Запускается через dispatch(...)->afterResponse(): отдельный воркер очереди не нужен.
 */
class SyncAmoLead implements ShouldQueue {
    use Queueable;

    public const CREATED = 'created';
    public const PAID = 'paid';

    public function __construct(public int $subscriptionId, public string $event, public float $amount = 0) {
    }

    public function handle(): void {
        $sub = Subscription::with('plan.options')->find($this->subscriptionId);
        if (!$sub) {
            return;
        }
        if ($this->event === self::PAID) {
            AmoCrm::leadPaid($sub, $this->amount ?: $sub->firstPaymentAmount());
        } else {
            AmoCrm::leadCreated($sub);
        }
    }
}
