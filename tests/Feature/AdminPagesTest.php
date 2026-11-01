<?php

namespace Tests\Feature;

use App\Filament\Resources\Deliveries\Pages\ListDeliveries;
use App\Filament\Resources\Subscriptions\Pages\EditSubscription;
use App\Models\City;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\TimeDelivery;
use App\Models\User;
use App\Services\AmoCrm;
use App\Services\DeliverySchedule;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** Страницы админки открываются и показывают данные доставок. */
class AdminPagesTest extends TestCase {
    use RefreshDatabase;

    public function test_deliveries_list_and_subscription_edit_render(): void {
        DB::table('roles')->insert([
            ['id' => Role::ADMIN, 'name' => 'Админ', 'code' => 'admin'],
            ['id' => Role::MANAGER, 'name' => 'Менеджер', 'code' => 'manager'],
            ['id' => Role::CLIENT, 'name' => 'Клиент', 'code' => 'client'],
        ]);
        $admin = User::create(['name' => 'A', 'email' => 'a@site.local', 'password' => 'x', 'role_id' => Role::ADMIN, 'is_active' => true]);
        $city = City::create(['name' => 'Ярославль', 'code' => 'yar', 'is_active' => true]);
        $plan = Plan::create(['name' => 'С любовью', 'price' => 2990, 'description' => '', 'is_active' => true, 'is_custom' => false, 'city_id' => $city->id, 'scenario' => 'any', 'sort' => 2]);
        $window = TimeDelivery::create(['from' => '09:00', 'to' => '11:00']);
        $sub = Subscription::create(['is_active' => true, 'plan_id' => $plan->id, 'frequency' => 'weekly', 'delivery_day' => 6, 'time_delivery_id' => $window->id, 'sender_name' => 'Анна', 'sender_phone' => '+79123456789', 'address' => 'Ярославль, ул. Свободы, 1', 'preferences' => ['styles' => ['Минимализм'], 'card_text' => 'Привет']]);
        DeliverySchedule::planFirstMonth($sub);

        $this->actingAs($admin);
        Livewire::test(ListDeliveries::class)->assertOk()
            ->filterTable('period', ['from' => today()->toDateString(), 'to' => today()->addMonth()->toDateString()])
            ->assertCanSeeTableRecords(\App\Models\Delivery::all())
            ->assertSee('Ярославль, ул. Свободы, 1')->assertSee('Минимализм');
        Livewire::test(EditSubscription::class, ['record' => $sub->id])->assertOk()->assertSee('Пожелания клиента')->assertSee('Первый платёж');
        $this->get('/hub')->assertOk();
        // Виджеты дашборда грузятся отложенно, проверяем сам виджет
        Livewire::test(\App\Filament\Widgets\StatsOverview::class)->assertOk()->assertSee('Доставок сегодня')->assertSee('Активных подписок');

        $token = $sub->amoViewToken();
        $this->assertStringContainsString(route('amo.subscription.show', ['token' => $token]), AmoCrm::summary($sub));
        auth()->logout();
        $this->get(route('amo.subscription.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Только просмотр')
            ->assertSee('Ярославль, ул. Свободы, 1')
            ->assertDontSee('/hub/subscriptions/');
        $this->get(route('amo.subscription.show', ['token' => str_repeat('a', 64)]))->assertNotFound();
    }

    public function test_amo_lead_fills_known_custom_fields_on_creation_and_payment(): void {
        $city = City::create(['name' => 'Иркутск', 'code' => 'irk', 'is_active' => true]);
        $plan = Plan::create(['name' => 'Своя подписка', 'price' => 2000, 'description' => '', 'is_active' => true, 'is_custom' => true, 'city_id' => $city->id, 'scenario' => 'home']);
        $window = TimeDelivery::create(['from' => '10:00', 'to' => '12:00']);
        $sub = Subscription::create([
            'is_active' => true,
            'plan_id' => $plan->id,
            'frequency' => 'weekly',
            'delivery_day' => 1,
            'time_delivery_id' => $window->id,
            'sender_name' => 'Анна',
            'sender_phone' => '+79123456789',
            'receiving_name' => 'Мария',
            'receiving_phone' => '+79876543210',
            'address' => 'Иркутск, ул. Ленина, 1',
            'preferences' => ['card_text' => 'Поздравляем'],
        ]);
        DeliverySchedule::planFirstMonth($sub);

        config(['services.amocrm.subdomain' => 'amo', 'services.amocrm.token' => 'token']);
        Settings::set('amocrm_enabled', true);
        Http::fake([
            'https://amo.amocrm.ru/api/v4/leads/custom_fields' => Http::response(['_embedded' => ['custom_fields' => [
                ['id' => 1, 'name' => 'ФИО Получателя', 'type' => 'text'],
                ['id' => 2, 'name' => 'Телефон Получателя', 'type' => 'numeric'],
                ['id' => 3, 'name' => 'Адрес доставки', 'type' => 'streetaddress'],
                ['id' => 4, 'name' => 'Дата доставки', 'type' => 'date'],
                ['id' => 5, 'name' => 'Время доставки', 'type' => 'text'],
                ['id' => 6, 'name' => 'Источник заявки', 'type' => 'select', 'enums' => [['id' => 61, 'value' => 'Сайт ЦВЕТОФОР']]],
                ['id' => 7, 'name' => 'Способ оплаты', 'type' => 'select', 'enums' => [['id' => 71, 'value' => 'Юкасса (сайты)']]],
                ['id' => 8, 'name' => 'Доставка/Самовывоз', 'type' => 'radiobutton', 'enums' => [['id' => 81, 'value' => 'Доставка']]],
                ['id' => 9, 'name' => 'Статус оплаты', 'type' => 'select', 'enums' => [['id' => 91, 'value' => 'НЕ ОПЛАЧЕН'], ['id' => 92, 'value' => 'Оплачен']]],
                ['id' => 10, 'name' => 'Состав заказа', 'type' => 'textarea'],
                ['id' => 11, 'name' => 'Передоплата', 'type' => 'numeric'],
                ['id' => 12, 'name' => 'Тип предоплаты', 'type' => 'select', 'enums' => [['id' => 121, 'value' => 'Безнал']]],
                ['id' => 13, 'name' => 'Оплачено безналом', 'type' => 'numeric'],
                ['id' => 14, 'name' => 'Телефон заказчика', 'type' => 'text'],
            ]]], 200),
            'https://amo.amocrm.ru/api/v4/leads/complex' => Http::response([['id' => 123]], 200),
            'https://amo.amocrm.ru/api/v4/leads/123/notes' => Http::response([], 200),
            'https://amo.amocrm.ru/api/v4/leads/123' => Http::response([], 200),
        ]);

        $this->assertSame(123, AmoCrm::leadCreated($sub));
        AmoCrm::leadPaid($sub->fresh(), 6000);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://amo.amocrm.ru/api/v4/leads/complex') {
                return false;
            }
            $fields = collect($request->data()[0]['custom_fields_values'])->keyBy('field_id');

            return $fields[1]['values'][0]['value'] === 'Мария'
                && $fields[6]['values'][0]['enum_id'] === 61
                && $fields[7]['values'][0]['enum_id'] === 71
                && $fields[8]['values'][0]['enum_id'] === 81
                && $fields[9]['values'][0]['enum_id'] === 91
                && str_contains($fields[10]['values'][0]['value'], 'Своя подписка');
        });
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://amo.amocrm.ru/api/v4/leads/123') {
                return false;
            }
            $fields = collect($request->data()['custom_fields_values'])->keyBy('field_id');

            return $fields[9]['values'][0]['enum_id'] === 92
                && $fields[11]['values'][0]['value'] === '6000'
                && $fields[12]['values'][0]['enum_id'] === 121
                && $fields[13]['values'][0]['value'] === '6000';
        });
    }
}
