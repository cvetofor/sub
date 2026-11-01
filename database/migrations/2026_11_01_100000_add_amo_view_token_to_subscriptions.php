<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'amo_view_token')) {
                $table->string('amo_view_token', 64)->nullable()->unique()->after('amo_lead_id');
            }
        });
    }

    public function down(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'amo_view_token')) {
                $table->dropUnique(['amo_view_token']);
                $table->dropColumn('amo_view_token');
            }
        });
    }
};
