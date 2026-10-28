<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller {
    public function index(Request $request) {
        // Второй дизайн: квиз на той же странице, выбор сделал middleware AssignDesign
        if ($request->attributes->get('design') === \App\Services\Settings::DESIGN_QUIZ) {
            return app(QuizController::class)->index($request);
        }

        $plans = Plan::showcase()->get()->map(fn (Plan $plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'tag' => $plan->tag ?? Plan::SCENARIOS[$plan->scenario] ?? '',
            'scenario' => $plan->scenario,
            'price' => (float) $plan->price,
            'description' => $plan->description,
            'image' => $plan->imageUrl(),
        ])->values();

        $variant = $request->attributes->get('hero_variant', 'a');
        $pricing = Pricing::table();

        return view('home', [
            'variant' => $variant,
            'plans' => $plans,
            'pricing' => $pricing,
            'firstBouquetDate' => self::nextSaturday(),
            'gallery' => self::gallery(),
            // Данные для скрипта страницы. Флаги: безопасно для вставки внутрь <script>.
            'siteJson' => json_encode(
                ['variant' => $variant, 'pricing' => $pricing, 'plans' => $plans, 'wizardUrl' => route('wizard')],
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ),
        ]);
    }

    /** Ближайшая суббота: день первой доставки, если оплатить сегодня. */
    public static function nextSaturday(): Carbon {
        $date = Carbon::today();
        $days = (6 - $date->dayOfWeek + 7) % 7 ?: 7;

        return $date->addDays($days)->locale('ru');
    }

    /** Фото для блока «Из последних доставок». Пока референсы, заменяются своими кадрами. */
    private static function gallery(): array {
        return [
            ['src' => asset('images/site/g-home-window.jpg'), 'alt' => 'Оранжевые гвоздики в вазе у окна', 'plan' => 'Просто так'],
            ['src' => asset('images/site/g-gift-tulips.jpg'), 'alt' => 'Тюльпаны в руках на белом фоне', 'plan' => 'С любовью'],
            ['src' => asset('images/site/g-home-table.jpg'), 'alt' => 'Полевые цветы в стеклянной вазе на столе', 'plan' => 'Просто так'],
            ['src' => asset('images/site/g-author-window.jpg'), 'alt' => 'Крупная композиция у окна', 'plan' => 'С размахом'],
        ];
    }

    /** Карта сайта: только публичные страницы. Мастер, кабинет и оплата закрыты от индексации. */
    public function sitemap() {
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('user_agreement'), 'changefreq' => 'yearly', 'priority' => '0.2'],
        ];

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    /** robots.txt с адресом карты сайта текущего домена. */
    public function robots() {
        $lines = ['User-agent: *', 'Disallow: /subscribe', 'Disallow: /auth/', 'Disallow: /profile', 'Disallow: /payment/', 'Disallow: /hub', 'Disallow: /frontend-api/', '', 'Sitemap: ' . route('sitemap')];

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
