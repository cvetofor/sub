<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Http\Controllers\PaymentController;
use App\Models\Plan;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Support\View\Components\ButtonComponent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EditSubscription extends EditRecord {
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array {
        return [
            DeleteAction::make(),
        ];
    }

    public function form(Schema $schema): Schema {
        $subscription = $schema->model;
        $plan = Plan::find($subscription->plan_id);
        $optName = '';
        $optPrice = 0;
        if (isset($plan->options)) {
            $optName = $plan->options->pluck('name')->implode(', ');
            $optPrice = $plan->options
                ->where('is_every_delivery', true)
                ->sum('price');
            $optPriceOneTime = $plan->options
                ->where('is_every_delivery', false)
                ->sum('price');
        }

        $frequency = \App\Enums\Frequency::getFrequencyElem($subscription->frequency);

        return $schema->components([
            Section::make('Контактная информация')
                ->schema([
                    TextInput::make('sender_name')
                        ->label('ФИО отправителя'),
                    TextInput::make('sender_phone')
                        ->label('номер телефона отправителя'),
                    TextInput::make('receiving_name')
                        ->label('ФИО получателя'),
                    TextInput::make('receiving_phone')
                        ->label('Номер телефона получателя'),
                    TextInput::make('address')
                        ->label('Адрес доставки'),
                ]),

            Section::make('Информация о выбранном плане')
                ->schema([
                    TextEntry::make('plan_name')
                        ->label('Название')
                        ->default($plan?->name),
                    TextEntry::make('plan_price')
                        ->label('Стоимость за доставку')
                        ->suffix('₽')
                        ->default($plan->price),
                    TextEntry::make('plan_description')
                        ->label('Описание')
                        ->default($plan?->description),
                    TextEntry::make('plan_city')
                        ->label('Город')
                        ->default($plan?->city?->name),
                    TextEntry::make('plan_options')
                        ->label('Опции')
                        ->default($optName),
                ]),

            Tabs::make('tabs')->tabs([
                Tab::make('Общая информация')
                    ->icon(Heroicon::InformationCircle)
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('')->schema([
                                TextEntry::make('created_at')
                                    ->label('Дата начала подписки')
                                    ->dateTime(),

                                TextEntry::make('frequency_translate')
                                    ->label('Частота доставки')
                                    ->default("{$frequency['translate']} (≈{$frequency['count']} доставок в месяц)"),

                                TextEntry::make('next_date_payment')
                                    ->label('Следующий платеж'),
                            ]),
                            Section::make('')->schema([
                                TextEntry::make('plan_price')
                                    ->label('Стоимость подписки')
                                    ->default($plan->price)
                                    ->suffix('₽')
                                    ->hintIcon('heroicon-o-information-circle', tooltip: 'Стоимость указана за 1 доставку'),

                                TextEntry::make('options_price_every_delivery')
                                    ->label('Сумма опций плана')
                                    ->suffix('₽')
                                    ->default($optPrice + $optPriceOneTime)
                                    ->hintIcon('heroicon-o-information-circle', tooltip: 'Стоимость указана за 1 доставку'),

                                TextEntry::make('total_price')
                                    ->label('Общая сумма подписки')
                                    ->suffix('₽')
                                    ->default($subscription->totalAmount()),

                                Text::make('Расчёт: (база за доставку + опции за доставку) × доставок в месяц + сумма одноразовых опций')
                                    ->color('warning')

                            ])
                        ]),
                    ]),
                Tab::make('Платежи')
                    ->icon(Heroicon::Banknotes)
                    ->schema([
                        Section::make()->schema([
                            TextEntry::make('payment_link')
                                ->label('Сгенерированная ссылка на оплату:')
                                ->copyable()
                                ->color('primary')
                                ->reactive()
                                ->visible(fn($get) => !empty($get('payment_link')))
                                ->hintIcon('heroicon-o-information-circle', tooltip: 'Скопируйте ссылку, т.к. после перезагрузки страницы — она пропадет!'),


                            View::make('filament.schemas.components.payments-table')
                                ->viewData([
                                    'payments' => $subscription->payments,
                                ])
                                ->reactive(),
                        ])
                            ->afterHeader([
                                Action::make('generate_payment_link')
                                    ->label('Сгенерировать ссылку на оплату')
                                    ->action(function (callable $set): void {
                                        $paymentController = app()->make(PaymentController::class);

                                        $link = $paymentController->create($this->record);

                                        $set('payment_link', $link);
                                    }),
                            ])
                    ])
            ])
                ->columns(1)
                ->columnSpan('full'),
        ]);
    }
}
