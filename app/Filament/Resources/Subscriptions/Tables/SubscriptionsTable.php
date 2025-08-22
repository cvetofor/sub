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
                    ->url(fn($record) => '/admin/plans/' . $record->plan_id . '/edit')
                    ->color('primary'),
                IconColumn::make('using_promo')
                    ->label('Использован промо')
                    ->alignCenter()
                    ->boolean(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label('Активная подписка')
                    ->toggle()
                    ->default(true)
                    ->query(function ($query, array $data) {
                        if ($data['isActive'] === true) {
                            $query->where('is_active', true);
                        } elseif ($data['isActive'] === false) {
                            $query->where('is_active', false);
                        }

                        return $query;
                    })
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Фильтры'),
            )
            ->deferFilters(false)
            ->persistFiltersInSession();
    }
}
