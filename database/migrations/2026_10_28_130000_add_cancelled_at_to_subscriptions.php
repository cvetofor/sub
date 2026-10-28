<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Клиент отменил неоплаченную заявку из кабинета: она скрывается у клиента, напоминания не уходят. */
    public function up(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('unpaid_reminded_at');
            }
        });
    }

    public function down(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });
    }
};
