<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Role;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable {
    public static function configure(Table $table): Table {


        return $table
            ->columns([
                TextColumn::make('name')->label('Имя')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role_id')
                    ->label('Роль')
                    ->formatStateUsing(fn($state) => Role::find($state)?->name ?? 'Не указана'),
                IconColumn::make('is_active')->label('Активирован')->boolean()->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Статус пользователя')
                    ->options([
                        false => 'Не активирован',
                        true => 'Активирован'
                    ]),
                SelectFilter::make('role_id')
                    ->label('Роль')
                    ->options(fn() => Role::pluck('name', 'id')->toArray())
            ])
            ->recordActions(\App\Filament\AvailableActions::get())
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Фильтры'),
            )
            ->deferFilters(false)
            ->persistFiltersInSession();
    }
}
