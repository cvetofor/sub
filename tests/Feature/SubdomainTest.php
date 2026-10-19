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

    private function notification(array $metadata): array {
        return [
            'type' => 'notification',
            'event' => 'payment.succeeded',
            'object' => [
                'id' => '2f0a1b3c-0000-5000-8000-1234567890ab', 'status' => 'succeeded', 'paid' => true,
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
