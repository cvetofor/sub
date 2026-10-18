<?php

namespace Tests\Feature;

use App\Models\AbEvent;
use App\Models\City;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanOption;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\TimeDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Сквозной сценарий: код из СМС → подписка → тестовая оплата → активация → управление в кабинете. */
class WizardFlowTest extends TestCase {
    use RefreshDatabase;

    private Plan $plan;
    private PlanOption $vase;
    private TimeDelivery $window;

    protected function setUp(): void {
        parent::setUp();
        DB::table('roles')->insert([
            ['id' => Role::ADMIN, 'name' => 'Админ', 'code' => 'admin'],
            ['id' => Role::MANAGER, 'name' => 'Менеджер', 'code' => 'manager'],
            ['id' => Role::CLIENT, 'name' => 'Клиент', 'code' => 'client'],
        ]);
        $city = City::create(['name' => 'Ярославль', 'code' => 'yar', 'is_active' => true]);
        $this->plan = Plan::create(['name' => 'С любовью', 'price' => 2990, 'description' => '', 'is_active' => true, 'is_custom' => false, 'city_id' => $city->id, 'scenario' => 'any', 'sort' => 2]);
        $this->vase = PlanOption::create(['name' => 'Ваза', 'price' => 2000, 'type' => 'addition', 'is_every_delivery' => false, 'is_active' => true]);
        $this->window = TimeDelivery::create(['from' => '09:00', 'to' => '11:00']);
    }

    private function loginByCode(string $phone = '8 912 345-67-89', string $name = 'Анна'): User {
        $sent = $this->postJson('/auth/code', ['phone' => $phone])->assertOk()->json();
        $this->assertSame('+79123456789', $sent['phone']);
        $this->postJson('/auth/verify', ['phone' => $sent['phone'], 'code' => '0000' === $sent['debug_code'] ? '1111' : '0000'])->assertStatus(422);
        $this->postJson('/auth/verify', ['phone' => $sent['phone'], 'code' => $sent['debug_code'], 'name' => $name])->assertOk()->assertJsonPath('ok', true);
        $this->assertAuthenticated();

        return User::where('phone_e164', '+79123456789')->firstOrFail();
    }

    private function payload(array $extra = []): array {
        return array_merge([
            'plan_id' => $this->plan->id, 'frequency' => 'biweekly', 'for' => 'gift',
            'styles' => ['Минимализм'], 'tags' => ['Без лилий'],
            'address' => 'Ярославль, ул. Свободы, 1, кв. 5', 'delivery_day' => 6, 'time_delivery_id' => $this->window->id,
            'option_ids' => [$this->vase->id], 'sender_name' => 'Анна',
            'receiving_name' => 'Мария', 'receiving_phone' => '+7 925 111-22-33', 'card_text' => 'С любовью',
        ], $extra);
    }

    public function test_guest_cannot_create_subscription(): void {
        $this->postJson('/subscribe', $this->payload())->assertUnauthorized();
    }

