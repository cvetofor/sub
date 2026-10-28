<?php

namespace App\Filament\Pages;

use App\Services\Settings;
use BackedEnum;
use Filament\Forms\Components\Radio;
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
            'amocrm_enabled' => (bool) ($all['amocrm_enabled'] ?? false),
            'amocrm_pipeline_id' => $all['amocrm_pipeline_id'] ?? null,
            'amocrm_status_new' => $all['amocrm_status_new'] ?? null,
            'amocrm_status_paid' => $all['amocrm_status_paid'] ?? null,
            'amocrm_responsible_user_id' => $all['amocrm_responsible_user_id'] ?? null,
        ]);
    }

    public function form(Schema $schema): Schema {
        $amoConfigured = (bool) config('services.amocrm.subdomain') && (bool) config('services.amocrm.token');

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
                ->schema(array_map(
                    fn (string $kind, string $label) => Toggle::make('sms.' . $kind)->label($label)->default(true),
                    array_keys(Settings::SMS_KINDS),
                    Settings::SMS_KINDS
                )),
            Section::make('amoCRM')
                ->description($amoConfigured
                    ? 'Доступ настроен в окружении (AMOCRM_SUBDOMAIN, AMOCRM_TOKEN). Заявка с сайта создаёт сделку с контактом сразу после оформления, оплата переводит сделку на следующий этап и дописывает примечание.'
                    : 'Доступ не настроен: добавьте AMOCRM_SUBDOMAIN и AMOCRM_TOKEN (долгосрочный токен интеграции) в .env на сервере. До этого сделки не создаются.')
                ->schema([
                    Toggle::make('amocrm_enabled')->label('Передавать заявки в amoCRM'),
                    TextInput::make('amocrm_pipeline_id')->label('ID воронки')->numeric()->helperText('Пусто: основная воронка аккаунта.'),
                    TextInput::make('amocrm_status_new')->label('ID этапа для новой заявки')->numeric()->helperText('Пусто: первый этап воронки.'),
                    TextInput::make('amocrm_status_paid')->label('ID этапа после оплаты')->numeric()->helperText('Пусто: этап не меняется, только примечание и сумма.'),
                    TextInput::make('amocrm_responsible_user_id')->label('ID ответственного менеджера')->numeric()->helperText('Пусто: по правилам amoCRM.'),
                ])->columns(2),
        ])->statePath('data');
    }

    public function save(): void {
        $data = $this->form->getState();
        Settings::setMany([
            'design_mode' => $data['design_mode'],
            'hero_mode' => $data['hero_mode'],
            'sms' => array_map(fn ($v) => (bool) $v, $data['sms'] ?? []),
            'amocrm_enabled' => (bool) ($data['amocrm_enabled'] ?? false),
            'amocrm_pipeline_id' => $data['amocrm_pipeline_id'] ?: null,
            'amocrm_status_new' => $data['amocrm_status_new'] ?: null,
            'amocrm_status_paid' => $data['amocrm_status_paid'] ?: null,
            'amocrm_responsible_user_id' => $data['amocrm_responsible_user_id'] ?: null,
        ]);

        Notification::make()->title('Настройки сохранены')->success()->send();
    }
}
