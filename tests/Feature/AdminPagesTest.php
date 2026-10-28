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
use App\Services\DeliverySchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
    }
}
