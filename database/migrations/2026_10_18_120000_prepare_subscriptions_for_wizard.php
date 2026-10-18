<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Приводим базу к тому, как код реально создаёт подписки, и добавляем поля мастера.
     * Все проверки через hasColumn: на боевой базе часть колонок уже могла быть изменена вручную.
     */
    public function up(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Пользователь появляется после кода из СМС, окно доставки выбирается не всегда.
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('time_delivery_id')->nullable()->change();
            $table->timestamp('next_date_payment')->nullable()->change();

            if (!Schema::hasColumn('subscriptions', 'preferences')) {
                // Стиль, предпочтения, день доставки, текст открытки, сценарий: JSON
                $table->json('preferences')->nullable()->after('comment');
            }
            if (!Schema::hasColumn('subscriptions', 'delivery_day')) {
                // День недели доставки, 1 = понедельник … 7 = воскресенье
                $table->unsignedTinyInteger('delivery_day')->nullable()->after('time_delivery_id');
            }
        });

        if (!Schema::hasColumn('payments', 'recurrent_token')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('recurrent_token')->nullable()->after('payment_gateway_transaction');
            });
        }

        if (!Schema::hasColumn('users', 'phone_e164')) {
            Schema::table('users', function (Blueprint $table) {
                // Телефон в едином виде +7XXXXXXXXXX для поиска при входе по коду
                $table->string('phone_e164', 16)->nullable()->after('phone')->index();
            });

            foreach (DB::table('users')->whereNotNull('phone')->get(['id', 'phone']) as $user) {
                $digits = preg_replace('/\D+/', '', (string) $user->phone);
                if (strlen($digits) === 11 && in_array($digits[0], ['7', '8'], true)) {
                    DB::table('users')->where('id', $user->id)->update(['phone_e164' => '+7' . substr($digits, 1)]);
                } elseif (strlen($digits) === 10) {
                    DB::table('users')->where('id', $user->id)->update(['phone_e164' => '+7' . $digits]);
                }
            }
        }
    }

    public function down(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['preferences', 'delivery_day']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_e164');
        });
    }
};
