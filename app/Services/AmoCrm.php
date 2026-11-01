<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Delivery;
use App\Support\Days;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сделки в amoCRM: заявка с сайта создаёт сделку с контактом, оплата переводит её на следующий этап.
 * Нужны AMOCRM_SUBDOMAIN и AMOCRM_TOKEN (долгосрочный токен интеграции) в окружении
 * и включённый флаг в настройках админки. Без них вызовы только пишутся в журнал.
 */
class AmoCrm {
    /** Доступ к API есть, даже если автоматическая передача заявок пока выключена. */
    public static function credentialsPresent(): bool {
        return (bool) config('services.amocrm.subdomain') && (bool) config('services.amocrm.token');
    }

    public static function configured(): bool {
        return Settings::amoEnabled();
    }

    /** Список воронок для понятного выбора в админке: номер хранит сайт, человек видит название. */
    public static function pipelineOptions(): array {
        if (!self::credentialsPresent()) {
            return [];
        }

        try {
            return Cache::remember('amocrm:pipelines', now()->addMinutes(5), function () {
                $pipelines = self::client()->get('/api/v4/leads/pipelines')->throw()->json('_embedded.pipelines', []);

                return collect($pipelines)
                    ->reject(fn (array $pipeline) => (bool) ($pipeline['is_archive'] ?? false))
                    ->mapWithKeys(fn (array $pipeline) => [(string) $pipeline['id'] => $pipeline['name']])
                    ->all();
            });
        } catch (\Throwable $e) {
            Log::channel('shop')->warning('Не удалось получить список воронок amoCRM.', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /** Этапы выбранной воронки для выбора по названию, а не по техническому номеру. */
    public static function stageOptions(?int $pipelineId): array {
        if (!$pipelineId || !self::credentialsPresent()) {
            return [];
        }

        try {
            return Cache::remember("amocrm:pipeline:{$pipelineId}:stages", now()->addMinutes(5), function () use ($pipelineId) {
                $statuses = self::client()->get("/api/v4/leads/pipelines/{$pipelineId}/statuses")->throw()->json('_embedded.statuses', []);

                return collect($statuses)
                    ->mapWithKeys(fn (array $status) => [(string) $status['id'] => $status['name']])
                    ->all();
            });
        } catch (\Throwable $e) {
            Log::channel('shop')->warning('Не удалось получить этапы воронки amoCRM.', ['pipeline_id' => $pipelineId, 'message' => $e->getMessage()]);

            return [];
        }
    }

    /** Сотрудники amoCRM для назначения ответственного по имени. */
    public static function userOptions(): array {
        if (!self::credentialsPresent()) {
            return [];
        }

        try {
            return Cache::remember('amocrm:users', now()->addMinutes(5), function () {
                $users = self::client()->get('/api/v4/users')->throw()->json('_embedded.users', []);

                return collect($users)
                    ->mapWithKeys(fn (array $user) => [(string) $user['id'] => $user['name']])
                    ->all();
            });
        } catch (\Throwable $e) {
            Log::channel('shop')->warning('Не удалось получить список сотрудников amoCRM.', ['message' => $e->getMessage()]);

            return [];
        }
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
            'custom_fields_values' => self::leadCustomFieldValues($sub, false),
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
        $patch['custom_fields_values'] = self::leadCustomFieldValues($sub, true, $amount);
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
        $lines[] = 'Карточка заявки (только просмотр, вход не нужен): ' . route('amo.subscription.show', ['token' => $sub->amoViewToken()]);

        return implode("\n", $lines);
    }

    private static function firstDelivery(Subscription $sub): string {
        $first = $sub->deliveries()->planned()->orderBy('planned_date')->first();

        return $first ? 'Первая доставка ' . $first->planned_date->format('d.m.Y') . ($first->windowLabel() ? ' ' . $first->windowLabel() : '') . '.' : '';
    }

    /**
     * Поля карточки сделки, которые сайт действительно знает. Поля находятся
     * по названиям в amoCRM, поэтому смена их технических номеров не сломает
     * интеграцию. Неизвестные сайту данные (например, гелий) намеренно не
     * подменяются нулями.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function leadCustomFieldValues(Subscription $sub, bool $paid, ?float $paidAmount = null): array {
        if (!self::credentialsPresent()) {
            return [];
        }

        $sub->loadMissing('plan.options', 'timeDelivery', 'deliveries.timeDelivery');
        $preferences = $sub->preferences ?? [];
        $firstDelivery = $sub->deliveries
            ->where('status', Delivery::PLANNED)
            ->sortBy('planned_date')
            ->first();
        $deliveryDate = $firstDelivery?->planned_date
            ?? DeliverySchedule::firstDate((int) ($sub->delivery_day ?: 6));
        $window = $firstDelivery?->timeDelivery ?: $sub->timeDelivery;
        $windowLabel = $window ? substr($window->from, 0, 5) . '–' . substr($window->to, 0, 5) : null;

        $composition = array_filter([
            'Подписка «' . $sub->planName() . '»',
            number_format((float) ($sub->plan?->price ?? 0), 0, '', ' ') . ' ₽ за букет',
            mb_strtolower(Pricing::LABELS[$sub->frequency] ?? $sub->frequency),
            $sub->plan?->options->isNotEmpty() ? 'Дополнения: ' . $sub->plan->options->pluck('name')->join(', ') : null,
        ]);
        $comment = array_filter([
            !empty($preferences['card_text']) ? 'Открытка: ' . $preferences['card_text'] : null,
            !empty($preferences['wishes']) ? 'Пожелания флористу: ' . $preferences['wishes'] : null,
            $sub->comment ? 'Комментарий курьеру: ' . $sub->comment : null,
        ]);
        $prepayment = $paid ? ($paidAmount ?? $sub->firstPaymentAmount()) : null;

        return array_values(array_filter([
            self::customFieldValue('ФИО Получателя', $sub->receiving_name),
            self::customFieldValue('Телефон Получателя', self::digits($sub->receiving_phone)),
            self::customFieldValue('Адрес доставки', $sub->address),
            self::customFieldValue('Дата доставки', $deliveryDate),
            self::customFieldValue('Время доставки', $windowLabel),
            self::customFieldValue('Источник заявки', 'Сайт ЦВЕТОФОР'),
            self::customFieldValue('Способ оплаты', 'Юкасса (сайты)'),
            self::customFieldValue('Доставка/Самовывоз', 'Доставка'),
            self::customFieldValue('Статус оплаты', $paid ? 'Оплачен' : 'НЕ ОПЛАЧЕН'),
            self::customFieldValue('Состав заказа', implode('. ', $composition)),
            self::customFieldValue('Комментарий к заказу', implode("\n", $comment)),
            self::customFieldValue('Название в чеке', $sub->planName()),
            self::customFieldValue('Передоплата', $prepayment),
            self::customFieldValue('Тип предоплаты', $paid ? 'Безнал' : null),
            self::customFieldValue('Оплачено безналом', $prepayment),
            self::customFieldValue('Дата приема заказ', $sub->created_at),
            self::customFieldValue('Телефон заказчика', self::digits($sub->sender_phone)),
        ]));
    }

    /** @return array<string, array<string, mixed>> */
    private static function leadCustomFields(): array {
        try {
            return Cache::remember('amocrm:lead-custom-fields', now()->addMinutes(5), function () {
                $fields = self::client()->get('/api/v4/leads/custom_fields')->throw()->json('_embedded.custom_fields', []);

                return collect($fields)
                    ->filter(fn (array $field) => !empty($field['name']) && !empty($field['id']))
                    ->mapWithKeys(fn (array $field) => [$field['name'] => $field])
                    ->all();
            });
        } catch (\Throwable $e) {
            Log::channel('shop')->warning('Не удалось получить поля сделки amoCRM.', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /** @return array<string, mixed>|null */
    private static function customFieldValue(string $name, mixed $value): ?array {
        if ($value === null || $value === '') {
            return null;
        }
        $field = self::leadCustomFields()[$name] ?? null;
        if (!$field) {
            return null;
        }

        if (($field['type'] ?? null) === 'date') {
            $value = $value instanceof Carbon ? $value->copy()->startOfDay()->timestamp : Carbon::parse($value)->startOfDay()->timestamp;
        }
        $entry = ['field_id' => (int) $field['id']];
        if (in_array($field['type'] ?? null, ['select', 'radiobutton'], true)) {
            $enum = collect($field['enums'] ?? [])->firstWhere('value', $value);
            if (!$enum) {
                Log::channel('shop')->warning('В amoCRM нет нужного варианта поля сделки.', ['field' => $name, 'value' => $value]);

                return null;
            }
            $entry['values'] = [['enum_id' => (int) $enum['id']]];
        } else {
            $entry['values'] = [['value' => (string) $value]];
        }

        return $entry;
    }

    private static function digits(?string $phone): ?string {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits ?: null;
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
