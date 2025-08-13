<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CreateUser extends CreateRecord {
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

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
                ])
            ])
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array {
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        return $data;
    }
}
