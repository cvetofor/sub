<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CitiesTable {
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                IconColumn::make('is_active')->label('Активирован')->boolean()->alignCenter(),
            ])
            ->recordActions(\App\Filament\AvailableActions::get())
            ->toolbarActions([]);
    }
}
