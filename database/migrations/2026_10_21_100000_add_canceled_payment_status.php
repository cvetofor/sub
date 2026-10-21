<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /** Статус «отменён» для платежей, по уведомлению payment.canceled от ЮKassa. */
    public function up(): void {
        if (!DB::table('payment_statuses')->where('id', 3)->exists()) {
            DB::table('payment_statuses')->insert(['id' => 3, 'code' => 'canceled', 'name' => 'Отменён', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void {
    }
};
