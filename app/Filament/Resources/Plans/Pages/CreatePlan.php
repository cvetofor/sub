<?php

namespace App\Filament\Resources\Plans\Pages;

use App\Filament\Resources\Plans\PlanResource;
use App\Models\City;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreatePlan extends CreateRecord {
    protected static string $resource = PlanResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema {
        return $schema->components([
            Grid::make(1)->schema([
                Section::make('Детали')->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->trim(),

                    TextInput::make('price')
                        ->label('Стоимость')
                        ->numeric(false)
                        ->suffix('₽')
                        ->required()
                        ->trim()
                        ->hintIcon('heroicon-o-information-circle', tooltip: 'Стоимость указывается за 1 доставку'),

                    Textarea::make('description')
                        ->label('Описание')
                        ->required()
                        ->trim(),

                    Select::make('city_id')
                        ->label('Город')
                        ->options(fn() => City::active()->pluck('name', 'id'))->required(),

                    Toggle::make('is_active')
                        ->label('Активировать')
                ])
            ]),
            Grid::make(1)->schema([
                Section::make('Опции')->schema([
                    Select::make('options')
                        ->label('Опции')
                        ->multiple()
                        ->relationship(
                            name: 'options',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn($query) => $query->where('is_active', true)
                        )
                        ->preload()
                ])
            ]),

        ]);
    }
}
