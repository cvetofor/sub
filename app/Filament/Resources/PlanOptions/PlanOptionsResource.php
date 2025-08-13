<?php

namespace App\Filament\Resources\PlanOptions;

use App\Filament\Resources\PlanOptions\Pages\CreatePlanOptions;
use App\Filament\Resources\PlanOptions\Pages\EditPlanOptions;
use App\Filament\Resources\PlanOptions\Pages\ListPlanOptions;
use App\Filament\Resources\PlanOptions\Schemas\PlanOptionsForm;
use App\Filament\Resources\PlanOptions\Tables\PlanOptionsTable;
use App\Models\PlanOptions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PlanOptionsResource extends Resource {
    protected static ?string $model = PlanOptions::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Административное управлние элементами';

    public static function form(Schema $schema): Schema {
        return PlanOptionsForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return PlanOptionsTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListPlanOptions::route('/'),
            'create' => CreatePlanOptions::route('/create'),
            'edit' => EditPlanOptions::route('/{record}/edit'),
        ];
    }
}
