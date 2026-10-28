<?php

namespace App\Filament\Resources\PlanOptions\Pages;

use App\Filament\Resources\PlanOptions\PlanOptionsResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class EditPlanOptions extends EditRecord {
    protected static string $resource = PlanOptionsResource::class;

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
                            'addition' => 'Дополнение к букету (ваза, конфеты, открытка)',
                            'occasion' => 'Повод',
                            'recipient' => 'Получатель'
                        ])
                        ->required(),

                    Toggle::make('is_every_delivery')
                        ->label('Каждую доставку?')
                        ->helperText('Включено: добавляется к каждому букету и к ежемесячному списанию. Выключено: один раз, только в первый платёж (ваза, яркая открытка).')
                        ->required(),

                    TextInput::make('description')
                        ->label('Короткое описание для сайта')
                        ->placeholder('Стеклянная, под высоту букета')
                        ->maxLength(160)
                        ->trim(),

                    FileUpload::make('image')
                        ->label('Фото для квиза (3:2)')
                        ->image()
                        ->disk('public')
                        ->directory('options')
                        ->imageEditor()
                        ->maxSize(4096),

                    TextInput::make('sort')
                        ->label('Порядок показа')
                        ->numeric()
                        ->default(0),

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
