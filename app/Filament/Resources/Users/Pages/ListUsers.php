<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords {
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array {
        $currentUser = Filament::auth()->user();
        $action = $currentUser && $currentUser->role_id === 1 ? [CreateAction::make()] : [];

        return $action;
    }
}
