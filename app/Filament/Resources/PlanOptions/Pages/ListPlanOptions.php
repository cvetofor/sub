<?php

namespace App\Filament\Resources\PlanOptions\Pages;

use App\Filament\Resources\PlanOptions\PlanOptionsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlanOptions extends ListRecords
{
    protected static string $resource = PlanOptionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
