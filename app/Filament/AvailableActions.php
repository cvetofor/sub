<?php

namespace App\Filament;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;

class AvailableActions {
    public static function get() {
        $currentUser = Filament::auth()->user();

        $actions = $currentUser && $currentUser->role_id === 1 ? [
            EditAction::make(),
            DeleteAction::make()
        ] : [
            EditAction::make()
        ];

        return $actions;
    }
}
