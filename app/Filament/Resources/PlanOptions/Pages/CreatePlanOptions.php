<?php

namespace App\Filament\Resources\PlanOptions\Pages;

use App\Filament\Resources\PlanOptions\PlanOptionsResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class CreatePlanOptions extends CreateRecord {
    protected static string $resource = PlanOptionsResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema {
        return $schema->components([
            Grid::make(1)->schema([
                Section::make('Детали')->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->trim()
                        ->required()
                        ->trim(),

                    TextInput::make('price')
                        ->label('Стоимость')
                        ->trim()
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->suffix('₽')
                        ->required()
                        ->trim(),

                    Select::make('type')
                        ->label('Тип')
                        ->options([
                            'delivery' => 'Доставка',
                            'style' => 'Стиль букетов',
                            'preference' => 'Предпочтение',
                            'addition' => 'Дополнительно',
                            'occasion' => 'Повод',
                            'recipient' => 'Получатель'
                        ])
                        ->required(),

                    // Select::make('occasion')
                    //     ->label('Тип')
                    //     ->options([
                    //         'love' => 'Любю безумно',
                    //         'reason' => 'Без повода',
                    //         'birthday' => 'День рождения',
                    //         'anniversary' => 'Годовщина',
                    //         'office' => 'В офис',
                    //         'home' => 'В дом'
                    //     ])
                    //     ->required(),

                    // Select::make('recipient')
                    //     ->label('Тип')
                    //     ->options([
                    //         'myself' => 'Для себя',
                    //         'partner' => 'Партнёру/супругу',
                    //         'parents' => 'Родителям',
                    //         'colleague' => 'Коллеге',
                    //         'family' => 'Детям/семьям',
                    //         'other' => 'Другое',
                    //     ])
                    //     ->required(),

                    Toggle::make('is_active')
                        ->label('Активировать')
                ])
            ])
        ]);
    }
}
