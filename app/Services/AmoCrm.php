<?php

namespace App\Services;

use App\Models\Subscription;
use App\Support\Days;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сделки в amoCRM: заявка с сайта создаёт сделку с контактом, оплата переводит её на следующий этап.
 * Нужны AMOCRM_SUBDOMAIN и AMOCRM_TOKEN (долгосрочный токен интеграции) в окружении
 * и включённый флаг в настройках админки. Без них вызовы только пишутся в журнал.
 */
class AmoCrm {
    public static function configured(): bool {
        return Settings::amoEnabled();
    }

    /** Заявка оформлена (ещё не оплачена): сделка и контакт в amoCRM. Возвращает id сделки. */
    public static function leadCreated(Subscription $sub): ?int {
        $sub->loadMissing('plan.options');
        if ($sub->amo_lead_id) {
            return (int) $sub->amo_lead_id;
        }
        $payload = [[
            'name' => 'Подписка №' . $sub->id . ': ' . $sub->planName() . ', ' . mb_strtolower(Pricing::LABELS[$sub->frequency] ?? $sub->frequency),
            'price' => (int) round($sub->firstPaymentAmount()),
            '_embedded' => [
                'tags' => array_values(array_filter([
                    ['name' => 'сайт'],
                    $sub->source ? ['name' => $sub->source === Subscription::SOURCE_QUIZ ? 'квиз' : 'мастер'] : null,
                    ['name' => ($sub->preferences['for'] ?? null) === 'gift' ? 'в подарок' : 'себе'],
                ])),
                'contacts' => [[
                    'first_name' => $sub->sender_name,
                    'custom_fields_values' => [[
                        'field_code' => 'PHONE',
                        'values' => [['value' => $sub->sender_phone, 'enum_code' => 'WORK']],
                    ]],
                ]],
            ],
        ]];
        self::addOptional($payload[0], 'pipeline_id', Settings::get('amocrm_pipeline_id'));
        self::addOptional($payload[0], 'status_id', Settings::get('amocrm_status_new'));
        self::addOptional($payload[0], 'responsible_user_id', Settings::get('amocrm_responsible_user_id'));

        if (!self::configured()) {
            Log::channel('shop')->info('amoCRM не настроена, сделка не создана.', ['subscription_id' => $sub->id]);

            return null;
        }

        try {
            $response = self::client()->post('/api/v4/leads/complex', $payload)->throw()->json();
            $leadId = (int) ($response[0]['id'] ?? 0);
            if (!$leadId) {
                throw new \RuntimeException('В ответе нет id сделки');
            }
            $sub->forceFill(['amo_lead_id' => $leadId])->save();
            self::note($leadId, self::summary($sub));
            Log::channel('shop')->info('Сделка создана в amoCRM.', ['subscription_id' => $sub->id, 'lead_id' => $leadId]);

            return $leadId;
        } catch (\Throwable $e) {
            Log::channel('shop')->error('Не удалось создать сделку в amoCRM.', ['subscription_id' => $sub->id, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /** Первая оплата прошла: сделка переходит на этап «оплачено», сумма и примечание обновляются. */
    public static function leadPaid(Subscription $sub, float $amount): void {
        if (!self::configured()) {
            Log::channel('shop')->info('amoCRM не настроена, оплата не передана.', ['subscription_id' => $sub->id]);

            return;
        }
        $leadId = $sub->amo_lead_id ?: self::leadCreated($sub);
        if (!$leadId) {
            return;
        }
        $patch = ['price' => (int) round($amount)];
        self::addOptional($patch, 'status_id', Settings::get('amocrm_status_paid'));
        try {
            self::client()->patch('/api/v4/leads/' . $leadId, $patch)->throw();
            self::note($leadId, 'Оплачено ' . number_format($amount, 0, '', ' ') . ' ₽ через ЮKassa. ' . self::firstDelivery($sub));
            Log::channel('shop')->info('Оплата передана в amoCRM.', ['subscription_id' => $sub->id, 'lead_id' => $leadId]);
        } catch (\Throwable $e) {
            Log::channel('shop')->error('Не удалось обновить сделку в amoCRM.', ['subscription_id' => $sub->id, 'lead_id' => $leadId, 'message' => $e->getMessage()]);
        }
    }

    private static function note(int $leadId, string $text): void {
        self::client()->post('/api/v4/leads/' . $leadId . '/notes', [['note_type' => 'common', 'params' => ['text' => $text]]])->throw();
    }

    /** Текст примечания к сделке: всё, что нужно оператору, чтобы позвонить. */
    public static function summary(Subscription $sub): string {
        $sub->loadMissing('plan.options', 'timeDelivery');
        $p = $sub->preferences ?? [];
        $lines = [
            'Заявка с сайта №' . $sub->id . ' (' . ($sub->source === Subscription::SOURCE_QUIZ ? 'квиз' : 'мастер') . ')',
            'Тариф: ' . $sub->planName() . ', ' . number_format((float) $sub->plan->price, 0, '', ' ') . ' ₽ за букет, ' . mb_strtolower(Pricing::LABELS[$sub->frequency] ?? $sub->frequency),
            'Первый платёж: ' . number_format($sub->firstPaymentAmount(), 0, '', ' ') . ' ₽, дальше ' . number_format($sub->monthlyAmount(), 0, '', ' ') . ' ₽ в месяц',
            'Заказчик: ' . $sub->sender_name . ' ' . $sub->sender_phone,
        ];
        if (($p['for'] ?? null) === 'gift') {
            $lines[] = 'Получатель: ' . $sub->receiving_name . ' ' . $sub->receiving_phone
                . (!empty($p['hide_sender']) ? ', не говорить, от кого' : '')
                . (!empty($p['agree_with_recipient']) ? ', день и время согласовать с получателем' : '');
        }
        $lines[] = 'Адрес: ' . $sub->address;
        $lines[] = 'Доставка: ' . (Days::NOMINATIVE[$sub->delivery_day] ?? '') . ($sub->timeDelivery ? ', ' . substr($sub->timeDelivery->from, 0, 5) . '–' . substr($sub->timeDelivery->to, 0, 5) : '');
        if ($sub->plan->options->isNotEmpty()) {
            $lines[] = 'Дополнения: ' . $sub->plan->options->pluck('name')->join(', ');
        }
        if (!empty($p['card_text'])) {
            $lines[] = 'Открытка: ' . $p['card_text'];
        }
        if (!empty($p['wishes'])) {
            $lines[] = 'Пожелания флористу: ' . $p['wishes'];
        }
        if ($sub->comment) {
            $lines[] = 'Комментарий курьеру: ' . $sub->comment;
        }
        $lines[] = 'Админка: ' . url('/hub/subscriptions/' . $sub->id . '/edit');

        return implode("\n", $lines);
    }

    private static function firstDelivery(Subscription $sub): string {
        $first = $sub->deliveries()->planned()->orderBy('planned_date')->first();

        return $first ? 'Первая доставка ' . $first->planned_date->format('d.m.Y') . ($first->windowLabel() ? ' ' . $first->windowLabel() : '') . '.' : '';
    }

    private static function addOptional(array &$target, string $key, mixed $value): void {
        if ($value !== null && $value !== '' && (int) $value > 0) {
            $target[$key] = (int) $value;
        }
    }

    private static function client(): PendingRequest {
        return Http::baseUrl('https://' . config('services.amocrm.subdomain') . '.amocrm.ru')
            ->withToken((string) config('services.amocrm.token'))
            ->acceptJson()
            ->timeout(15);
    }
}
