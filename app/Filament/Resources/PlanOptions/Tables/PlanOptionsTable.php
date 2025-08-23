<?php

namespace App\Filament\Resources\PlanOptions\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlanOptionsTable {
    const typeOptions = [
        'delivery' => 'Доставка',
        'style' => 'Стиль букетов',
        'preference' => 'Предпочтение',
        'addition' => 'Дополнительно',
    ];

    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('name')->label('Имя')->searchable(),
                TextColumn::make('price')->label('Стоимость')->suffix(' ₽')->searchable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(function ($state) {

                        return  self::typeOptions[$state] ?? 'Не указана';
                    })
                    ->searchable(),
                IconColumn::make('is_active')->label('Активирован')->boolean()->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Статус опции')
                    ->options([
                        false => 'Не активирована',
                        true => 'Активирована'
                    ]),

                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(self::typeOptions),
            ])
            ->recordActions(\App\Filament\AvailableActions::get())
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Фильтры'),
            )
            ->searchable()
            ->deferFilters(false)
            ->persistFiltersInSession();
    }
}
