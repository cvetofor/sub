<?php

namespace Tests\Feature;

use App\Http\Middleware\AssignDesign;
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
        $response = $this->get('/?design=classic');
        $response->assertOk()->assertCookie(AssignDesign::COOKIE)->assertCookie(AssignDesign::VISITOR_COOKIE)->assertCookie(AssignDesign::DESIGN_COOKIE, 'classic');
        $this->assertContains($response->getCookie(AssignDesign::COOKIE)->getValue(), ['a', 'b']);
        $this->assertSame(1, AbEvent::where('event', 'visit')->count());
    }

    public function test_variant_can_be_forced_and_renders_matching_hero(): void {
        $this->get('/?design=classic&hero=a')->assertOk()->assertSee('id="heroSeg"', false)->assertDontSee('id="modeCards"', false);
        $this->get('/?design=classic&hero=b')->assertOk()->assertSee('id="modeCards"', false)->assertDontSee('id="heroSeg"', false);
    }

    public function test_bots_do_not_pollute_funnel(): void {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/')->assertOk();
        $this->assertSame(0, AbEvent::count());
    }

    public function test_plans_and_prices_are_shown(): void {
        $this->get('/?design=classic&hero=a')->assertOk()->assertSee('Просто так')->assertSee('1 999');
    }

    public function test_agreement_and_error_pages_use_new_layout(): void {
        $this->get('/user_agreement')->assertOk()->assertSee('Пользовательское соглашение')->assertSee('class="hdr"', false);
        $this->get('/no-such-page')->assertNotFound()->assertSee('Такой страницы нет')->assertSee('noindex', false);
    }
}
