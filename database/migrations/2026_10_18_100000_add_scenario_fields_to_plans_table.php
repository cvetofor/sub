<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Новая главная: у плана появляются сценарий (дом / подарок / любой),
     * короткая подпись под названием, фото карточки и порядок вывода.
     */
    public function up(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('scenario', 16)->default('any')->after('is_custom');
            $table->string('tag')->nullable()->after('scenario');
            $table->string('image')->nullable()->after('tag');
            $table->unsignedSmallInteger('sort')->default(0)->after('image');
        });
    }

    public function down(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['scenario', 'tag', 'image', 'sort']);
        });
    }
};
