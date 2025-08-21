<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller {
    public function create(Request $request): JsonResponse {
        if ($request->boolean('is_custom')) {
            $validated = $request->validate([
                'time_delivery' => 'required|integer',
                'sender_name' => 'required|string|max:255',
                'sender_phone' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'frequency' => 'required|string',
                'price' => 'required',
                'city_id' => 'required|integer',
                'option_ids' => 'required'
            ]);
        } else {
            $validated = $request->validate([
                'plan_id' => 'required|integer|max:8',
                'sender_name' => 'required|string|max:255',
                'sender_phone' => 'required|string|max:20',
                'frequency' => 'required|string'
            ]);
        }

        DB::beginTransaction();

        if ($request->boolean('is_custom')) {
            $plan = Plan::create([
                'name'      => 'Пользовательский план',
                'price'     => $request->price,
                'is_custom' => true,
                'city_id'   => $request->city_id,
            ]);

            if (!empty($request->option_ids)) {
                $plan->options()->sync($request->option_ids);
            }
        } else {
            $plan = Plan::findOrFail($validated['plan_id']);
        }

        $subscription = Subscription::create([
            'is_active' => true,
            'plan_id' => $plan->id,
            'time_delivery_id' => $request->time_delivery ?? null,
            'sender_name' => $request->sender_name,
            'receiving_name' => $request->receiving_name ?? $request->sender_name,
            'address' => $request->address ?? null,
            'frequency' => $request->frequency,
            'comment' => $request->comment ?? null,
            'using_promo' => $request->using_promo ?? false,
            'sender_phone' => $request->sender_phone,
            'receiving_phone' => $request->receiving_phone ?? $request->sender_phone,
        ]);

        DB::commit();

        return response()->json([
            'success'      => true,
            'debug' => $subscription
        ], 201);
    }
}
