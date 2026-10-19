<?php

namespace App\Filament\Resources\Deliveries;

use App\Filament\Resources\Deliveries\Pages\ListDeliveries;
use App\Filament\Resources\Deliveries\Tables\DeliveriesTable;
use App\Models\Delivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Доставки: что везём сегодня и завтра, отметки флориста, выгрузка на день. */
class DeliveryResource extends Resource {
    protected static ?string $model = Delivery::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Доставки';

    protected static ?string $pluralModelLabel = 'Доставки';

    protected static ?string $modelLabel = 'доставку';

    protected static string | UnitEnum | null $navigationGroup = 'Подписки пользователей';

    protected static ?int $navigationSort = -1;

    public static function getNavigationBadge(): ?string {
        $today = Delivery::planned()->onDate(today())->whereHas('subscription', fn ($q) => $q->where('is_active', true))->count();

        return $today > 0 ? (string) $today : null;
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'Доставок сегодня';
    }

    public static function table(Table $table): Table {
        return DeliveriesTable::configure($table);
    }

    public static function getPages(): array {
        return [
            'index' => ListDeliveries::route('/'),
        ];
    }
}
