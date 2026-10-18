<?php

namespace Tests\Feature;

use App\Models\AbEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackTest extends TestCase {
    use RefreshDatabase;

    private function visitor(): static {
        // Для JSON-запросов куки уходят только с withCredentials()
        return $this->withCredentials()->withCookies(['ab_hero' => 'b', 'ab_visitor' => str_repeat('ab', 16)]);
    }

    public function test_funnel_event_is_logged_with_variant(): void {
        $this->visitor()->postJson('/frontend-api/track', ['event' => 'wizard_step', 'step' => 3, 'plan_id' => 2, 'frequency' => 'weekly', 'mode' => 'gift'])
            ->assertNoContent();
        $event = AbEvent::first();
        $this->assertSame('b', $event->variant);
        $this->assertSame('wizard_step', $event->event);
        $this->assertSame(3, $event->step);
        $this->assertSame('weekly', $event->frequency);
        $this->assertArrayHasKey('wizard_step_3', AbEvent::funnel(null)['b']);
    }

    public function test_server_only_events_are_rejected(): void {
        $this->visitor()->postJson('/frontend-api/track', ['event' => 'payment_success'])->assertStatus(422);
        $this->visitor()->postJson('/frontend-api/track', ['event' => 'nope'])->assertStatus(422);
        $this->assertSame(0, AbEvent::count());
    }

    public function test_without_cookie_nothing_is_logged(): void {
        $this->postJson('/frontend-api/track', ['event' => 'plans_view'])->assertNoContent();
        $this->assertSame(0, AbEvent::count());
    }
}
