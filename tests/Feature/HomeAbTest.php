<?php

namespace Tests\Feature;

use App\Http\Middleware\AssignHeroVariant;
use App\Models\AbEvent;
use App\Models\City;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAbTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $city = City::create(['name' => 'Ярославль', 'code' => 'yar', 'is_active' => true]);
        Plan::create(['name' => 'Просто так', 'price' => 1999, 'description' => '', 'is_active' => true, 'is_custom' => false, 'city_id' => $city->id, 'scenario' => 'home', 'sort' => 1]);
    }

    public function test_home_assigns_variant_cookie_and_logs_visit(): void {
        $response = $this->get('/');
        $response->assertOk()->assertCookie(AssignHeroVariant::COOKIE)->assertCookie(AssignHeroVariant::VISITOR_COOKIE);
        $this->assertContains($response->getCookie(AssignHeroVariant::COOKIE)->getValue(), ['a', 'b']);
        $this->assertSame(1, AbEvent::where('event', 'visit')->count());
    }

    public function test_variant_can_be_forced_and_renders_matching_hero(): void {
        $this->get('/?hero=a')->assertOk()->assertSee('id="heroSeg"', false)->assertDontSee('id="modeCards"', false);
        $this->get('/?hero=b')->assertOk()->assertSee('id="modeCards"', false)->assertDontSee('id="heroSeg"', false);
    }

    public function test_bots_do_not_pollute_funnel(): void {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/')->assertOk();
        $this->assertSame(0, AbEvent::count());
    }

    public function test_plans_and_prices_are_shown(): void {
        $this->get('/?hero=a')->assertOk()->assertSee('Просто так')->assertSee('1 999');
    }
}
