<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /** Статусы оплаты, на которые ссылается код (Payment::IN_PROGRESS, Payment::PAYED). Добавляем только недостающие. */
    public function up(): void {
        $rows = [
            ['id' => 1, 'code' => 'in_progress', 'name' => 'В обработке'],
            ['id' => 2, 'code' => 'payed', 'name' => 'Оплачено'],
        ];
        foreach ($rows as $row) {
            if (!DB::table('payment_statuses')->where('id', $row['id'])->exists()) {
                DB::table('payment_statuses')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void {
        // Справочник оставляем: на него могут ссылаться платежи
    }
};
