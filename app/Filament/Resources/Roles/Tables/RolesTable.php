<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable {
    public static function configure(Table $table): Table {
        $currentUser = Filament::auth()->user();
        $action = $currentUser && $currentUser->role_id === 1 ? [
            EditAction::make(),
            DeleteAction::make()
        ] : [
            EditAction::make()
        ];


        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название'),
            ])
            ->recordActions($action)
            ->searchable()
            ->persistFiltersInSession()
            ->toolbarActions([]);;
    }
}
