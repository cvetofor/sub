<?php

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateCity extends CreateRecord {
    protected static string $resource = CityResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema {
        return $schema->components([
            Grid::make(1)->schema([
                Section::make('Детали')->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->trim(),

                    Toggle::make('is_active')
                        ->label('Активировать')
                ])
            ])
        ]);
    }
}
