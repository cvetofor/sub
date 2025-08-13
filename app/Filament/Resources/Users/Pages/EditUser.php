<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\View\Components\ButtonComponent;

class EditUser extends EditRecord {
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array {
        return [
            DeleteAction::make(),
        ];
    }

    public function form(Schema $schema): Schema {
        return $schema->components([
            Grid::make(1)->schema([
                Section::make('Детали')->schema([
                    TextInput::make('name')
                        ->label('Имя')
                        ->required(),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(User::class, 'email'),

                    TextInput::make('password')
                        ->label('Пароль')
                        ->password()
                        ->required()
                        ->dehydrated(fn($state) => filled($state)),

                    Select::make('role_id')
                        ->label('Роль')
                        ->options(Role::pluck('name', 'id')),

                    Checkbox::make('is_active')
                        ->label('Активирован')
                ]),

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

    public function getRoles() {
        return Role::all();
    }
}
