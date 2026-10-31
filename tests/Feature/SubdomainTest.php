<?php

namespace Tests\Feature;

use App\Http\Api\Yookassa;
use App\Support\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Новый сайт на соседнем поддомене рядом со старым: тексты, robots, общий вебхук ЮKassa. */
class SubdomainTest extends TestCase {
    use RefreshDatabase;

    public function test_site_host_comes_from_env_or_app_url(): void {
        config(['app.site_host' => null, 'app.url' => 'https://xn--b1ag3bn2a.xn--b1ag1aakjpl.xn--p1ai']);
        $this->assertSame('цветы.цветофор.рф', Site::host());
        config(['app.site_host' => 'подарок.цветофор.рф']);
        $this->assertSame('подарок.цветофор.рф', Site::host());
    }

    public function test_robots_points_to_current_domain_sitemap(): void {
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /subscribe')->assertSee('Sitemap: ' . route('sitemap'));
    }

    public function test_foreign_yookassa_notification_is_forwarded_to_legacy_site(): void {
        config(['yookassa.legacy_callback' => 'https://old.example/api/payment/yookassa/callback']);
        Http::fake(['old.example/*' => Http::response('', 200)]);
        $payload = $this->notification(metadata: ['sub_id' => '5', 'is_reccurent' => '0']);

        $this->postJson('/api/payment/yookassa/callback', $payload, ['REMOTE_ADDR' => '185.71.76.1'])->assertNoContent(200);
        Http::assertSent(fn ($r) => $r->url() === 'https://old.example/api/payment/yookassa/callback' && $r['object']['id'] === '2f0a1b3c-0000-5000-8000-1234567890ab');
    }

    public function test_foreign_notification_without_legacy_url_is_acknowledged(): void {
        config(['yookassa.legacy_callback' => null]);
        Http::fake();
        $this->postJson('/api/payment/yookassa/callback', $this->notification(['sub_id' => '5']), ['REMOTE_ADDR' => '185.71.76.1'])->assertNoContent(200);
        Http::assertNothingSent();
    }

    public function test_untrusted_ip_is_rejected_before_parsing(): void {
        $this->postJson('/api/payment/yookassa/callback', $this->notification(['site' => Yookassa::SITE_TAG]), ['REMOTE_ADDR' => '8.8.8.8'])->assertStatus(403);
    }

