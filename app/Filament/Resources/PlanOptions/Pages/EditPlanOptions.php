<?php

namespace App\Filament\Resources\PlanOptions\Pages;

use App\Filament\Resources\PlanOptions\PlanOptionsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlanOptions extends EditRecord
{
    protected static string $resource = PlanOptionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
