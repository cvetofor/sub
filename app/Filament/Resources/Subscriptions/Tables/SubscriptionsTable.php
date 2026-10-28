<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Models\Plan;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class SubscriptionsTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Номер подписки')
                    ->searchable(),
                TextColumn::make('sender_name')
                    ->label('ФИО заказчика')
                    ->searchable(),
                TextColumn::make('sender_phone')
                    ->label('Номер телефона заказчика')
                    ->searchable(),
                TextColumn::make('plan_id')
                    ->label('Название плана')
                    ->formatStateUsing(fn($state) => Plan::find($state)?->name ?? 'План не найден')
                    ->url(fn($record) => '/hub/plans/' . $record->plan_id . '/edit')
                    ->color('primary'),
                TextColumn::make('frequency')
                    ->label('Частота')
                    ->formatStateUsing(fn ($state) => \App\Services\Pricing::LABELS[$state] ?? $state),
                TextColumn::make('delivery_day')
                    ->label('День')
                    ->formatStateUsing(fn ($state) => $state ? \App\Support\Days::NOMINATIVE[$state] ?? '—' : '—'),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->alignCenter()
                    ->boolean(),
                TextColumn::make('paid_state')
                    ->label('Оплата')
                    ->state(fn ($record) => $record->isPaid() ? 'Оплачена' : 'Не оплачена')
                    ->badge()
                    ->color(fn ($state) => $state === 'Оплачена' ? 'success' : 'warning'),
                TextColumn::make('source')
                    ->label('Откуда')
                    ->formatStateUsing(fn ($state) => match ($state) { 'quiz' => 'Квиз', 'wizard' => 'Мастер', default => '—' })
                    ->toggleable(),
                TextColumn::make('amo_lead_id')
                    ->label('amoCRM')
                    ->formatStateUsing(fn ($state) => $state ? '№' . $state : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('paid')
                    ->label('Оплата')
                    ->options(['paid' => 'Оплаченные', 'unpaid' => 'Не оплаченные заявки'])
                    ->query(function ($query, array $data) {
                        $paid = fn ($q) => $q->where('payment_status_id', \App\Models\Payment::PAYED);
                        if (($data['value'] ?? null) === 'paid') {
                            $query->whereHas('payments', $paid);
                        } elseif (($data['value'] ?? null) === 'unpaid') {
                            $query->whereDoesntHave('payments', $paid);
                        }

                        return $query;
                    }),
                \Filament\Tables\Filters\SelectFilter::make('source')
                    ->label('Откуда')
                    ->options(['quiz' => 'Квиз', 'wizard' => 'Мастер']),
                Filter::make('is_active')
                    ->label('Активная подписка')
                    ->toggle()
                    ->query(function ($query, array $data) {
                        if ($data['isActive'] === true) {
                            $query->where('is_active', true);
                        } elseif ($data['isActive'] === false) {
                            $query->where('is_active', false);
                        }

                        return $query;
                    })
            ])
            ->recordActions(\App\Filament\AvailableActions::get())
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Фильтры'),
            )
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->defaultSort('created_at', 'desc');
    }
}
