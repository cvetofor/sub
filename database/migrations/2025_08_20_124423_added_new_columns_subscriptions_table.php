<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('sender_name');
            $table->string('sender_phone');
            $table->string('receiving_name');
            $table->string('receiving_phone');
            $table->string('address');
            $table->string('frequency');
            $table->string('comment')->nullable();
            $table->boolean('using_promo')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('comment');
            $table->dropColumn('sender_name');
            $table->dropColumn('receiving_name');
        });
    }
};
