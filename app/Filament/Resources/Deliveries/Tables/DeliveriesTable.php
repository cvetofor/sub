<?php

namespace App\Filament\Resources\Deliveries\Tables;

use App\Models\Delivery;
use App\Support\Days;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveriesTable {
    public static function configure(Table $table): Table {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['subscription.plan.options', 'timeDelivery']))
            ->defaultSort('planned_date')
            ->columns([
                TextColumn::make('planned_date')
                    ->label('Дата')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('d.m.Y') . ' ' . Days::short(Carbon::parse($state)->dayOfWeekIso))
                    ->sortable(),
                TextColumn::make('timeDelivery.from')
                    ->label('Окно')
                    ->formatStateUsing(fn ($record) => $record->windowLabel()),
                TextColumn::make('subscription_id')
                    ->label('Подписка')
                    ->formatStateUsing(fn ($state) => '№' . $state)
                    ->url(fn ($record) => '/hub/subscriptions/' . $record->subscription_id . '/edit')
                    ->color('primary'),
                TextColumn::make('subscription.plan.name')->label('План'),
                TextColumn::make('subscription.receiving_name')->label('Получатель')->searchable(),
                TextColumn::make('subscription.receiving_phone')->label('Телефон')->searchable(),
                TextColumn::make('subscription.address')->label('Адрес')->searchable()->limit(50)->tooltip(fn ($record) => $record->subscription->address),
                TextColumn::make('wishes')
                    ->label('Пожелания')
                    ->state(function ($record) {
                        $p = $record->subscription->preferences ?? [];
                        $parts = array_filter([
                            implode(', ', $p['styles'] ?? []),
                            implode(', ', $p['tags'] ?? []),
                            $record->subscription->plan->options->pluck('name')->join(', '),
                            !empty($p['card_text']) ? 'Открытка' : null,
                        ]);

                        return implode(' · ', $parts) ?: '—';
                    })
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Delivery::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        Delivery::PLANNED => 'warning',
                        Delivery::DELIVERED => 'success',
                        Delivery::SKIPPED => 'gray',
                        default => 'danger',
                    }),
                TextColumn::make('reminded_at')->label('СМС')->since()->placeholder('—')->toggleable(),
                TextColumn::make('note')->label('Заметка')->limit(30)->toggleable(),
            ])
            ->filters([
                Filter::make('period')
                    ->label('Период')
                    ->schema([
                        DatePicker::make('from')->label('С')->native(false)->displayFormat('d.m.Y')->default(today()),
                        DatePicker::make('to')->label('По')->native(false)->displayFormat('d.m.Y')->default(today()->addDays(7)),
                    ])
                    // Параметры закрытий Filament подставляет по имени: обязательно $query и $data
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['from'])) {
                            $query->whereDate('planned_date', '>=', $data['from']);
                        }
                        if (!empty($data['to'])) {
                            $query->whereDate('planned_date', '<=', $data['to']);
                        }
                    })
                    ->indicateUsing(fn (array $data) => array_filter([
                        !empty($data['from']) ? 'с ' . Carbon::parse($data['from'])->format('d.m.Y') : null,
                        !empty($data['to']) ? 'по ' . Carbon::parse($data['to'])->format('d.m.Y') : null,
                    ])),
                SelectFilter::make('status')->label('Статус')->options(Delivery::STATUSES)->default(Delivery::PLANNED),
                Filter::make('active_only')->label('Только активные подписки')->toggle()->default(true)
                    ->query(fn (Builder $query) => $query->whereHas('subscription', fn ($s) => $s->where('is_active', true))),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->recordActions([
                Action::make('delivered')
                    ->label('Доставлено')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn ($record) => $record->status === Delivery::PLANNED)
                    ->action(fn ($record) => $record->update(['status' => Delivery::DELIVERED, 'delivered_at' => now()])),
                Action::make('reschedule')
                    ->label('Перенести')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->visible(fn ($record) => $record->status === Delivery::PLANNED)
                    ->schema([
                        DatePicker::make('planned_date')->label('Новая дата')->required()->native(false)->displayFormat('d.m.Y')->minDate(today()),
                        TextInput::make('note')->label('Заметка')->maxLength(300),
                    ])
                    ->fillForm(fn ($record) => ['planned_date' => $record->planned_date, 'note' => $record->note])
                    ->action(fn ($record, array $data) => $record->update(['planned_date' => $data['planned_date'], 'note' => $data['note'] ?? null, 'reminded_at' => null])),
                Action::make('cancel')
                    ->label('Отменить')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === Delivery::PLANNED)
                    ->action(fn ($record) => $record->update(['status' => Delivery::CANCELLED])),
            ])
            ->toolbarActions([]);
    }
}
