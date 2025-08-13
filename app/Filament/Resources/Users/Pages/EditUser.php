<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class EditUser extends EditRecord {
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array {
        return [
            DeleteAction::make(),
        ];
    }

    public function form(Schema $schema): Schema {
        $currentUser = Filament::auth()->user();

        $fields = [
            TextInput::make('name')
                ->label('Имя')
                ->trim()
                ->required(),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(User::class, 'email')
                ->trim(),
        ];

        if ($currentUser && $currentUser->role_id === 1) {
            $fields[] = TextInput::make('password')
                ->label('Пароль')
                ->password()
                ->dehydrated(fn($state) => filled($state))
                ->trim();

            $fields[] = Select::make('role_id')
                ->label('Роль')
                ->options(Role::pluck('name', 'id'))
                ->required();
        } else {
            $fields[] = TextEntry::make('role_id')
                ->label('Роль')
                ->getStateUsing(fn($record) => optional($record->role)->name);
        }

        $fields[] = Toggle::make('is_active')
            ->label('Активирован');

        return $schema->components([
            Grid::make(1)->schema([
                Section::make('Детали')->schema($fields),
            ]),
            Grid::make(1)->schema([
                Section::make('Ревизия')->schema([
                    TextEntry::make('created_at')
                        ->label('Дата создания')
                        ->dateTime(),
                    TextEntry::make('updated_at')
                        ->label('Дата последнего редактирования')
                        ->dateTime(),
                ]),
            ]),
        ]);
    }
}