    public function test_canceled_first_payment_is_marked_and_recurrent_cancel_pauses_subscription(): void {
        \Illuminate\Support\Facades\DB::table('roles')->insert([['id' => 3, 'name' => 'Клиент', 'code' => 'client']]);
        $city = \App\Models\City::create(['name' => 'Ярославль', 'code' => 'yar', 'is_active' => true]);
        $plan = \App\Models\Plan::create(['name' => 'С любовью', 'price' => 2990, 'description' => '', 'is_active' => true, 'is_custom' => false, 'city_id' => $city->id, 'scenario' => 'any', 'sort' => 2]);
        $sub = \App\Models\Subscription::create(['is_active' => true, 'plan_id' => $plan->id, 'frequency' => 'biweekly', 'delivery_day' => 6, 'sender_name' => 'Анна', 'sender_phone' => '+79123456789', 'address' => 'Ярославль, ул. Свободы, 1']);
        $payment = \App\Models\Payment::create(['payment_status_id' => \App\Models\Payment::IN_PROGRESS, 'amount' => 2990, 'payment_gateway_transaction' => '2f0a1b3c-0000-5000-8000-aaaaaaaaaaaa']);
        $sub->payments()->attach($payment->id);

        // Отмена первого платежа: платёж помечен, подписка не трогается
        $this->postJson('/api/payment/yookassa/callback', $this->notification(['sub_id' => (string) $sub->id, 'is_reccurent' => '0', 'site' => Yookassa::SITE_TAG, 'payment_id' => $payment->payment_gateway_transaction], 'payment.canceled', 'canceled'), ['REMOTE_ADDR' => '185.71.76.1'])->assertNoContent(200);
        $this->assertSame(\App\Models\Payment::CANCELED, (int) $payment->fresh()->payment_status_id);
        $this->assertTrue((bool) $sub->fresh()->is_active);

        // Отмена повторного списания: подписка на паузе с причиной
        $this->postJson('/api/payment/yookassa/callback', $this->notification(['sub_id' => (string) $sub->id, 'is_reccurent' => '1', 'site' => Yookassa::SITE_TAG, 'payment_id' => '2f0a1b3c-0000-5000-8000-bbbbbbbbbbbb'], 'payment.canceled', 'canceled'), ['REMOTE_ADDR' => '185.71.76.1'])->assertNoContent(200);
        $this->assertFalse((bool) $sub->fresh()->is_active);
        $this->assertSame(\App\Models\Subscription::PAUSE_PAYMENT, $sub->fresh()->pause_reason);

        // Банк сначала принял повторное списание: после success эта же запись должна стать оплаченной.
        $pendingPayment = \App\Models\Payment::create([
            'payment_status_id' => \App\Models\Payment::IN_PROGRESS,
            'amount' => 2990,
            'payment_gateway_transaction' => '2f0a1b3c-0000-5000-8000-cccccccccccc',
            'idempotence_key' => str_repeat('a', 64),
            'recurrent_token' => 'pm-1',
        ]);
        $sub->payments()->attach($pendingPayment->id);

        // Успешное повторное списание по уведомлению: запись обновлена, подписка снова активна, доставки запланированы.
        $this->postJson('/api/payment/yookassa/callback', $this->notification(['sub_id' => (string) $sub->id, 'is_reccurent' => '1', 'site' => Yookassa::SITE_TAG, 'payment_id' => $pendingPayment->payment_gateway_transaction]), ['REMOTE_ADDR' => '185.71.76.1'])->assertNoContent(200);
        $this->assertTrue((bool) $sub->fresh()->is_active);
        $this->assertSame(2, $sub->payments()->count());
        $this->assertSame(\App\Models\Payment::PAYED, (int) $pendingPayment->fresh()->payment_status_id);
        $this->assertSame(2, $sub->deliveries()->count());

        // ЮKassa вправе повторить webhook: это не должно продлить подписку и создать доставки второй раз.
        $nextDate = $sub->fresh()->next_date_payment->toDateTimeString();
        $this->postJson('/api/payment/yookassa/callback', $this->notification(['sub_id' => (string) $sub->id, 'is_reccurent' => '1', 'site' => Yookassa::SITE_TAG, 'payment_id' => $pendingPayment->payment_gateway_transaction]), ['REMOTE_ADDR' => '185.71.76.1'])->assertNoContent(200);
        $this->assertSame(2, $sub->payments()->count());
        $this->assertSame(2, $sub->deliveries()->count());
        $this->assertSame($nextDate, $sub->fresh()->next_date_payment->toDateTimeString());

        // Привязка способа оплаты: без действий, но 200
        $this->postJson('/api/payment/yookassa/callback', $this->notification(['sub_id' => (string) $sub->id, 'site' => Yookassa::SITE_TAG], 'payment_method.active'), ['REMOTE_ADDR' => '185.71.76.1'])->assertNoContent(200);
    }

    public function test_application_uses_ulan_ude_timezone(): void {
        $this->assertSame('Asia/Irkutsk', config('app.timezone'));
    }

    private function notification(array $metadata, string $event = 'payment.succeeded', string $status = 'succeeded'): array {
        return [
            'type' => 'notification',
            'event' => $event,
            'object' => [
                'id' => $metadata['payment_id'] ?? '2f0a1b3c-0000-5000-8000-1234567890ab', 'status' => $status, 'paid' => $status === 'succeeded',
                'amount' => ['value' => '100.00', 'currency' => 'RUB'],
                'created_at' => '2026-10-19T10:00:00.000Z',
                'metadata' => $metadata,
                'payment_method' => ['type' => 'bank_card', 'id' => 'pm-1', 'saved' => true],
                'recipient' => ['account_id' => '1', 'gateway_id' => '1'],
                'refundable' => false, 'test' => true,
            ],
        ];
    }
}
