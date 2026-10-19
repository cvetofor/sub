<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Запланированные доставки по подпискам: по ним работают напоминания, выгрузка флористу и кабинет. */
    public function up(): void {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->date('planned_date');
            $table->foreignId('time_delivery_id')->nullable()->constrained('time_deliveries')->nullOnDelete();
            // planned → delivered | skipped (клиент пропустил) | cancelled (подписка остановлена)
            $table->string('status', 16)->default('planned');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();

            $table->index(['planned_date', 'status']);
            $table->index(['subscription_id', 'status']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'pause_reason')) {
                // client | payment_failed | admin: почему подписка не активна
                $table->string('pause_reason', 24)->nullable()->after('is_active');
            }
        });
    }

    public function down(): void {
        Schema::dropIfExists('deliveries');
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('pause_reason');
        });
    }
};
