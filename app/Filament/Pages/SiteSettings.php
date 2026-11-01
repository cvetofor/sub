<?php

namespace App\Filament\Pages;

use App\Services\AmoCrm;
use App\Services\Settings;
use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Настройки сайта: какой дизайн показывать, какие СМС отправлять, работать ли с amoCRM. Без выката. */
class SiteSettings extends Page {
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Настройки сайта';

    protected static ?string $title = 'Настройки сайта';

    protected static string|UnitEnum|null $navigationGroup = 'Административное управлние элементами';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void {
        $all = Settings::all();
        $this->form->fill([
            'design_mode' => Settings::designMode(),
            'hero_mode' => Settings::heroMode(),
            'sms' => array_replace(Settings::DEFAULTS['sms'], is_array($all['sms'] ?? null) ? $all['sms'] : []),
            'unpaid_reminder_minutes' => (int) ($all['unpaid_reminder_minutes'] ?? 60),
            'amocrm_enabled' => (bool) ($all['amocrm_enabled'] ?? false),
            'amocrm_pipeline_id' => $all['amocrm_pipeline_id'] ?? null,
            'amocrm_status_new' => $all['amocrm_status_new'] ?? null,
            'amocrm_status_paid' => $all['amocrm_status_paid'] ?? null,
            'amocrm_responsible_user_id' => $all['amocrm_responsible_user_id'] ?? null,
        ]);
    }

    public function form(Schema $schema): Schema {
        $amoConfigured = AmoCrm::credentialsPresent();

        return $schema->components([
            Section::make('Дизайн сайта')
                ->description('Что видит новый посетитель на главной. Выбор запоминается в его браузере на 30 дней, поэтому при чередовании один человек всегда видит одну версию. Результаты: «Статистика дизайнов».')
                ->schema([
                    Radio::make('design_mode')
                        ->label('Какой дизайн показывать')
                        ->options(Settings::DESIGN_MODES)
                        ->required(),
                    Radio::make('hero_mode')
                        ->label('Первый экран классического дизайна')
                        ->helperText('Старый тест первого экрана внутри дизайна 1. Результаты: «A/B-тест главной».')
                        ->options(Settings::HERO_MODES)
                        ->required(),
                ]),
            Section::make('СМС клиентам')
                ->description('По неоплаченным заявкам СМС не отправляются никогда: напоминания планируются только после оплаты. Здесь можно выключить отдельные виды.')
                ->schema(array_merge(
                    array_map(
                        fn (string $kind, string $label) => Toggle::make('sms.' . $kind)->label($label)->default(true),
                        array_keys(Settings::SMS_KINDS),
                        Settings::SMS_KINDS
                    ),
                    [
                        TextInput::make('unpaid_reminder_minutes')
                            ->label('Через сколько минут напомнить о неоплаченной заявке')
                            ->numeric()->minValue(5)->maxValue(4320)->default(60)
                            ->helperText('Одна СМС на заявку со ссылкой на оплату, по заявкам не старше недели. Проверка каждые 10 минут.'),
                    ]
                )),
            Section::make('amoCRM')
                ->description($amoConfigured
                    ? 'Доступ настроен. Выбирайте воронку, этапы и ответственного по названиям — технические номера сайт сохранит сам. Новая заявка создаёт сделку с контактом; после оплаты она перейдёт на выбранный этап и получит примечание.'
                    : 'Доступ ещё не настроен: нужны адрес аккаунта и долгоживущий токен частной интеграции AmoCRM. Пока их нет, переключатель ниже не создаёт сделок. Пошаговая инструкция: docs/AMOCRM.md.')
                ->schema([
                    Toggle::make('amocrm_enabled')->label('Передавать заявки в AmoCRM'),
                    Select::make('amocrm_pipeline_id')
                        ->label('Воронка для подписок')
                        ->options(fn () => AmoCrm::pipelineOptions())
                        ->placeholder($amoConfigured ? 'Выберите воронку' : 'Появится после подключения AmoCRM')
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set): void {
                            $set('amocrm_status_new', null);
                            $set('amocrm_status_paid', null);
                        })
                        ->helperText('Выберите название воронки. Если оставить пустым, AmoCRM использует основную воронку аккаунта.'),
                    Select::make('amocrm_status_new')
                        ->label('Этап для новой заявки')
                        ->options(fn (callable $get) => AmoCrm::stageOptions((int) $get('amocrm_pipeline_id')))
                        ->placeholder('Сначала выберите воронку')
                        ->searchable()
                        ->disabled(fn (callable $get) => !$get('amocrm_pipeline_id'))
                        ->helperText('Сделка попадёт сюда сразу после оформления на сайте.'),
                    Select::make('amocrm_status_paid')
                        ->label('Этап после первой оплаты')
                        ->options(fn (callable $get) => AmoCrm::stageOptions((int) $get('amocrm_pipeline_id')))
                        ->placeholder('Сначала выберите воронку')
                        ->searchable()
                        ->disabled(fn (callable $get) => !$get('amocrm_pipeline_id'))
                        ->helperText('После успешной оплаты сайт переведёт сделку на этот этап. Пусто — этап не меняется.'),
                    Select::make('amocrm_responsible_user_id')
                        ->label('Ответственный менеджер')
                        ->options(fn () => AmoCrm::userOptions())
                        ->placeholder($amoConfigured ? 'По правилам AmoCRM' : 'Появится после подключения AmoCRM')
                        ->searchable()
                        ->helperText('Необязательно: если не выбирать, AmoCRM назначит ответственного по своим правилам.'),
                ])->columns(2),
        ])->statePath('data');
    }

    public function save(): void {
        $data = $this->form->getState();
        Settings::setMany([
            'design_mode' => $data['design_mode'],
            'hero_mode' => $data['hero_mode'],
            'sms' => array_map(fn ($v) => (bool) $v, $data['sms'] ?? []),
            'unpaid_reminder_minutes' => max(5, (int) ($data['unpaid_reminder_minutes'] ?? 60)),
            'amocrm_enabled' => (bool) ($data['amocrm_enabled'] ?? false),
            'amocrm_pipeline_id' => ($data['amocrm_pipeline_id'] ?? null) ?: null,
            'amocrm_status_new' => ($data['amocrm_status_new'] ?? null) ?: null,
            'amocrm_status_paid' => ($data['amocrm_status_paid'] ?? null) ?: null,
            'amocrm_responsible_user_id' => ($data['amocrm_responsible_user_id'] ?? null) ?: null,
        ]);

        Notification::make()->title('Настройки сохранены')->success()->send();
    }
}
