<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Delivery;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\TimeDelivery;
use App\Models\User;
use App\Services\DeliverySchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** Расписание доставок: после оплаты, при смене дня, пропуске, паузе и продлении. */
class DeliveriesTest extends TestCase {
    use RefreshDatabase;

    private Plan $plan;
    private TimeDelivery $window;

    protected function setUp(): void {
        parent::setUp();
        Carbon::setTestNow('2026-10-19 10:00:00'); // понедельник
        DB::table('roles')->insert([
            ['id' => Role::ADMIN, 'name' => 'Админ', 'code' => 'admin'],
            ['id' => Role::MANAGER, 'name' => 'Менеджер', 'code' => 'manager'],
            ['id' => Role::CLIENT, 'name' => 'Клиент', 'code' => 'client'],
        ]);
        $city = City::create(['name' => 'Ярославль', 'code' => 'yar', 'is_active' => true]);
        $this->plan = Plan::create(['name' => 'С любовью', 'price' => 2990, 'description' => '', 'is_active' => true, 'is_custom' => false, 'city_id' => $city->id, 'scenario' => 'any', 'sort' => 2]);
        $this->window = TimeDelivery::create(['from' => '09:00', 'to' => '11:00']);
    }

    protected function tearDown(): void {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function paidSubscription(string $frequency = 'biweekly', int $day = 6): array {
        $sent = $this->postJson('/auth/code', ['phone' => '9123456789'])->json();
        $this->postJson('/auth/verify', ['phone' => $sent['phone'], 'code' => $sent['debug_code'], 'name' => 'Анна'])->assertOk();
        $user = User::where('phone_e164', '+79123456789')->firstOrFail();
        $id = $this->postJson('/subscribe', [
            'plan_id' => $this->plan->id, 'frequency' => $frequency, 'address' => 'Ярославль, ул. Свободы, 1, кв. 5',
            'delivery_day' => $day, 'time_delivery_id' => $this->window->id, 'sender_name' => 'Анна',
        ])->assertCreated()->json('subscription_id');
        $this->post('/payment/test/' . $id)->assertRedirect();

        return [$user, Subscription::findOrFail($id)];
    }

    public function test_first_month_schedule_matches_promo(): void {
        [, $weekly] = $this->paidSubscription('weekly', 6);
        $this->assertSame(['2026-10-24', '2026-10-31', '2026-11-07', '2026-11-14'], $weekly->deliveries->pluck('planned_date')->map->toDateString()->all());

        auth()->logout();
        $monthly = Subscription::create(['plan_id' => $this->plan->id, 'frequency' => 'monthly', 'delivery_day' => 3, 'sender_name' => 'Б', 'sender_phone' => '+79990000000', 'address' => 'x', 'time_delivery_id' => $this->window->id]);
        DeliverySchedule::planFirstMonth($monthly);
        // Раз в месяц: в первый месяц два букета через две недели
        $this->assertSame(['2026-10-21', '2026-11-04'], $monthly->deliveries->pluck('planned_date')->map->toDateString()->all());
        DeliverySchedule::planFirstMonth($monthly);
        $this->assertSame(2, $monthly->deliveries()->count(), 'повторный вызов не дублирует');

        DeliverySchedule::planNextMonth($monthly);
        $this->assertSame('2026-12-02', $monthly->deliveries()->orderByDesc('planned_date')->value('planned_date')->toDateString() ?? '');
        $this->assertSame(3, $monthly->deliveries()->count());
    }

    public function test_changing_weekday_moves_future_deliveries(): void {
        [$user, $sub] = $this->paidSubscription('biweekly', 6); // 24.10, 07.11
        $this->actingAs($user)->post("/subscription/{$sub->id}/delivery", ['address' => $sub->address, 'delivery_day' => 3, 'time_delivery_id' => $this->window->id])->assertRedirect('/profile');
        $this->assertSame(['2026-10-21', '2026-11-04'], $sub->deliveries()->planned()->orderBy('planned_date')->pluck('planned_date')->map->toDateString()->all());
    }

    public function test_skip_moves_bouquet_to_the_end_and_protects_near_deliveries(): void {
        [$user, $sub] = $this->paidSubscription('biweekly', 6); // 24.10, 07.11
        $first = $sub->deliveries()->planned()->orderBy('planned_date')->first();
        $this->actingAs($user)->post("/subscription/{$sub->id}/deliveries/{$first->id}/skip")->assertRedirect('/profile')->assertSessionHas('success');
        $this->assertSame(Delivery::SKIPPED, $first->fresh()->status);
        $this->assertSame(['2026-11-07', '2026-11-21'], $sub->deliveries()->planned()->orderBy('planned_date')->pluck('planned_date')->map->toDateString()->all());

        // Доставка послезавтра уже собирается: пропустить нельзя
        Carbon::setTestNow('2026-11-06 10:00:00');
        $near = $sub->deliveries()->planned()->orderBy('planned_date')->first();
        $this->actingAs($user)->post("/subscription/{$sub->id}/deliveries/{$near->id}/skip")->assertSessionHas('error');
        $this->assertSame(Delivery::PLANNED, $near->fresh()->status);

        // Чужую доставку пропустить нельзя
        $stranger = User::create(['name' => 'Ч', 'email' => 'x@phone.local', 'phone' => '+79990000001', 'phone_e164' => '+79990000001', 'password' => 'x', 'role_id' => Role::CLIENT, 'is_active' => true]);
        $this->actingAs($stranger)->post("/subscription/{$sub->id}/deliveries/{$near->id}/skip")->assertForbidden();
    }

    public function test_resume_after_pause_shifts_overdue_deliveries(): void {
        [$user, $sub] = $this->paidSubscription('biweekly', 6); // 24.10, 07.11
        $this->actingAs($user)->post("/subscription/{$sub->id}/pause")->assertRedirect();
        $this->assertSame(Subscription::PAUSE_CLIENT, $sub->fresh()->pause_reason);

        Carbon::setTestNow('2026-11-10 10:00:00'); // вторник, обе даты прошли
        $this->actingAs($user)->post("/subscription/{$sub->id}/resume")->assertRedirect();
        $this->assertNull($sub->fresh()->pause_reason);
        $this->assertSame(['2026-11-14', '2026-11-28'], $sub->deliveries()->planned()->orderBy('planned_date')->pluck('planned_date')->map->toDateString()->all());
    }

    public function test_reminders_go_out_once_for_tomorrow_only(): void {
        [, $sub] = $this->paidSubscription('biweekly', 6); // 24.10
        Carbon::setTestNow('2026-10-23 18:00:00');
        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info', 'warning', 'error')->byDefault();

        $this->artisan('schedule:run')->assertSuccessful();
        $first = $sub->deliveries()->orderBy('planned_date')->first();
        $this->assertNotNull($first->fresh()->reminded_at, 'напоминание отправлено и отмечено');

        $this->artisan('schedule:run')->assertSuccessful();
        $this->assertSame(1, Delivery::whereNotNull('reminded_at')->count(), 'второй раз не шлём');
    }

    public function test_cabinet_shows_upcoming_deliveries_and_florist_export_works(): void {
        [$user, $sub] = $this->paidSubscription('biweekly', 6);
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Ближайшие доставки')->assertSee('24 октября')->assertSee('Пропустить');

        $csv = \App\Filament\Resources\Deliveries\Pages\ListDeliveries::export(Carbon::parse('2026-10-24'));
        ob_start();
        $csv->sendContent();
        $body = ob_get_clean();
        $this->assertStringContainsString('Ярославль, ул. Свободы, 1, кв. 5', $body);
        $this->assertStringContainsString('С любовью', $body);
        $this->assertStringContainsString('09:00–11:00', $body);
    }

    public function test_team_gets_telegram_messages_when_configured(): void {
        config(['services.telegram.token' => 'test-token', 'services.telegram.chat_id' => '-100']);
        \Illuminate\Support\Facades\Http::fake(['api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true])]);

        [$user, $sub] = $this->paidSubscription('biweekly', 6);
        $this->actingAs($user)->post("/subscription/{$sub->id}/delivery", ['address' => $sub->address, 'delivery_day' => 3, 'time_delivery_id' => $this->window->id]);
        $this->actingAs($user)->post("/subscription/{$sub->id}/pause");

        \Illuminate\Support\Facades\Http::assertSentCount(3); // новая подписка, смена доставки, пауза
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r['text'], 'Новая подписка') && str_contains($r['text'], 'Ярославль') && $r['chat_id'] === '-100');
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r['text'], 'изменил доставку: Среда') && str_contains($r['text'], '/hub/subscriptions/' . $sub->id));
    }

    public function test_cabinet_shows_payment_history_and_wizard_shows_city(): void {
        [$user] = $this->paidSubscription('biweekly', 6);
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('История платежей')->assertSee('2 990 ₽');
        $this->get('/subscribe')->assertOk()->assertSee('Доставляем по городу Ярославль');
    }
}
