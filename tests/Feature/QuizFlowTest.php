<?php

namespace Tests\Feature;

use App\Filament\Widgets\DesignFunnelWidget;
use App\Http\Middleware\AssignDesign;
use App\Models\AbEvent;
use App\Models\City;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanOption;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\TimeDelivery;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** Квиз: переключение дизайна, оформление без кода, оплата, ссылка входа из СМС, amoCRM, статистика. */
class QuizFlowTest extends TestCase {
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
        $city = City::create(['name' => 'Улан-Удэ', 'code' => 'ulan', 'is_active' => true]);
        Plan::create(['name' => 'Мини', 'name_gift' => 'Знак внимания', 'price' => 990, 'description' => '', 'is_active' => true, 'is_custom' => false, 'city_id' => $city->id, 'scenario' => 'any', 'sort' => 0]);
        $this->plan = Plan::create(['name' => 'Забота', 'name_gift' => 'С любовью', 'price' => 2990, 'description' => '', 'is_active' => true, 'is_custom' => false, 'city_id' => $city->id, 'scenario' => 'any', 'sort' => 2]);
        $this->vase = PlanOption::create(['name' => 'Ваза', 'price' => 900, 'type' => 'addition', 'is_every_delivery' => false, 'is_active' => true, 'description' => 'Стеклянная']);
        $this->window = TimeDelivery::create(['from' => '13:00', 'to' => '15:00']);
        Settings::forget();
    }

    private function payload(array $extra = []): array {
        return array_merge([
            'who' => 'gift', 'frequency' => 'weekly', 'plan_id' => $this->plan->id,
            'option_ids' => [$this->vase->id], 'card_text' => 'Просто так', 'wishes' => 'Без лилий',
            'receiving_name' => 'Анна', 'receiving_phone' => '+7 924 000-00-00', 'hide_sender' => true, 'agree_with_recipient' => false,
            'address' => 'ул. Ленина, 12', 'apartment' => '34', 'intercom' => '34', 'delivery_day' => 5, 'time_delivery_id' => $this->window->id,
            'sender_name' => 'Максим', 'sender_phone' => '8 924 774-04-04',
        ], $extra);
    }

    public function test_design_switch_from_settings(): void {
        Settings::set('design_mode', Settings::DESIGN_QUIZ);
        $this->get('/')->assertOk()->assertSee('id="quiz"', false)->assertSee('Оформите за две минуты')->assertCookie(AssignDesign::DESIGN_COOKIE);
        $this->assertSame('quiz', AbEvent::where('event', 'visit')->value('design'));

        Settings::set('design_mode', Settings::DESIGN_CLASSIC);
        $this->get('/')->assertOk()->assertSee('id="site-data"', false)->assertDontSee('id="quiz"', false);
        // Принудительный выбор для проверки руками работает в любом режиме
        $this->get('/?design=quiz')->assertOk()->assertSee('id="quiz"', false);
        $this->get('/quiz')->assertOk()->assertSee('id="quiz"', false);

        Settings::set('design_mode', Settings::DESIGN_SPLIT);
        $response = $this->get('/')->assertOk();
        $design = $response->getCookie(AssignDesign::DESIGN_COOKIE)->getValue();
        $this->assertContains($design, ['classic', 'quiz']);
        // Тот же посетитель видит тот же дизайн
        $this->withCookie(AssignDesign::DESIGN_COOKIE, $design)->get('/')->assertOk()->assertCookie(AssignDesign::DESIGN_COOKIE, $design);
    }

    public function test_quiz_shows_gift_names_and_addons(): void {
        $html = $this->get('/quiz')->assertOk()->assertSee('Знак внимания')->assertSee('С любовью')->assertSee('Ваза')->assertSee('13:00–15:00')->getContent();
        $this->assertLessThan(strpos($html, 'data-name="Забота"'), strpos($html, 'data-name="Мини"'), 'тарифы в квизе идут по цене');
    }

    public function test_guest_creates_subscription_and_pays_without_sms_code(): void {
        $response = $this->withCredentials()->withCookies(['ab_design' => 'quiz', 'ab_visitor' => str_repeat('ef', 16)])
            ->postJson('/quiz/subscribe', $this->payload())
            ->assertCreated()->assertJsonPath('ok', true);
        $this->assertGuest();

        $sub = Subscription::findOrFail($response->json('subscription_id'));
        $this->assertSame('quiz', $sub->source);
        $this->assertSame('quiz', $sub->ab_design);
        $this->assertSame('q', $sub->ab_variant);
        $this->assertSame('+79247740404', $sub->sender_phone);
        $this->assertSame('+79240000000', $sub->receiving_phone);
        $this->assertSame('Анна', $sub->receiving_name);
        $this->assertSame('ул. Ленина, 12, кв. 34, домофон 34', $sub->address);
        $this->assertSame('gift', $sub->preferences['for']);
        $this->assertTrue($sub->preferences['hide_sender']);
        $this->assertSame('Без лилий', $sub->preferences['wishes']);
        $this->assertSame('С любовью', $sub->planName());
        $this->assertFalse($sub->is_active);
        $this->assertSame(0, $sub->deliveries()->count(), 'по неоплаченной заявке доставки и СМС не планируются');
        // Клиент создан по номеру, но не вошёл
        $user = User::where('phone_e164', '+79247740404')->firstOrFail();
        $this->assertSame($user->id, $sub->user_id);
        $this->assertSame('Максим', $user->name);
        // 4 букета по цене 3 + ваза
        $this->assertSame(2990.0 * 3 + 900, $sub->firstPaymentAmount());
        $this->assertSame(1, AbEvent::where('event', 'payment_redirect')->where('design', 'quiz')->count());

        // Гость без подписи на страницу оплаты не попадёт, с подписанной ссылкой попадёт и войдёт
        $url = $response->json('payment_url');
        $this->assertStringContainsString('signature=', $url);
        $this->get('/payment/test/' . $sub->id)->assertRedirect('/auth/login');
        $this->get($url)->assertOk()->assertSee('9 870');
        $this->assertAuthenticatedAs($user);
        $this->post($url)->assertRedirect();

        $sub->refresh();
        $this->assertTrue($sub->is_active);
        $this->assertSame(4, $sub->deliveries()->count());
        $this->assertSame(1, AbEvent::where('event', 'payment_success')->where('design', 'quiz')->count());
        // СМС после оплаты содержит ссылку входа без кода
        $user->refresh();
        $this->assertNotNull($user->login_token);

        // Страница «Что дальше» в стиле квиза
        $this->get('/payment/yookassa/redirect?subscription_id=' . $sub->id . '&attempt=9')
            ->assertOk()->assertSee('Готово, Максим')->assertSee('С любовью')->assertSee('Второй букет, бесплатно')->assertSee('purchase-data', false);
    }

    public function test_signed_return_url_logs_guest_in_and_login_link_works(): void {
        $id = $this->postJson('/quiz/subscribe', $this->payload(['who' => 'home', 'option_ids' => []]))->assertCreated()->json('subscription_id');
        $sub = Subscription::findOrFail($id);
        $this->post($this->postJson('/quiz/subscribe', [])->json('payment_url') ?? '/nowhere'); // мусорный запрос не ломает
        $this->get(Subscription::findOrFail($id)->payments()->first() ? \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.test', now()->addDay(), ['subscription' => $id]) : '/')->assertOk();
        $this->post('/payment/test/' . $id)->assertRedirect();
        auth()->logout();

        // Возврат с оплаты без входа, но с подписью ЮKassa: показываем итог и впускаем в кабинет
        $this->get('/payment/yookassa/redirect?subscription_id=' . $id . '&attempt=9')->assertRedirect('/auth/login');
        $signed = \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.yookassa.redirect', now()->addDay(), ['subscription_id' => $id]);
        $this->get($signed . '&attempt=9')->assertOk()->assertSee('Готово');
        $this->assertAuthenticated();
        auth()->logout();

        // Ссылка из СМС: /k/{token}
        $user = $sub->fresh()->user;
        $this->assertNotNull($user->login_token);
        $this->get('/k/' . $user->login_token)->assertRedirect('/profile');
        $this->assertAuthenticatedAs($user);
        auth()->logout();
        $this->get('/k/AAAAAAAAAAAA')->assertRedirect('/auth/login');
        $this->assertGuest();
        $user->forceFill(['login_token_expires_at' => now()->subDay()])->save();
        $this->get('/k/' . $user->login_token)->assertRedirect('/auth/login');
        $this->assertGuest();
    }

    public function test_validation_and_staff_phone(): void {
        $this->postJson('/quiz/subscribe', $this->payload(['sender_phone' => '12345']))->assertStatus(422)->assertJsonPath('errors.sender_phone.0', 'В номере не хватает цифр. Пример: +7 924 000-00-00');
        $this->postJson('/quiz/subscribe', $this->payload(['who' => 'office']))->assertStatus(422);
        $this->postJson('/quiz/subscribe', $this->payload(['plan_id' => null, 'budget' => 500]))->assertStatus(422)->assertJsonPath('errors.budget.0', 'Минимальный бюджет 990 ₽.');
        $this->postJson('/quiz/subscribe', $this->payload(['receiving_phone' => '123']))->assertStatus(422)->assertJsonPath('errors.receiving_phone.0', 'Телефон в формате +7 924 000-00-00.');

        $r = $this->postJson('/quiz/subscribe', $this->payload(['plan_id' => null, 'budget' => 2500, 'option_ids' => []]))->assertCreated();
        $this->assertSame(2500.0, (float) Subscription::find($r->json('subscription_id'))->plan->price);

        User::create(['name' => 'Админ', 'email' => 'admin@site.local', 'phone' => '+79990000001', 'phone_e164' => '+79990000001', 'password' => 'x', 'role_id' => Role::ADMIN, 'is_active' => true]);
        $this->postJson('/quiz/subscribe', $this->payload(['sender_phone' => '+79990000001']))->assertStatus(422);
        $this->assertSame(0, Subscription::where('sender_phone', '+79990000001')->count());
    }

    public function test_amocrm_lead_is_created_on_order_and_updated_on_payment(): void {
        config(['services.amocrm.subdomain' => 'cvetofor', 'services.amocrm.token' => 'secret']);
        Settings::setMany(['amocrm_enabled' => true, 'amocrm_pipeline_id' => 77, 'amocrm_status_new' => 101, 'amocrm_status_paid' => 102]);
        Http::fake([
            'cvetofor.amocrm.ru/api/v4/leads/complex' => Http::response([['id' => 555, 'contact_id' => 777]], 200),
            'cvetofor.amocrm.ru/api/v4/leads/555/notes' => Http::response([], 200),
            'cvetofor.amocrm.ru/api/v4/leads/555' => Http::response(['id' => 555], 200),
        ]);

        $id = $this->postJson('/quiz/subscribe', $this->payload())->assertCreated()->json('subscription_id');
        $sub = Subscription::findOrFail($id);
        $this->assertSame(555, (int) $sub->amo_lead_id);
        Http::assertSent(function ($request) {
            if (!str_ends_with($request->url(), '/leads/complex')) {
                return false;
            }
            $lead = $request->data()[0];

            return $lead['pipeline_id'] === 77 && $lead['status_id'] === 101 && $lead['price'] === 9870
                && $lead['_embedded']['contacts'][0]['custom_fields_values'][0]['values'][0]['value'] === '+79247740404'
                && $request->hasHeader('Authorization', 'Bearer secret');
        });
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/leads/555/notes') && str_contains($r->data()[0]['params']['text'], 'Получатель: Анна'));

        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('payment.test', now()->addDay(), ['subscription' => $id]))->assertOk();
        $this->post('/payment/test/' . $id)->assertRedirect();
        Http::assertSent(fn ($r) => $r->method() === 'PATCH' && str_ends_with($r->url(), '/leads/555') && $r->data()['status_id'] === 102 && $r->data()['price'] === 9870);
    }

    public function test_amocrm_is_silent_when_disabled(): void {
        Http::fake();
        $this->postJson('/quiz/subscribe', $this->payload())->assertCreated();
        Http::assertNothingSent();
        $this->assertNull(Subscription::first()->amo_lead_id);
    }

    public function test_sms_kinds_can_be_switched_off(): void {
        Settings::set('sms', ['activated' => false, 'reminder' => true, 'renewed' => true, 'payment_failed' => true, 'code' => true]);
        $this->assertFalse(Settings::smsEnabled('activated'));
        $this->assertTrue(Settings::smsEnabled('reminder'));
        $this->assertFalse(\App\Services\Sms::send('+79247740404', 'тест', 'activated'));
        $this->assertTrue(\App\Services\Sms::send('+79247740404', 'тест', 'reminder'));
    }

    public function test_track_accepts_quiz_events_with_design(): void {
        $this->withCredentials()->withCookies(['ab_design' => 'quiz', 'ab_visitor' => str_repeat('ab', 16)])
            ->postJson('/frontend-api/track', ['event' => 'quiz_step', 'step' => 5, 'mode' => 'gift'])->assertNoContent();
        $e = AbEvent::first();
        $this->assertSame('quiz', $e->design);
        $this->assertSame('q', $e->variant);
        $this->assertSame(5, $e->step);
        $this->assertArrayHasKey('step_5', AbEvent::designFunnel(null)['quiz']);
    }

    public function test_design_stats_and_settings_pages_work_for_admin(): void {
        $admin = User::create(['name' => 'A', 'email' => 'a@site.local', 'password' => 'x', 'role_id' => Role::ADMIN, 'is_active' => true]);
        AbEvent::log('q', 'visit', str_repeat('ab', 16), ['design' => 'quiz']);
        AbEvent::log('a', 'visit', str_repeat('cd', 16), ['design' => 'classic']);
        $id = $this->postJson('/quiz/subscribe', $this->payload())->json('subscription_id');
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('payment.test', now()->addDay(), ['subscription' => $id]));
        $this->post('/payment/test/' . $id);

        $numbers = DesignFunnelWidget::numbers(null);
        $this->assertSame(1, $numbers['quiz']['visits']);
        $this->assertSame(1, $numbers['quiz']['orders']);
        $this->assertSame(1, $numbers['quiz']['paid']);
        $this->assertSame(9870.0, $numbers['quiz']['revenue']);
        $this->assertSame(0, $numbers['classic']['orders']);

        $this->actingAs($admin);
        $this->get('/hub/design-stats')->assertOk();
        Livewire::test(DesignFunnelWidget::class)->assertOk()->assertSee('Оплатили')->assertSee('9 870');

        Livewire::test(\App\Filament\Pages\SiteSettings::class)->assertOk()
            ->set('data.design_mode', 'quiz')
            ->set('data.sms.reminder', false)
            ->call('save')->assertHasNoErrors();
        Settings::forget();
        $this->assertSame('quiz', Settings::designMode());
        $this->assertFalse(Settings::smsEnabled('reminder'));
        $this->assertSame('quiz', Setting::find('design_mode')->value);

        $this->get('/hub/subscriptions')->assertOk();
        Livewire::test(\App\Filament\Resources\Subscriptions\Pages\EditSubscription::class, ['record' => $id])->assertOk()->assertSee('Квиз (дизайн 2)')->assertSee('Оплачена');
    }

    public function test_unpaid_order_gets_one_payment_link_sms_after_delay(): void {
        \Carbon\Carbon::setTestNow('2026-10-28 10:00:00');
        $id = $this->postJson('/quiz/subscribe', $this->payload())->assertCreated()->json('subscription_id');
        $sub = Subscription::findOrFail($id);
        $this->assertSame(0, \App\Services\UnpaidReminder::run(), 'час ещё не прошёл');

        \Carbon\Carbon::setTestNow('2026-10-28 11:05:00');
        $this->assertSame(1, \App\Services\UnpaidReminder::run());
        $this->assertSame(0, \App\Services\UnpaidReminder::run(), 'вторая СМС не уходит');
        $sub->refresh();
        $this->assertNotNull($sub->unpaid_reminded_at);
        $user = $sub->user->fresh();
        $this->assertNotNull($user->login_token);

        // Ссылка из СМС ведёт к оплате этой заявки без кода
        auth()->logout();
        $this->get('/k/' . $user->login_token . '?pay=' . $id)->assertRedirect('/subscription/' . $id . '/pay');
        $this->assertAuthenticatedAs($user);
        $this->get('/subscription/' . $id . '/pay')->assertRedirect();

        // Оплаченные заявки и выключенная настройка: без СМС
        auth()->logout();
        $id2 = $this->postJson('/quiz/subscribe', $this->payload(['sender_phone' => '+7 924 111-22-33']))->json('subscription_id');
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('payment.test', now()->addDay(), ['subscription' => $id2]));
        $this->post('/payment/test/' . $id2)->assertRedirect();
        \Carbon\Carbon::setTestNow('2026-10-28 13:00:00');
        $this->assertSame(0, \App\Services\UnpaidReminder::run());
        auth()->logout();
        $id3 = $this->postJson('/quiz/subscribe', $this->payload(['sender_phone' => '+7 924 333-22-11']))->json('subscription_id');
        Settings::set('sms', ['unpaid' => false]);
        \Carbon\Carbon::setTestNow('2026-10-28 15:00:00');
        $this->assertSame(0, \App\Services\UnpaidReminder::run());
        $this->assertNull(Subscription::find($id3)->unpaid_reminded_at);
        \Carbon\Carbon::setTestNow();
    }

    public function test_client_can_cancel_unpaid_order_from_cabinet(): void {
        $id = $this->postJson('/quiz/subscribe', $this->payload())->assertCreated()->json('subscription_id');
        $user = Subscription::findOrFail($id)->user;
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Оплатить 9 870 ₽')->assertSee('Отменить заказ');

        $stranger = User::create(['name' => 'Ч', 'email' => 'q@phone.local', 'phone' => '+79990000009', 'phone_e164' => '+79990000009', 'password' => 'x', 'role_id' => Role::CLIENT, 'is_active' => true]);
        $this->actingAs($stranger)->post("/subscription/{$id}/cancel")->assertForbidden();

        $this->actingAs($user)->post("/subscription/{$id}/cancel")->assertRedirect('/profile')->assertSessionHas('success');
        $sub = Subscription::findOrFail($id);
        $this->assertNotNull($sub->cancelled_at);
        $this->actingAs($user)->get('/profile')->assertOk()->assertDontSee('Отменить заказ');
        \Carbon\Carbon::setTestNow(now()->addHours(2));
        $this->assertSame(0, \App\Services\UnpaidReminder::run(), 'по отменённой заявке напоминание не уходит');
        \Carbon\Carbon::setTestNow();

        // Оплаченную отменить из кабинета нельзя
        auth()->logout();
        $id2 = $this->postJson('/quiz/subscribe', $this->payload(['sender_phone' => '+7 924 111-22-33']))->json('subscription_id');
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('payment.test', now()->addDay(), ['subscription' => $id2]));
        $this->post('/payment/test/' . $id2)->assertRedirect();
        $this->post("/subscription/{$id2}/cancel")->assertRedirect('/profile')->assertSessionHas('error');
        $this->assertNull(Subscription::find($id2)->cancelled_at);
    }

    public function test_address_is_checked_against_delivery_city_when_dadata_is_configured(): void {
        config(['services.dadata.token' => 'dd-key']);
        Http::fake([
            'suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address' => Http::sequence()
                ->push(['suggestions' => [['value' => 'г Иркутск, ул Ленина, д 12', 'data' => ['city' => 'Иркутск', 'house' => '12']]]])
                ->push(['suggestions' => [['value' => 'г Улан-Удэ, ул Ленина', 'data' => ['city' => 'Улан-Удэ', 'house' => null]]]])
                ->push(['suggestions' => [['value' => 'г Улан-Удэ, ул Ленина, д 12', 'data' => ['city' => 'Улан-Удэ', 'house' => '12']]]])
                ->push('', 503),
        ]);
        $this->postJson('/quiz/subscribe', $this->payload(['address' => 'Иркутск, Ленина 12']))->assertStatus(422)->assertJsonPath('errors.address.0', 'Пока доставляем только по городу Улан-Удэ. Проверьте улицу и дом.');
        $this->postJson('/quiz/subscribe', $this->payload(['address' => 'ул. Ленина']))->assertStatus(422)->assertJsonPath('errors.address.0', 'Укажите номер дома: по одной улице курьер не найдёт.');
        $id = $this->postJson('/quiz/subscribe', $this->payload(['address' => 'Ленина 12']))->assertCreated()->json('subscription_id');
        $this->assertSame('г Улан-Удэ, ул Ленина, д 12, кв. 34, домофон 34', Subscription::find($id)->address);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/suggest/address') && $r->data()['locations'][0]['city'] === 'Улан-Удэ' && $r->hasHeader('Authorization', 'Token dd-key'));

        // Сервис недоступен (четвёртый ответ 503): заказ не блокируем
        auth()->logout();
        $this->postJson('/quiz/subscribe', $this->payload(['sender_phone' => '+7 924 111-22-33', 'address' => 'Где-то далеко, 1']))->assertCreated();
    }

    public function test_address_check_is_skipped_without_dadata_key(): void {
        Http::fake();
        $this->postJson('/quiz/subscribe', $this->payload(['address' => 'Иркутск, Ленина 12']))->assertCreated();
        Http::assertNothingSent();
    }
}
