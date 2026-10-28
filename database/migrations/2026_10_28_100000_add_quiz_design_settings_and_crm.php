<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Второй дизайн сайта (квиз) и A/B-тест между дизайнами, настройки в админке,
     * сделки amoCRM, два названия у тарифа (себе / в подарок), фото и описание у дополнений.
     */
    public function up(): void {
        // Настройки сайта, которые меняются из админки без выката: ключ → значение в JSON
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('key', 64)->primary();
                $table->json('value')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('ab_events', function (Blueprint $table) {
            if (!Schema::hasColumn('ab_events', 'design')) {
                // classic (главная с мастером) или quiz: по этому полю считается статистика дизайнов
                $table->string('design', 8)->nullable()->after('variant')->index();
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'ab_design')) {
                $table->string('ab_design', 8)->nullable()->after('ab_visitor')->index();
            }
            if (!Schema::hasColumn('subscriptions', 'source')) {
                // wizard (мастер классического дизайна) | quiz | admin
                $table->string('source', 16)->nullable()->after('ab_design');
            }
            if (!Schema::hasColumn('subscriptions', 'amo_lead_id')) {
                $table->unsignedBigInteger('amo_lead_id')->nullable()->after('source');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'login_token')) {
                // Вход по ссылке из СМС без кода: токен и срок его жизни
                $table->string('login_token', 40)->nullable()->unique()->after('phone_e164');
                $table->timestamp('login_token_expires_at')->nullable()->after('login_token');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'name_gift')) {
                // Название того же тарифа в сценарии «В подарок». Пусто: показываем основное название
                $table->string('name_gift')->nullable()->after('name');
            }
        });

        Schema::table('plan_options', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_options', 'description')) {
                $table->string('description', 160)->nullable()->after('price');
            }
            if (!Schema::hasColumn('plan_options', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
            if (!Schema::hasColumn('plan_options', 'sort')) {
                $table->unsignedSmallInteger('sort')->default(0)->after('image');
            }
        });

        // Подписки, созданные до этого выката, оформлены через мастер классического дизайна
        DB::table('subscriptions')->whereNull('source')->update(['source' => 'wizard', 'ab_design' => 'classic']);
        DB::table('ab_events')->whereNull('design')->update(['design' => 'classic']);

        // Названия тарифов по решению владельца: себе / в подарок, одинаковые цены и состав
        $names = [
            'Просто цветы' => ['Просто цветы', 'Просто так'],
            'Забота без напоминаний' => ['Забота', 'С любовью'],
            'Забота' => ['Забота', 'С любовью'],
            'Цветочная терапия' => ['Авторский', 'С размахом'],
            'Авторский' => ['Авторский', 'С размахом'],
            'Букет XL' => ['Букет XL', 'Букет XL'],
        ];
        foreach ($names as $old => [$self, $gift]) {
            DB::table('plans')->where('is_custom', false)->where('name', $old)->whereNull('name_gift')
                ->update(['name' => $self, 'name_gift' => $gift, 'scenario' => 'any']);
        }
        // Тариф за 990 ₽, чтобы «от 990 ₽» на сайте было честным
        $has990 = DB::table('plans')->where('is_custom', false)->where('price', '<=', 990)->exists();
        if (!$has990 && DB::table('plans')->where('is_custom', false)->exists()) {
            $cityId = DB::table('cities')->where('is_active', true)->value('id');
            DB::table('plans')->insert([
                'name' => 'Мини',
                'name_gift' => 'Знак внимания',
                'price' => 990,
                'description' => 'Компактный букет, 5–7 стеблей. Свежие цветы дня, доставка включена.',
                'is_active' => true,
                'is_custom' => false,
                'city_id' => $cityId,
                'scenario' => 'any',
                'tag' => 'компактный, 5–7 стеблей',
                'sort' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {
        Schema::table('plan_options', function (Blueprint $table) {
            $table->dropColumn(['description', 'image', 'sort']);
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('name_gift');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_token', 'login_token_expires_at']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['ab_design', 'source', 'amo_lead_id']);
        });
        Schema::table('ab_events', function (Blueprint $table) {
            $table->dropColumn('design');
        });
        Schema::dropIfExists('settings');
    }
};
