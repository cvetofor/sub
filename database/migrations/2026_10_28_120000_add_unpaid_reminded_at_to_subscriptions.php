<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Когда клиенту ушла СМС о неоплаченной заявке со ссылкой на оплату: одна на заявку. */
    public function up(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'unpaid_reminded_at')) {
                $table->timestamp('unpaid_reminded_at')->nullable()->after('amo_lead_id');
            }
        });
    }

    public function down(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('unpaid_reminded_at');
        });
    }
};
