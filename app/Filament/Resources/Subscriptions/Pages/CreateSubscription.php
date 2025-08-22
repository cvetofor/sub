<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\City;
use App\Models\Plan;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateSubscription extends CreateRecord {
    protected static string $resource = SubscriptionResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema {
        $frequencyElems = \App\Enums\Frequency::getFrequencyElem();
        $frequencyOptions = [];
        foreach ($frequencyElems as $elem) {
            $frequencyOptions[$elem['code']] = $elem['translate'];
        }

        return $schema->components([
            Section::make('Контактная информация')
                ->schema([
                    TextInput::make('sender_name')
                        ->label('ФИО отправителя')
                        ->required(),

                    TextInput::make('sender_phone')
                        ->label('Номер телефона отправителя')
                        ->mask('+7 (999) 999-9999')
                        ->required(),

                    Checkbox::make('same_as_sender')
                        ->label('Получатель совпадает с отправителем')
                        ->reactive()
                        ->afterStateUpdated(function (bool $state, callable $set, callable $get) {
                            if ($state) {
                                $set('receiving_name', $get('sender_name'));
                                $set('receiving_phone', $get('sender_phone'));
                            } else {
                                $set('receiving_name', null);
                                $set('receiving_phone', null);
                            }
                        }),

                    TextInput::make('receiving_name')
                        ->label('ФИО получателя')
                        ->disabled(fn(callable $get) => $get('same_as_sender')),

                    TextInput::make('receiving_phone')
                        ->label('Номер телефона получателя')
                        ->mask('+7 (999) 999-9999')
                        ->disabled(fn(callable $get) => $get('same_as_sender')),


                    FusedGroup::make([
                        TextInput::make('address')
                            ->placeholder('Адрес доставки')
                            ->required()
                            ->columnSpan(2),

                        Select::make('city_id')
                            ->placeholder('Город')
                            ->options(fn() => City::active()->pluck('name', 'id'))
                            ->reactive()
                            ->required(),
                    ])
                        ->columns(3)
                ]),

            Grid::make()->schema([
                Section::make('Информация о выбранном плане')
                    ->schema([
                        Select::make('plan_id')
                            ->label('План')
                            ->options(function (callable $get) {
                                $cityId = $get('city_id');
                                if (!$cityId) {
                                    return [];
                                }

                                return Plan::active()
                                    ->where('is_custom', false)
                                    ->where('city_id', $cityId)
                                    ->pluck('name', 'id');
                            })
                            ->reactive()
                            ->required()
                            ->disabled(fn(callable $get) => !$get('city_id'))
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $plan = Plan::find($state);
                                    $set('plan_price', $plan->price ?? 0);
                                } else {
                                    $set('plan_price', 0);
                                }
                            }),

                        Select::make('frequency')
                            ->label('Частота')
                            ->options(fn() => $frequencyOptions)
                            ->reactive()
                            ->required()
                            ->disabled(fn(callable $get) => !$get('plan_id'))
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $planPrice = $get('plan_price');
                                    $frequencyCount = \App\Enums\Frequency::getFrequencyElem($state)['count'] ?? 0;
                                    $planId = $get('plan_id');
                                    $plan = Plan::find($planId);
                                    $sumPlanOptons = $plan->options->where('is_every_delivery', true)->sum('price');
                                    $sumOncePlanOptons = $plan->options->where('is_every_delivery', false)->sum('price');

                                    $totalPrice = ($planPrice + $sumPlanOptons) * $frequencyCount + $sumOncePlanOptons;
                                    $set('total_price', $totalPrice ?? 0);
                                    $set('plan_opt_price',  $sumPlanOptons + $sumOncePlanOptons ?? 0);
                                } else {
                                    $set('total_price', 0);
                                }
                            }),
                    ]),

                Section::make('Стоимость')
                    ->schema([
                        TextInput::make('plan_price')
                            ->label('Стоимость подписки')
                            ->suffix('₽')
                            ->hintIcon('heroicon-o-information-circle', tooltip: 'Стоимость указана за 1 доставку')
                            ->reactive()
                            ->disabled()
                            ->default(fn(callable $get) => $get('plan_price') ?? 0),

                        TextInput::make('plan_opt_price')
                            ->label('Сумма опций плана')
                            ->suffix('₽')
                            ->hintIcon('heroicon-o-information-circle', tooltip: 'Стоимость указана за 1 доставку')
                            ->reactive()
                            ->disabled()
                            ->default(fn(callable $get) => $get('plan_price') ?? 0),


                        TextInput::make('total_price')
                            ->label('Общая сумма подписки')
                            ->suffix('₽')
                            ->reactive()
                            ->disabled()
                            ->default(fn(callable $get) => $get('plan_price') ?? 0),
                    ]),
            ])
                ->columns(1)
        ]);
    }
}
