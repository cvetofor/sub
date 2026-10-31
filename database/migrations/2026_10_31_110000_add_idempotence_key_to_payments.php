<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Храним постоянный ключ одной повторной оплаты. Если запрос до ЮKassa
     * оборвётся, следующий запуск повторит именно эту операцию, а не создаст новую.
     */
    public function up(): void {
        if (!Schema::hasColumn('payments', 'idempotence_key')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('idempotence_key', 64)->nullable()->unique()->after('payment_gateway_transaction');
            });
        }
    }

    public function down(): void {
        if (Schema::hasColumn('payments', 'idempotence_key')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique(['idempotence_key']);
                $table->dropColumn('idempotence_key');
            });
        }
    }
};
