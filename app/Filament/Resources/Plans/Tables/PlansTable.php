<?php

namespace App\Filament\Resources\Plans\Tables;

use App\Models\City;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlansTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable(),
                TextColumn::make('price')->label('Стоимость')->suffix(' ₽')->searchable(),
                TextColumn::make('description')->label('Описание')->limit(55)->searchable(),
                TextColumn::make('city_id')
                    ->label('Город')
                    ->formatStateUsing(fn($state) => City::find($state)?->name ?? 'Город не найден'),
                IconColumn::make('is_custom')->label('Пользовательский план')->alignCenter()->boolean(),
                IconColumn::make('is_active')->label('Активирован')->alignCenter()->boolean(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Статус города')
                    ->options([
                        false => 'Не активирован',
                        true => 'Активирован'
                    ]),
                SelectFilter::make('city_id')
                    ->label('Город')
                    ->options(fn() => City::active()->pluck('name', 'id'))
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
