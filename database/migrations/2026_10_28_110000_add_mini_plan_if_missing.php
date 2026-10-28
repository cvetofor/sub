<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Тариф за 990 ₽ («Мини» / «Знак внимания»), если среди активных витринных тарифов его нет.
     * Предыдущая миграция учитывала и скрытый тестовый тариф за 1 ₽, поэтому на боевой базе тариф не создался.
     */
    public function up(): void {
        $hasActive990 = DB::table('plans')->where('is_custom', false)->where('is_active', true)->where('price', '<=', 990)->exists();
        $hasMini = DB::table('plans')->where('is_custom', false)->whereIn('name', ['Мини', 'Знак внимания'])->exists();
        if ($hasActive990 || $hasMini || !DB::table('plans')->where('is_custom', false)->exists()) {
            return;
        }
        DB::table('plans')->insert([
            'name' => 'Мини',
            'name_gift' => 'Знак внимания',
            'price' => 990,
            'description' => 'Компактный букет, 5–7 стеблей. Свежие цветы дня, доставка включена.',
            'is_active' => true,
            'is_custom' => false,
            'city_id' => DB::table('cities')->where('is_active', true)->value('id'),
            'scenario' => 'any',
            'tag' => 'компактный, 5–7 стеблей',
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void {
        DB::table('plans')->where('is_custom', false)->where('name', 'Мини')->where('price', 990)->delete();
    }
};
