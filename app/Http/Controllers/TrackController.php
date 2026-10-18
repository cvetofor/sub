<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AssignHeroVariant;
use App\Models\AbEvent;
use App\Services\Pricing;
use Illuminate\Http\Request;

/** Приём событий воронки с фронтенда в журнал A/B-теста. */
class TrackController extends Controller {
    public function store(Request $request) {
        $data = $request->validate([
            'event' => 'required|string|in:' . implode(',', array_diff(config('analytics.events'), ['visit', 'payment_success'])),
            'step' => 'nullable|integer|min:1|max:4',
            'plan_id' => 'nullable|integer|min:1',
            'mode' => 'nullable|string|in:home,gift',
            'frequency' => 'nullable|string|in:' . implode(',', Pricing::frequencies()),
            'source' => 'nullable|string|max:32',
        ]);

        [$variant, $visitor] = AssignHeroVariant::current($request);
        if (!$variant || !$visitor) {
            return response()->noContent(204);
        }

        AbEvent::log($variant, $data['event'], $visitor, $data);

        return response()->noContent(204);
    }
}