    public function test_full_flow_to_paid_subscription(): void {
        $user = $this->loginByCode();
        $this->assertSame(Role::CLIENT, (int) $user->role_id);

        $response = $this->withCredentials()->withCookies(['ab_hero' => 'a', 'ab_visitor' => str_repeat('cd', 16)])
            ->postJson('/subscribe', $this->payload())
            ->assertCreated()->assertJsonPath('ok', true);

        $sub = Subscription::findOrFail($response->json('subscription_id'));
        $this->assertSame($user->id, $sub->user_id);
        $this->assertSame('+79123456789', $sub->sender_phone);
        $this->assertSame('+79251112233', $sub->receiving_phone);
        $this->assertSame(6, $sub->delivery_day);
        $this->assertSame(['Минимализм'], $sub->preferences['styles']);
        $this->assertSame('a', $sub->ab_variant);
        $this->assertTrue($sub->using_promo);
        $this->assertFalse($sub->is_active);
        // Витринный план не изменился, подписка получила свою копию с вазой
        $this->assertNotSame($this->plan->id, $sub->plan_id);
        $this->assertSame(0, $this->plan->options()->count());
        $this->assertTrue((bool) $sub->plan->is_custom);
        // 2 букета по цене 1 + ваза разово
        $this->assertSame(2990.0 + 2000, $sub->firstPaymentAmount());
        $this->assertSame(2990.0 * 2, $sub->monthlyAmount());
        $this->assertSame(4990.0, (float) $response->json('amount'));
        $this->assertSame(1, AbEvent::where('event', 'payment_redirect')->count());

        // Без ключей ЮKassa вне production создаётся тестовый платёж
        $url = $response->json('payment_url');
        $this->assertStringContainsString('/payment/test/' . $sub->id, $url);
        $this->assertSame(4990.0, (float) $sub->payments()->first()->amount);

        $this->get($url)->assertOk()->assertSee('4 990');
        $this->post($url)->assertRedirect();

        $sub->refresh();
        $this->assertTrue($sub->is_active);
        $this->assertNotNull($sub->next_date_payment);
        $this->assertSame(Payment::PAYED, (int) $sub->payments()->first()->payment_status_id);
        $this->assertSame(1, AbEvent::where('event', 'payment_success')->where('variant', 'a')->count());

        $this->get('/payment/yookassa/redirect?subscription_id=' . $sub->id)->assertOk()->assertSee('Готово')->assertSee('purchase-data', false);
        $this->get('/profile')->assertOk()->assertSee('С любовью')->assertSee('Активна');
    }

    public function test_budget_creates_custom_plan(): void {
        $this->loginByCode();
        $response = $this->postJson('/subscribe', $this->payload(['plan_id' => null, 'budget' => 3500, 'frequency' => 'weekly', 'option_ids' => []]))->assertCreated();
        $sub = Subscription::findOrFail($response->json('subscription_id'));
        $this->assertTrue((bool) $sub->plan->is_custom);
        $this->assertSame(3500.0, (float) $sub->plan->price);
        $this->assertSame(3500.0 * 3, $sub->firstPaymentAmount());
    }

    public function test_validation_errors_are_in_russian(): void {
        $this->loginByCode();
        $this->postJson('/subscribe', $this->payload(['address' => 'ул']))->assertStatus(422)->assertJsonPath('errors.address.0', 'Укажите адрес: улица, дом, квартира.');
        $this->postJson('/subscribe', $this->payload(['plan_id' => 999]))->assertStatus(422)->assertJsonPath('ok', false);
    }

    public function test_only_owner_can_pause_and_resume(): void {
        $owner = $this->loginByCode();
        $id = $this->postJson('/subscribe', $this->payload())->json('subscription_id');
        $this->post('/payment/test/' . $id);

        $stranger = User::create(['name' => 'Чужой', 'email' => 'x@phone.local', 'phone' => '+79990000000', 'phone_e164' => '+79990000000', 'password' => 'x', 'role_id' => Role::CLIENT, 'is_active' => true]);
        $this->actingAs($stranger)->post("/subscription/{$id}/pause")->assertForbidden();
        $this->assertTrue(Subscription::find($id)->is_active);

        $this->actingAs($owner)->post("/subscription/{$id}/pause")->assertRedirect();
        $this->assertFalse(Subscription::find($id)->is_active);
        $this->actingAs($owner)->post("/subscription/{$id}/resume")->assertRedirect();
        $this->assertTrue(Subscription::find($id)->is_active);
    }

    public function test_code_is_rate_limited_per_phone(): void {
        $this->postJson('/auth/code', ['phone' => '9123456789'])->assertOk();
        $this->postJson('/auth/code', ['phone' => '9123456789'])->assertStatus(429);
        $this->postJson('/auth/code', ['phone' => '+7 495 123-45-67'])->assertStatus(422);
    }
}
