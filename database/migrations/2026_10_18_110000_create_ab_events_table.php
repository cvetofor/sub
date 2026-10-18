<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Журнал событий воронки для A/B-теста первого экрана.
     * Одна строка — одно событие посетителя. Админка считает воронку по этой таблице.
     */
    public function up(): void {
        Schema::create('ab_events', function (Blueprint $table) {
            $table->id();
            $table->char('variant', 1)->index();
            $table->string('event', 40);
            $table->string('visitor', 64)->index();
            $table->unsignedTinyInteger('step')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('mode', 8)->nullable();
            $table->string('frequency', 12)->nullable();
            $table->string('source', 32)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index(['variant', 'event', 'visitor']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->char('ab_variant', 1)->nullable()->after('using_promo');
            $table->string('ab_visitor', 64)->nullable()->after('ab_variant');
        });
    }

    public function down(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['ab_variant', 'ab_visitor']);
        });
        Schema::dropIfExists('ab_events');
    }
};
