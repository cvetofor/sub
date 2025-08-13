<?php

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditCity extends EditRecord {
    protected static string $resource = CityResource::class;


    protected function getHeaderActions(): array {
        $currentUser = Filament::auth()->user();

        return $currentUser && $currentUser->role_id === 1 ? [DeleteAction::make()] : [];
    }

    public function form(Schema $schema): Schema {
        return $schema->components([
            Grid::make(1)->schema([
                Section::make('Детали')->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->trim()
                        ->required()
                        ->trim(),

                    Toggle::make('is_active')
                        ->label('Активировать')
                ])
            ]),
            Grid::make(1)->schema([
                Section::make('Ревизия')->schema([
                    TextEntry::make('created_at')
                        ->label('Дата создания')
                        ->dateTime(),
                    TextEntry::make('updated_at')
                        ->label('Дата последнего редактирования')
                        ->dateTime(),
                ])
            ])
        ]);
    }
}
