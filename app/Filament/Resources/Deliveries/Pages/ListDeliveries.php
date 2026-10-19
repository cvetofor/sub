<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Models\Delivery;
use App\Support\Days;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDeliveries extends ListRecords {
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array {
        return [
            Action::make('export')
                ->label('Выгрузка флористу (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->schema([
                    DatePicker::make('date')->label('Дата')->default(today())->required()->native(false)->displayFormat('d.m.Y'),
                ])
                ->action(fn (array $data) => self::export(Carbon::parse($data['date']))),
        ];
    }

    /** CSV в кодировке UTF-8 с BOM, чтобы Excel открыл кириллицу без вопросов. Разделитель «;». */
    public static function export(Carbon $date) {
        $rows = Delivery::onDate($date)
            ->whereIn('status', [Delivery::PLANNED, Delivery::DELIVERED])
            ->whereHas('subscription', fn ($q) => $q->where('is_active', true))
            ->with(['subscription.plan.options', 'timeDelivery'])
            ->get()
            ->sortBy(fn ($d) => ($d->timeDelivery?->from ?? '99') . $d->subscription->address);

        $filename = 'dostavki-' . $date->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows, $date) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Дата', 'Окно', 'Подписка', 'План', 'Бюджет букета', 'Дополнения', 'Стиль', 'Учесть', 'Для кого', 'Открытка', 'Получатель', 'Телефон получателя', 'Заказчик', 'Телефон заказчика', 'Адрес', 'Комментарий курьеру', 'Статус'], ';');
            foreach ($rows as $d) {
                $s = $d->subscription;
                $p = $s->preferences ?? [];
                fputcsv($out, [
                    $date->format('d.m.Y') . ' (' . Days::short($date->dayOfWeekIso) . ')',
                    $d->windowLabel(),
                    '№' . $s->id,
                    $s->plan->name,
                    number_format((float) $s->plan->price, 0, '', ' ') . ' ₽',
                    $s->plan->options->pluck('name')->join(', '),
                    implode(', ', $p['styles'] ?? []),
                    implode(', ', $p['tags'] ?? []),
                    ($p['for'] ?? null) === 'gift' ? 'В подарок' : (($p['for'] ?? null) === 'home' ? 'Себе домой' : ''),
                    $p['card_text'] ?? '',
                    $s->receiving_name,
                    $s->receiving_phone,
                    $s->sender_name,
                    $s->sender_phone,
                    $s->address,
                    $s->comment,
                    Delivery::STATUSES[$d->status] ?? $d->status,
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
