<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords {
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array {
        $currentUser = Filament::auth()->user();
        $action = $currentUser && $currentUser->role_id === 1 ? [CreateAction::make()] : [];

        return $action;
    }
}
